/**
 * Report Phish - Google Workspace Gmail Add-on
 * Sends reported message metadata to your PhishAware backend webhook.
 * Deploy as admin-managed or private Marketplace app for your domain only.
 */

var WEBHOOK_URL = 'https://YOUR_APP_URL/api/webhook/report'; // Set in project properties
var WEBHOOK_SECRET = 'YOUR_WEBHOOK_SECRET'; // Set in project properties (File > Project properties > Script properties)

/**
 * Build the add-on UI when user opens an email.
 */
function buildAddOn(e) {
  var accessToken = e.messageMetadata.accessToken;
  var messageId = e.messageMetadata.messageId;

  var card = CardService.newCardBuilder()
    .setHeader(CardService.newCardHeader()
      .setTitle('CyberGuard Report Phish')
      .setImageUrl('https://www.gstatic.com/images/branding/product/2x/gmail_48dp.png'))
    .addSection(CardService.newCardSection()
      .addWidget(CardService.newTextParagraph()
        .setText('Report this email as phishing or suspicious.')))
    .addSection(CardService.newCardSection()
      .addWidget(CardService.newTextButton()
        .setText('Report Phish')
        .setOnClickAction(CardService.newAction()
          .setFunctionName('showReportPhishOptions')
          .setParameters({ type: 'phish' }))))
    .addSection(CardService.newCardSection()
      .addWidget(CardService.newTextButton()
        .setText('Report Spam')
        .setOnClickAction(CardService.newAction()
          .setFunctionName('reportSpam')
          .setParameters({ type: 'spam' }))))
    .addSection(CardService.newCardSection()
      .addWidget(CardService.newTextButton()
        .setText('Mark Safe')
        .setOnClickAction(CardService.newAction()
          .setFunctionName('markAsSafe')
          .setParameters({ type: 'safe' }))))
    .build();

  return CardService.newActionResponseBuilder()
    .setNavigation(CardService.newNavigation().pushCard(card))
    .setStateChanged(true)
    .build();
}

/**
 * Show optional checkboxes then send Report Phish.
 */
function showReportPhishOptions(e) {
  var messageId = e.messageMetadata ? e.messageMetadata.messageId : null;
  if (!messageId) return sendReport(e, 'phish', []);
  var card = CardService.newCardBuilder()
    .setHeader(CardService.newCardHeader().setTitle('Report Phish'))
    .addSection(CardService.newCardSection()
      .addWidget(CardService.newSelectionInput()
        .setType(CardService.SelectionInputType.CHECK_BOX)
        .setFieldName('user_actions')
        .addItem('I clicked the link', 'clicked_link', false)
        .addItem('I entered information', 'entered_info', false)))
    .addSection(CardService.newCardSection()
      .addWidget(CardService.newTextButton()
        .setText('Send report')
        .setOnClickAction(CardService.newAction()
          .setFunctionName('reportPhishWithOptions')
          .setParameters({ type: 'phish', messageId: messageId, accessToken: e.messageMetadata.accessToken || '' }))))
    .build();
  return CardService.newActionResponseBuilder()
    .setNavigation(CardService.newNavigation().pushCard(card))
    .build();
}

function reportPhishWithOptions(e) {
  var messageId = e.parameters.messageId;
  var userActions = [];
  try {
    if (e.formInputs && e.formInputs.user_actions) {
      userActions = e.formInputs.user_actions instanceof Array ? e.formInputs.user_actions : [e.formInputs.user_actions];
    }
  } catch (err) {}
  var eventObj = { messageMetadata: { messageId: messageId, accessToken: e.parameters.accessToken || (e.messageMetadata ? e.messageMetadata.accessToken : null) } };
  return sendReport(eventObj, e.parameters.type || 'phish', userActions);
}

function reportPhish(e) {
  return sendReport(e, 'phish', []);
}

function reportSpam(e) {
  return sendReport(e, 'spam', []);
}

function markAsSafe(e) {
  return sendReport(e, 'safe', []);
}

function sendReport(e, reportType, userActions) {
  if (typeof userActions === 'undefined') userActions = [];
  var props = PropertiesService.getScriptProperties();
  var webhookUrl = props.getProperty('WEBHOOK_URL') || WEBHOOK_URL;
  var secret = props.getProperty('WEBHOOK_SECRET') || WEBHOOK_SECRET;

  var messageId = e.messageMetadata ? e.messageMetadata.messageId : null;
  var accessToken = e.messageMetadata ? e.messageMetadata.accessToken : null;

  if (!messageId || !accessToken) {
    return showToast('Could not read message. Please try again.');
  }

  var message;
  try {
    message = GmailApp.getMessageById(messageId);
  } catch (err) {
    return showToast('Could not read message. Please try again.');
  }
  if (!message) {
    return showToast('Could not read message. Please try again.');
  }

  var payload = buildPayloadFromGmailMessage(message, reportType);
  payload.user_actions = userActions;

  try {
    payload.reporter_email = Session.getActiveUser().getEmail();
  } catch (err) {
    // ignore
  }

  var body = JSON.stringify(payload);
  var options = {
    method: 'post',
    contentType: 'application/json',
    payload: body,
    muteHttpExceptions: true,
    headers: {
      'X-Phish-Signature': computeSignature(body, secret)
    }
  };

  try {
    var response = UrlFetchApp.fetch(webhookUrl, options);
    var code = response.getResponseCode();
    if (code >= 200 && code < 300) {
      return showToast('Report submitted. Thank you.');
    } else {
      var details = '';
      try {
        var bodyJson = JSON.parse(response.getContentText());
        details = bodyJson && bodyJson.error ? (' (' + bodyJson.error + ')') : '';
      } catch (e2) {}
      return showToast('Report failed. Please try again or contact IT.' + details);
    }
  } catch (err) {
    return showToast('Could not send report: ' + err.toString());
  }
}

/**
 * Build webhook payload from GmailApp GmailMessage (no Gmail advanced service required).
 */
function buildPayloadFromGmailMessage(message, reportType) {
  var threadId = message.getThread ? message.getThread() : null;
  var snippet = message.getPlainBody ? (message.getPlainBody().substring(0, 500) || '') : '';

  var payload = {
    report_type: reportType,
    gmail_message_id: message.getId(),
    gmail_thread_id: threadId ? threadId.getId() : null,
    subject: message.getSubject ? message.getSubject() : null,
    from: message.getFrom ? message.getFrom() : null,
    to: message.getTo ? message.getTo() : null,
    date: message.getDate ? message.getDate().toString() : null,
    snippet: snippet,
    headers: {}
  };

  var headers = message.getHeaders ? message.getHeaders() : [];
  for (var i = 0; i < headers.length; i++) {
    var h = headers[i];
    payload.headers[h.name] = h.value;
  }
  if (payload.from) {
    var match = payload.from.match(/<([^>]+)>/);
    payload.from_address = match ? match[1].trim() : payload.from;
    payload.from_display = match ? payload.from.replace(match[0], '').replace(/^["']|["']$/g, '').trim() : null;
  }
  payload.to_addresses = payload.to || null;

  return payload;
}

function computeSignature(bodyString, secret) {
  var sig = Utilities.computeHmacSha256Signature(bodyString, secret);
  var hex = sig.map(function(b) { return ('0' + (b & 0xFF).toString(16)).slice(-2); }).join('');
  return 'sha256=' + hex;
}

function showToast(message) {
  return CardService.newActionResponseBuilder()
    .setNotification(CardService.newNotification().setText(message))
    .build();
}
