# SpiderWaves Inbound Webhook Integration Guide (for UCC Developer)

This document provides all details required for **UCC** to send master-change webhook notifications to **SpiderWaves**.

---

## 1. Endpoint Details

| Item | Value |
|---|---|
| **Method** | `POST` |
| **Production URL** | `https://spider.veeragold.in/webhooks/ucc/master_changed` |
| **Signed Path (`PATH_WITH_QUERY`)** | `/webhooks/ucc/master_changed` *(no hosting sub-folder)* |
| **Transport** | HTTPS (TLS 1.2+), valid trusted SSL certificate |
| **Redirects** | None (direct `POST` endpoint, no HTTP→HTTPS or trailing-slash redirect) |
| **Timeout** | SpiderWaves queues the sync and responds within ~1–2 seconds |

---

## 2. UCC Configuration (`.env`)

Configure the following in UCC's environment:

```ini
spiderApi.webhookUrl    = https://spider.veeragold.in/webhooks/ucc/master_changed
spiderApi.webhookBearer = <UCC_WEBHOOK_BEARER>
spiderApi.webhookSecret = <UCC_WEBHOOK_SECRET>
```

> **Note:** For security, the **Bearer key** and **HMAC secret** are shared separately via a secure channel (password manager / one-time secret link) and should never be committed to source control or sent together in plain chat/email.

---

## 3. Request Headers & Signature Specification

Every `POST` request from UCC must include:

| Header | Value |
|---|---|
| `Authorization` | `Bearer <UCC_WEBHOOK_BEARER>` |
| `X-Timestamp` | Current Unix timestamp in seconds (must be within **±300 seconds** of server time) |
| `X-Nonce` | Unique random string per request (max **64 characters**, retained for 15 minutes for replay protection) |
| `X-Signature` | Lowercase hex `HMAC-SHA256` using `<UCC_WEBHOOK_SECRET>` |
| `Content-Type` | `application/json` |

### `string_to_sign` Format

```text
string_to_sign = "POST" + "\n" + PATH_WITH_QUERY + "\n" + X-Timestamp + "\n" + X-Nonce + "\n" + sha256_hex(RAW_BODY)
X-Signature    = hash_hmac("sha256", string_to_sign, UCC_WEBHOOK_SECRET)
```

- `PATH_WITH_QUERY` is `/webhooks/ucc/master_changed` (including `?query` if any).
- `RAW_BODY` is the exact raw JSON bytes sent on the wire (do not re-encode or alter whitespace).

---

## 4. Request Payload

### Real Master-Change Notification
```json
{
  "master_version": "f4154ebaf175330eedbc3bdbd4f177d104d4383972a8f4badf90d3f0d8911ef6",
  "sent_at": "2026-11-25T12:00:00+00:00"
}
```
- SpiderWaves verifies the signature, queues a background `ucc:sync` job, and returns `200 OK` immediately.

### Test Ping (from UCC API Manager)
```json
{
  "master_version": "f4154ebaf175330eedbc3bdbd4f177d104d4383972a8f4badf90d3f0d8911ef6",
  "sent_at": "2026-11-25T12:00:00+00:00",
  "test": true
}
```
- SpiderWaves verifies all headers and signature, and returns `200 OK` with `"is_test": true` without triggering a sync.

---

## 5. Expected HTTP Responses

| Status | Meaning | Example Body |
|---|---|---|
| `200 OK` | Verified & accepted | `{"status":"ok","master_version":"...","is_test":false}` |
| `400 Bad Request` | Missing or invalid `master_version` in JSON | `{"status":"error","message":"A JSON object with master_version is required"}` |
| `401 Unauthorized` | Invalid bearer, malformed headers, timestamp > 300s off, invalid signature, or reused nonce | `{"status":"error","message":"Invalid signature"}` |
| `405 Method Not Allowed` | Non-`POST` request | `{"status":"error","message":"POST only"}` |
| `503 Service Unavailable` | Webhook credentials not yet configured on SpiderWaves `.env` | `{"status":"error","message":"Spider UCC webhook credentials ... are not configured in .env."}` |

---

## 6. Joint Verification Checklist

1. **Test Ping:** Click **Send test webhook** in UCC API Manager → expect `HTTP 200` with `"is_test": true`.
2. **Failure Drill:** Test with a wrong secret → expect `HTTP 401 Invalid signature`.
3. **Live Change Drill:** Update a master record in UCC → UCC sends notification → SpiderWaves returns `HTTP 200` and syncs the updated record automatically.

