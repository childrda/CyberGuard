# CyberGuard API

## Webhook: Report from Gmail Add-on

**Endpoint:** `POST /api/webhook/report`

**Purpose:** Accept reported message data from the Report Phish Gmail Add-on.

**Authentication:** HMAC-SHA256 signature in header `X-Phish-Signature`.

### Request

- **Content-Type:** `application/json`
- **Header:** `X-Phish-Signature: sha256=<hex>`  
  Where `hex` = HMAC-SHA256 of the **raw request body** (string) using `PHISHING_WEBHOOK_SECRET`.

### Body (JSON)

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| reporter_email | string | Yes | Email of the user reporting |
| report_type | string | No | `phish`, `spam`, or `safe` (default: phish) |
| gmail_message_id | string | No | Gmail API message ID |
| gmail_thread_id | string | No | Gmail thread ID |
| subject | string | No | Email subject |
| from | string | No | From header value |
| from_address | string | No | Parsed sender email |
| from_display | string | No | Sender display name |
| to_addresses | string | No | To header |
| date | string | No | Message date |
| snippet | string | No | Message snippet |
| headers | object | No | Header name → value |
| user_actions | array | No | e.g. `["clicked_link", "entered_password", "not_sure"]` |

### Response

**200 OK**
```json
{
  "ok": true,
  "reported_message_id": 1,
  "matched_simulation": true
}
```

**401 Unauthorized** – Invalid or missing signature.

**422 Unprocessable Entity** – Missing required field (e.g. reporter_email).

**503 Service Unavailable** – Add-on disabled (`GMAIL_REPORT_ADDON_ENABLED=false`).

### Rate limit

120 requests per minute per IP (configurable via throttle).
