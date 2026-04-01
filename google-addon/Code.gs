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
  if (!e || !e.messageMetadata) {
    var debugCard = CardService.newCardBuilder()
      .setHeader(CardService.newCardHeader().setTitle('CyberGuard Report Phish'))
      .addSection(CardService.newCardSection()
        .addWidget(CardService.newTextParagraph()
          .setText('This function is triggered by Gmail when viewing a message. Do not run buildAddOn() directly from the script editor.')))
      .build();
    return CardService.newActionResponseBuilder()
      .setNavigation(CardService.newNavigation().pushCard(debugCard))
      .build();
  }

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

/**
 * Ensure checkbox values are plain strings for the webhook JSON (Gmail may return mixed types).
 */
function coerceUserActionsToStrings(userActions) {
  if (!userActions) return [];
  var arr = userActions instanceof Array ? userActions : [userActions];
  var out = [];
  for (var i = 0; i < arr.length; i++) {
    var x = arr[i];
    if (typeof x === 'string' && x) {
      out.push(x);
    } else if (x && typeof x === 'object' && x.value) {
      out.push(String(x.value));
    } else if (x != null) {
      out.push(String(x));
    }
  }
  return out;
}

function reportPhishWithOptions(e) {
  var messageId = e.parameters.messageId;
  var userActions = [];
  try {
    if (e.formInputs && e.formInputs.user_actions) {
      var raw = e.formInputs.user_actions instanceof Array ? e.formInputs.user_actions : [e.formInputs.user_actions];
      userActions = coerceUserActionsToStrings(raw);
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
  var webhookUrl = (props.getProperty('WEBHOOK_URL') || WEBHOOK_URL || '').trim();
  var secret = (props.getProperty('WEBHOOK_SECRET') || WEBHOOK_SECRET || '').trim();
  var tenantDomain = (props.getProperty('TENANT_DOMAIN') || (typeof TENANT_DOMAIN !== 'undefined' ? TENANT_DOMAIN : '') || '').trim();

  if (!webhookUrl || webhookUrl.indexOf('YOUR_APP_URL') !== -1) {
    return showToast('Add-on not configured: WEBHOOK_URL is missing.');
  }
  if (!secret || secret.indexOf('YOUR_WEBHOOK_SECRET') !== -1) {
    return showToast('Add-on not configured: WEBHOOK_SECRET is missing.');
  }

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
  if (tenantDomain) payload.tenant_domain = tenantDomain;

  try {
    payload.reporter_email = Session.getActiveUser().getEmail();
  } catch (err) {
    // ignore
  }

  var body = JSON.stringify(payload);
  var bodySha = computeSha256(body);
  var options = {
    method: 'post',
    contentType: 'application/json',
    payload: body,
    muteHttpExceptions: true,
    headers: {
      'X-Phish-Signature': computeSignature(body, secret),
      'X-Body-SHA256': bodySha
    }
  };
  if (tenantDomain) options.headers['X-Tenant-Domain'] = tenantDomain;

  try {
    var response = UrlFetchApp.fetch(webhookUrl, options);
    var code = response.getResponseCode();
    if (code >= 200 && code < 300) {
      return showToastAndReset('Report submitted. Thank you.');
    } else {
      var apiError = '';
      var debugInfo = '';
      try {
        var bodyJson = JSON.parse(response.getContentText());
        apiError = bodyJson && bodyJson.error ? String(bodyJson.error) : '';
        if (bodyJson && bodyJson.debug) {
          var got = bodyJson.debug.received_signature || '';
          var exp = bodyJson.debug.expected_signature || '';
          debugInfo = ' got=' + got + ' expected=' + exp;
        }
      } catch (e2) {}
      var host = '';
      try {
        host = webhookUrl.replace(/^https?:\/\//i, '').split('/')[0];
      } catch (e3) {}

      var hint = '';
      if (code === 401 || /invalid signature/i.test(apiError)) {
        hint = 'Signature mismatch. Check WEBHOOK_SECRET in Script Properties and tenant webhook secret.';
      } else if (code === 422 && /unknown tenant/i.test(apiError)) {
        hint = 'Tenant not resolved. Set TENANT_DOMAIN (Script Properties) to your tenant domain.';
      } else if (code === 500 && /configuration error/i.test(apiError)) {
        hint = 'Server webhook config missing. Verify tenant webhook secret in CyberGuard.';
      } else if (code === 503) {
        hint = 'Add-on disabled on server (GMAIL_REPORT_ADDON_ENABLED=false).';
      } else if (code === 422) {
        hint = 'Payload validation failed. Check reporter email/domain mapping.';
      } else {
        hint = 'Unexpected server response.';
      }

      return showToast(
        'Report failed [' + code + ']. ' + hint +
        ' host=' + host +
        ', tenant=' + (tenantDomain || 'auto') +
        debugInfo
      );
    }
  } catch (err) {
    return showToast('Could not send report: ' + err.toString() + '. Check WEBHOOK_URL reachability.');
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
  // Normalize Message-ID explicitly for remediation workflows.
  var msgIdHeader = null;
  if (message.getHeader) {
    try {
      msgIdHeader = message.getHeader('Message-ID') || message.getHeader('message-id') || null;
    } catch (e) {}
  }
  if (!msgIdHeader) {
    msgIdHeader = payload.headers['Message-ID'] || payload.headers['message-id'] || null;
  }
  payload.message_id_header = msgIdHeader ? String(msgIdHeader).trim() : null;
  if (payload.from) {
    var match = payload.from.match(/<([^>]+)>/);
    payload.from_address = match ? match[1].trim() : payload.from;
    payload.from_display = match ? payload.from.replace(match[0], '').replace(/^["']|["']$/g, '').trim() : null;
  }
  payload.to_addresses = payload.to || null;

  return payload;
}

function computeSignature(bodyString, secret) {
  var sig = Utilities.computeHmacSha256Signature(bodyString, secret, Utilities.Charset.UTF_8);
  var hex = sig.map(function(b) { return ('0' + (b & 0xFF).toString(16)).slice(-2); }).join('');
  return 'sha256=' + hex;
}

function computeSha256(bodyString) {
  var dig = Utilities.computeDigest(Utilities.DigestAlgorithm.SHA_256, bodyString, Utilities.Charset.UTF_8);
  return dig.map(function(b) { return ('0' + (b & 0xFF).toString(16)).slice(-2); }).join('');
}

function showToast(message) {
  return CardService.newActionResponseBuilder()
    .setNotification(CardService.newNotification().setText(message))
    .build();
}

function showToastAndReset(message) {
  var doneCard = CardService.newCardBuilder()
    .setHeader(CardService.newCardHeader().setTitle('CyberGuard Report Phish'))
    .addSection(
      CardService.newCardSection()
        .addWidget(CardService.newTextParagraph().setText('Report submitted. You can close this panel.'))
    )
    .build();

  var nav = CardService.newNavigation().updateCard(doneCard);

  return CardService.newActionResponseBuilder()
    .setNavigation(nav)
    .setNotification(CardService.newNotification().setText(message))
    .setStateChanged(true)
    .build();
}
