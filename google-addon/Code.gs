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

  var message = Gmail.Users.Messages.get('me', messageId);
  var payload = buildPayload(message, reportType);
  payload.user_actions = userActions;

  // Get reporter email from Gmail profile if available
  try {
    var profile = Gmail.Users.getProfile('me');
    if (profile && profile.emailAddress) {
      payload.reporter_email = profile.emailAddress;
    }
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
      return showToast('Report failed. Please try again or contact IT.');
    }
  } catch (err) {
    return showToast('Could not send report: ' + err.toString());
  }
}

function buildPayload(message, reportType) {
  var payload = {
    report_type: reportType,
    gmail_message_id: message.id,
    gmail_thread_id: message.threadId,
    subject: null,
    from: null,
    to: null,
    date: null,
    snippet: message.snippet || null,
    headers: {}
  };

  var headers = message.payload && message.payload.headers ? message.payload.headers : [];
  for (var i = 0; i < headers.length; i++) {
    var h = headers[i];
    payload.headers[h.name] = h.value;
    if (h.name === 'Subject') payload.subject = h.value;
    if (h.name === 'From') payload.from = h.value;
    if (h.name === 'To') payload.to = h.value;
    if (h.name === 'Date') payload.date = h.value;
  }

  // Parse From into address and name if needed
  if (payload.from) {
    var match = payload.from.match(/<([^>]+)>/);
    payload.from_address = match ? match[1].trim() : payload.from;
    payload.from_display = match ? payload.from.replace(match[0], '').replace(/^["']|["']$/g, '').trim() : null;
  }

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
