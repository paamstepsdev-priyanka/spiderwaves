# Plan 2: Spider Developer. Local Copy of UCC Masters

Status: draft for verification.

Goal: keep a read-only local copy of five UCC masters, synced through UCC's API, with a manual **Revalidate Masters** button.

Files in this folder:

| File | Purpose |
|---|---|
| `plan.md` | This plan |
| `schema.sql` | All tables to create (MariaDB) |
| `openapi.yaml` | The UCC API you call (single source of truth for endpoints and row shapes) |
| `test_vectors.json` | Fixed inputs and expected outputs for signing, `row_hash`, manifest checksum and the webhook. Identical copy is given to the UCC developer |
| `webhook_receiver_example.php` | A working, tested reference for the webhook receiver (see "What we need from the Spider developer", 4.8) |

## 1. Scope and principles

You build:

- A signed API client for UCC.
- Five read-only tables: `ucc_customer`, `ucc_mapped_customer`, `ucc_wastage`, `ucc_purity`, `ucc_collection`, plus three supporting tables (see `schema.sql`).
- A sync job (cron) and a webhook endpoint that triggers it (requirements: "What we need from the Spider developer").
- A manual **Revalidate Masters** screen/button.
- Migration of Spider's own customer and mapped-customer data onto the copied tables.

Principles:

- **Spider owns no master data.** No create or edit screens for these tables. A missing customer is fixed in UCC and syncs across.
- Spider stores UCC's **ID and `private_key` exactly as UCC has them**. It never generates either.
- No direct access to UCC's database.
- Only these five masters are in scope.
- Never link records by name. Use the ID or `private_key`.

## What we need from the Spider developer

UCC's side is built and tested. This section lists everything we need from Spider, in the order it is needed. Where it says **must**, the integration does not work or is not safe without it.

### 1. First: exchange access in both directions

There are two separate sets of credentials. Never reuse one for the other.

| Direction | What | Who creates it | How it reaches the other side | Where it lives |
|---|---|---|---|---|
| Spider calls UCC (the API) | Bearer key + HMAC secret | A UCC developer, in UCC's API Manager. They are shown **once**. | Securely handed to the Spider developer | Spider's `.env` or secret store. **Never in code, the repository or logs.** |
| UCC calls Spider (the webhook) | Webhook bearer key + HMAC secret | **The Spider developer.** Generate them randomly (see below). | Securely handed to the UCC developer | UCC's `.env` (`spiderApi.webhookBearer`, `spiderApi.webhookSecret`) |

How to generate the webhook pair (any equivalent random source is fine):

```php
$webhookBearer = 'whk_' . bin2hex(random_bytes(24));   // 52 characters
$webhookSecret = bin2hex(random_bytes(32));              // 64 hex characters
```

Rules for both pairs:

- **Minimum strength:** bearer key at least 32 random characters, secret at least 32 random bytes. No words, no reused passwords.
- **Hand-over:** use a password manager share or a one-time secret link. Do **not** send secrets in plain email, chat or a ticket, and do not send the key and the secret in the same message.
- **Separate environments:** staging and production get different pairs.
- **Rotation:** tell the other side first and agree a time. In UCC, "Regenerate" invalidates the old API pair immediately, so Spider is locked out until it has the new one. There is no overlap period yet.
- **If a secret may have leaked:** say so at once. UCC deactivates the client; you issue a new webhook pair.

### 2. Information we need from you

| # | What we need | Why | Needed by |
|---|---|---|---|
| 1 | **Webhook URL, production** (full URL with path), for example `https://spider.example.com/webhooks/ucc/master_changed` | Goes into UCC's `spiderApi.webhookUrl` | Before UCC enables the webhook |
| 2 | **Webhook URL, staging** (and a staging webhook pair) | We test there first | Before any production test |
| 3 | **The exact URL path**, and whether Spider lives in a **sub-folder** (for example `/spider/...`) | The signed path is the exact path of this URL. A sub-folder is the most common cause of "Invalid signature". | With #1 |
| 4 | **The IP address(es) Spider calls UCC from**, static, production and staging | UCC adds them to the API key's IP allow-list. Until we have them the key is open to any IP. | Before go-live |
| 5 | **Whether your firewall restricts incoming calls**, and if so, which source IP you will allow. Ask us for UCC's outbound IP. | UCC's server must be able to reach your webhook | Before the first webhook test |
| 6 | **PHP version, framework, MariaDB version** (10.2.7 or newer is required for the `JSON` column) | The plan assumes CodeIgniter 4 and MariaDB | Before building |
| 7 | **Server time zone and confirmation that the server clock is synchronised (NTP)** | Both sides reject a timestamp more than 300 seconds off | Before the first test |
| 8 | **A contact and a channel** for webhook failures, plus who should be alerted | UCC shows failures in API Manager, but someone has to act | Before go-live |
| 9 | **Maintenance windows** (when Spider may be down) | So a planned outage is not mistaken for a fault | Before go-live |
| 10 | **Which Spider roles may use "Revalidate Masters"** | Access control for that screen | Before go-live |

### 3. What UCC gives you

- The API base URL, your bearer key and HMAC secret, and an IP allow-list entry for the addresses you give us.
- `openapi.yaml` (the API contract), `test_vectors.json` (fixed examples for signing, hashes and the webhook) and `webhook_receiver_example.php` (a working receiver, see 4.8).
- UCC's outbound IP address, on request, for your firewall.
- A **Send test webhook** button in UCC's API Manager. It sends a signed ping to your URL on demand and shows exactly what your endpoint answered, with a plain-language reason for any failure.
- Limits: the API allows 300 requests per client per minute. A normal sync uses a handful of calls.

### 4. The webhook receiver: requirements

The webhook only tells you "something changed, sync now". It carries **no data**. Everything you need is pulled from the API afterwards.

#### 4.1 The endpoint

| | |
|---|---|
| Method and URL | `POST https://<your host>/webhooks/ucc/master_changed` (the path is yours to choose; tell us the exact URL) |
| Transport | **HTTPS only**, TLS 1.2 or newer, a **valid certificate from a trusted authority**. UCC verifies the certificate; a self-signed or expired one makes every call fail. |
| Redirects | **None.** UCC does not follow redirects (a redirected POST would lose its body and its signed path). Give us the final URL: no `http` to `https` redirect, no trailing-slash redirect. |
| Authentication | Only the headers below. No cookies, no session, no login page in front of it, no CSRF token requirement. |
| Body size | Tiny (under 1 KB). |

#### 4.2 What UCC sends

Headers:

| Header | Value |
|---|---|
| `Authorization` | `Bearer <webhook bearer key>` |
| `X-Timestamp` | Unix seconds |
| `X-Nonce` | Random, unique per call, at most 64 characters |
| `X-Signature` | `hex(HMAC_SHA256(webhook secret, string_to_sign))` |
| `Content-Type` | `application/json` |

Body (compact JSON, sent exactly like this):

| Field | Type | Meaning |
|---|---|---|
| `master_version` | string | The current data version token (64 hex). **A hint only**: compare it for equality with the last token you applied. |
| `sent_at` | string | Time UCC sent it, ISO 8601. For your logs. |
| `test` | boolean, optional | Present and `true` only for a test ping from UCC's API Manager. Absent on real notifications. |

```
string_to_sign = "POST" + "\n" + PATH_WITH_QUERY + "\n" + X-Timestamp + "\n" + X-Nonce + "\n" + sha256_hex(RAW_BODY)
```

- `PATH_WITH_QUERY` is the **exact path of the URL UCC calls**, including any hosting sub-folder. Use the path of the request URL **exactly as you received it**.
- `RAW_BODY` is the **raw bytes received**. Never re-encode the JSON: whitespace or key order would change the hash.

A complete example (from `test_vectors.json` > `webhook`):

```
POST /webhooks/ucc/master_changed
Authorization: Bearer whk_example_bearer_do_not_use
X-Timestamp: 1700000500
X-Nonce: webhook-nonce-0001
X-Signature: 8807a5e78ded52a4144837bcad9d87e0615012f917f103812627f62eff2f9398
Content-Type: application/json

{"master_version":"ee1f3ec382b3409bd35170893f7ddb60fef272766974817fd2011bb711ff6134","sent_at":"2023-11-14T22:21:40+00:00"}
```

and a test ping from an app hosted in a sub-folder (`test_vectors.json` > `webhook_subfolder_test`). Note that the signed path includes `/spider`:

```
POST /spider/webhooks/ucc/master_changed
X-Timestamp: 1700000600
X-Nonce: webhook-nonce-0002
X-Signature: c60c83ba824c80e10860727792746761aa1fa1e976e99d124f6a5c8fbac91118

{"master_version":"ee1f3ec382b3409bd35170893f7ddb60fef272766974817fd2011bb711ff6134","sent_at":"2023-11-14T22:23:20+00:00","test":true}
```

The secret for both examples is `whk_example_secret_do_not_use_in_production_0123456789abcdef` and the bearer key is `whk_example_bearer_do_not_use`. They are examples only; never use them for real.

#### 4.3 Verification, in this order

Reject the call at the first failure. Answer with the status and message shown (the reference receiver does exactly this; generic messages leak nothing).

| # | Check | If it fails | Answer |
|---|---|---|---|
| 1 | Method is `POST` | | `405` |
| 2 | `Authorization` equals `Bearer <your webhook bearer key>` (constant-time compare, `hash_equals`) | | `401 Invalid credentials` |
| 3 | `X-Timestamp` is digits only, `X-Nonce` is present and at most 64 characters | | `401 Malformed authentication headers` |
| 4 | `X-Timestamp` is within **300 seconds** of your clock, past or future | | `401 Timestamp outside allowed window` |
| 5 | `X-Signature` equals the HMAC you compute (constant-time compare) | | `401 Invalid signature` |
| 6 | The nonce has **not been seen** in the last 10 minutes or more. Store it **only now**, after step 5 (so strangers cannot fill your nonce store). Keep nonces for at least 10 minutes; 15 is comfortable. | | `401 Nonce already used` |
| 7 | The body is a JSON object with a non-empty string `master_version` | | `400` |

#### 4.4 What to answer, how fast, and what UCC does next

| Your answer | UCC treats it as | What UCC does |
|---|---|---|
| `200` (any `2xx`) | Delivered | Done |
| Any other status, a timeout, or no connection | **Failed** | Retries later (see below) |

- **Answer within a second or two.** UCC waits at most **10 seconds**, then counts the call as failed.
- **Retry schedule:** after a failed attempt UCC waits 2, 4, 8, 16, 32, then 60 and 60 minutes, and gives up after **8 attempts, about three hours** in total. The sender runs once a minute, so each wait is rounded up to the next minute.
- **Only the newest notification matters.** If the data changes again while an older notification is still waiting, the older one is dropped and replaced. At most one is pending.
- **The webhook is not guaranteed delivery.** Your own timed sync (every 5 to 10 minutes) stays as the safety net, and you must also run a sync when Spider starts up after downtime.

#### 4.5 What to do after a valid call

- If `test` is `true`: **answer `200` and do nothing else.** It only proves that the connection, the credentials and the signature work.
- Otherwise: **queue a sync and answer `200` immediately.** Never run the sync inside the request.
  - Queueing can be as simple as setting a "sync requested" flag or inserting a row that your sync worker picks up within seconds.
  - Several notifications close together must lead to **one** sync, not many.
  - The sync itself is the manifest comparison described in Phase 3. `master_version` is only a hint: if it equals your stored `last_version`, there is nothing to do.

#### 4.6 Duplicates, order and safety

- The same notification can arrive **more than once** (a retry after a slow answer) and **out of order**. Your handler must be safe to run repeatedly.
- Never treat the webhook body as data. Never write anything to the master tables from the webhook.
- A real notification and a test ping must be distinguishable in your logs.

#### 4.7 Security and operations

- Keep the webhook bearer key and secret in an environment variable or secret store. Never log them, and never log the full signature.
- Return the generic messages above, not stack traces or details of what failed.
- Rate-limit the endpoint (for example 60 calls per minute per source IP). UCC sends at most about one real notification per minute.
- If you can, allow only UCC's outbound IP (ask us for it) at the firewall. This is an extra lock; the signature is the real protection.
- Log every call: time, test or real, short token, result (accepted or which check failed). Show "last webhook received" on the Revalidate Masters screen.
- Alert someone if Spider has received no webhook for a long time while its own sync keeps finding changes (that means the webhook path is broken but the safety net is working).

#### 4.8 Reference implementation and how to check yours

- `webhook_receiver_example.php`: a verified implementation of 4.3. Copy the function `verify_ucc_webhook()` into Spider (it has no framework dependency). Run the file itself to get a small demo endpoint:
  ```
  WEBHOOK_BEARER=whk_test WEBHOOK_SECRET=some_secret php -S localhost:8091 webhook_receiver_example.php
  ```
- `test_vectors.json`: `webhook` and `webhook_subfolder_test`. Your receiver, given the example's headers, path and body and a clock set to the example timestamp, must answer `200`. Altering any one byte of the body, the path or the secret must give `401 Invalid signature`.
- The reference receiver has been tested against UCC's real sender: valid calls, test pings, wrong bearer, wrong secret, a tampered body, a wrong path, a timestamp 301 seconds off in both directions, a reused nonce, a missing nonce, a bad body and a wrong method.

### 5. How we will test together

| Step | Who | What | Expected result |
|---|---|---|---|
| 1 | Spider | Run the two webhook vectors and the signing vectors against your code | All accepted; any one-byte change is rejected |
| 2 | Spider | Send us the staging URL and the staging webhook pair | |
| 3 | UCC | Put the three keys in UCC's `.env` and enable the cron job | API Manager shows the webhook as configured |
| 4 | UCC | Press **Send test webhook** | A green "Delivered: HTTP 200" |
| 5 | Both | Failure drill: Spider answers `401` for a wrong secret | UCC shows the HTTP status and a plain-language reason |
| 6 | Both | Failure drill: take the endpoint offline and press the button | UCC says it could not reach the URL |
| 7 | Both | Change a master value in UCC | Within about a minute a real notification arrives; Spider queues **one** sync |
| 8 | Spider | Check the master tables and run **Revalidate Masters** | No differences |
| 9 | Both | Downtime drill: stop the endpoint, change data, start it again | Either a retry delivers, or Spider's timed sync catches up. No data is lost either way. |
| 10 | Both | Repeat 3 to 8 on production | Same results |

### 6. Acceptance checklist

- [ ] The two webhook vectors and the signing vectors pass in Spider's code.
- [ ] The endpoint is on HTTPS with a trusted certificate, with no redirect.
- [ ] A test ping from UCC returns `200` and triggers **no** sync.
- [ ] A real notification triggers exactly **one** queued sync and answers within a second or two.
- [ ] A wrong secret, a wrong bearer key, an old timestamp and a reused nonce are each rejected with the messages in 4.3.
- [ ] The nonce store keeps nonces for at least 10 minutes and is written only after the signature is valid.
- [ ] Spider's timed sync still runs, and a sync also runs when Spider starts.
- [ ] Secrets are not in the repository or in logs.
- [ ] Every call is logged; someone is alerted when the webhook path breaks.
- [ ] All the information in section 2 has been sent to UCC.

### 7. Things Spider must not do

- Do not run the sync inside the webhook request.
- Do not trust the webhook body as data, or apply it to the master tables.
- Do not skip TLS verification, in either direction.
- Do not use the API credentials for the webhook, or the other way round.
- Do not put secrets in code, the repository, logs, tickets or plain chat.
- Do not compute the signature over re-encoded JSON, or over a path that differs from the URL UCC calls.
- Do not remove your timed sync because the webhook exists.

## 2. Phases

### Phase 1: UCC client

- [ ] `UccClient` service. Base URL, bearer key and HMAC secret come from `.env` (never committed).
- [ ] Timeouts (e.g. 10 s connect, 30 s total), limited retries on network errors and 5xx, **TLS verification always on**.
- [ ] Signs every request as in section 4. Handles 401/403 (stop and surface) and 429 (back off).
- [ ] Verify against the `signing` cases in `test_vectors.json`.

### Phase 2: Tables

- [ ] Create everything in `schema.sql` as migrations.
- [ ] No create/edit screens for `ucc_*` tables. Read-only model classes; only the sync service writes (use a separate DB user or permission if you can).
- [ ] All active queries filter `deleted_at IS NULL`.
- [ ] No foreign keys: a conflict-skipped row must not block the rest of the sync. Orphans are reported by Revalidate Masters.

### Phase 3: Sync job, webhook, Revalidate Masters

#### Sync job

A spark command (e.g. `php spark ucc:sync`) run by cron every 5 to 10 minutes. Use a lock so runs never overlap.

UCC has no change log and no triggers. Spider finds changes by **comparing row hashes**. The cron sync and the Revalidate Masters screen use the same comparison; the cron job applies the result automatically, the screen shows it first.

1. **Has anything changed?** `GET /masters/manifest`. If `meta.master_version` equals `ucc_sync_state.last_version`, stop. (The token is a hash: compare for equality only, never "greater than".)
2. **First load** (`last_version` is null): `GET /masters/snapshot?entity=<e>` per entity in this order: purities, wastages, collections, customers, mapped_customers, paging with `after_id`. Insert every row.
3. **Otherwise, per entity whose `checksum` differs from the local checksum** (computed over live rows, see section 4): page `GET /masters/manifest?entity=<e>&keys=1` to get `{id, private_key, row_hash}` for every UCC row, then diff against local rows by `private_key`:
   - key missing locally, or `row_hash` different from the **recomputed** hash of the local row: fetch those keys with `POST /masters/rows` (200 per call) and upsert them.
   - local live key not in the UCC list: **soft-delete** it (`deleted_at`). Never hard delete.
4. Apply everything in **one DB transaction**, in dependency order (purities, wastages, collections, customers, mapped_customers). Save `last_version` only when the transaction commits. On error roll back, record `last_error`, retry on the next run.
5. **Race guard:** after applying, call `GET /masters/manifest` again. If `master_version` moved while you were syncing, run steps 3 and 4 once more (at most 3 rounds), then save the token that matches what you applied. If it still moves, keep the old `last_version` so the next run retries.
6. Upsert rule per row: if no local row has that ID and none has that `private_key`, insert. If a row has the same ID **and** the same `private_key`, update it (clear `deleted_at` if set). If the ID matches but the `private_key` differs, or the key matches but the ID differs, it is a **conflict**: skip the row, insert into `ucc_sync_conflict`, continue.
7. UCC's `is_active = 0` is just a field; copy it. It is not a deletion.

#### Webhook

The full requirements are in **"What we need from the Spider developer"** above (section 4). In short:

- [ ] `POST` endpoint that verifies the bearer key, the HMAC signature over the **exact request path** and the **raw body**, a 300 s timestamp window and a nonce that has not been used (stored for at least 10 minutes, only after the signature is valid).
- [ ] A `test: true` ping is answered `200` with no sync. A real notification **queues one sync** and is answered `200` within a second or two.
- [ ] The cron sync stays as the safety net: the webhook is a hint, never data and never guaranteed delivery.

#### Revalidate Masters (manual screen)

Restricted to a permitted role, rate-limited (e.g. one run per minute). Every run is saved in `ucc_sync_check_log` (who, when, JSON summary).

1. **Handshake** (`GET /health`). Show a result per check so a failure points at its cause:
   - UCC reachable
   - TLS certificate valid
   - key and signature accepted
   - clock difference within limit (compare `server_time` with local time)
   - API version compatible
2. **Compare** (`GET /masters/manifest`). Compute count and checksum locally per entity (live rows only) and compare. For any entity that differs, fetch `manifest?entity=<e>&keys=1` (paged) and diff against local rows by `private_key`.
3. **Report** per entity:
   - **Missing in Spider:** UCC has it, Spider doesn't.
   - **Changed:** same key, different `row_hash`.
   - **Extra in Spider:** Spider has a live row that UCC no longer lists.
   - **Conflicts:** open rows in `ucc_sync_conflict`.
   - **Version token:** local `last_version` against UCC's `master_version` (different means something changed).
4. **Local integrity:** mapped customers whose `customer_id`, `collection_id`, `wastage_id` or `purity_id` points at a missing or soft-deleted row.
5. **Label two kinds of "missing" separately:**
   - **Sync gap:** UCC has it and Spider doesn't. Fix: sync.
   - **Source gap:** incomplete in UCC itself (the manifest `unsyncable` count, e.g. a row with no `private_key`). Spider can't fix it; show that it must be corrected in UCC.

Revalidation **changes nothing by itself**. Afterwards offer:

- **Sync missing and changed rows:** fetch just those keys with `POST /masters/rows` and apply them through the **same sync service** as the cron job (same conflict and soft-delete rules), not a separate code path.
- **Soft-delete extra rows:** set `deleted_at` on rows UCC no longer has. Never hard delete.
- **Full re-snapshot:** confirmation required.
- **Download CSV** of the differences, for whoever fixes the UCC side.

#### Nightly drift check

A scheduled job runs the compare step without a UI. If it finds differences it runs a sync; if they persist, raise an alert and, if configured, re-snapshot.

### Phase 4: Migrate Spider's own masters (after sync works on real data)

1. [ ] **Back up** Spider's database.
2. [ ] Match Spider's customer rows to `ucc_customer` (by name, reviewed by hand). Match mapped customers on **name, customer, collection, purity, stone type and gold type together**; the name alone is not unique.
3. [ ] Produce a report of unmatched rows in both directions. Fix by hand (or ask UCC to correct its data) before going on.
4. [ ] Remap foreign keys in every Spider table that references the old master IDs to the UCC IDs.
5. [ ] Point existing code at the `ucc_*` tables (always filter `deleted_at IS NULL`), remove Spider's own master tables and edit screens, and drop the old tables only after a verification period.

## 3. UCC fields that are not sent

`hallmark_rate`, `flat_labour_rate`, `in_use`, purity test values, `prefix` and similar must not be added to the `ucc_*` tables. If you need one, ask for a contract change.

## 4. Shared rules (must match UCC exactly)

### Request signing (both directions)

```
string_to_sign = METHOD + "\n" + PATH_WITH_QUERY + "\n" + X-Timestamp + "\n" + X-Nonce + "\n" + sha256_hex(BODY)
X-Signature    = hex(HMAC_SHA256(hmac_secret, string_to_sign))
Headers: Authorization: Bearer <key>, X-Timestamp (unix seconds), X-Nonce (unique), X-Signature
Reject incoming if |now - X-Timestamp| > 300 s, or the nonce was already seen.
BODY is the raw body ("" for GET). PATH_WITH_QUERY keeps query keys in the order sent.
PATH is relative to the UCC app root ("/spider_api/v1/..."), not the hosting sub-folder. Webhook (UCC calls Spider): the signed PATH is the **exact path of the webhook URL UCC calls**, including any hosting sub-folder (for example `/webhooks/ucc/master_changed`, or `/spider/webhooks/ucc/master_changed` if Spider lives in a sub-folder). On Spider, verify with the path of the request URL exactly as received.
PHP: hash_hmac('sha256', $string, $secret); compare with hash_equals().
```

### `row_hash`

`sha256` hex of `json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)` where `$row` has only the exposed fields, in the order of the `properties` of each schema in `openapi.yaml`, with these types: IDs and `is_active` as integers, `purity`/`wastage`/`gold_multiplier` as fixed-decimal strings (2, 2, 3 places), dates as `Y-m-d H:i:s` or `null`. See `test_vectors.json` (`row_hash`).

When revalidating, **recompute the hash from the stored row**; don't trust the stored `row_hash`.

### Manifest checksum

`sha256` hex of the lines `{id}:{private_key}:{row_hash}\n` for all live rows (`deleted_at IS NULL`) of the entity ordered by `id` ascending. See `test_vectors.json` (`manifest_checksum`).

### Version token (`master_version`)

`sha256` hex of the lines `{entity}:{checksum}\n` for the entities in this fixed order: `customers, mapped_customers, wastages, purities, collections`. See `test_vectors.json` (`master_version`). The token is a hash: **compare for equality only**, never "greater than". Store the last fully applied token in `ucc_sync_state.last_version`. The webhook body carries the same token: `{"master_version":"<token>","sent_at":"..."}`.

## 5. Testing

- [ ] **Client:** all `signing` cases in `test_vectors.json` match; expired timestamp, bad key and TLS failure each give a clear error.
- [ ] **Hash:** `row_hash` and manifest checksum match `test_vectors.json`.
- [ ] **First sync:** a full snapshot loads every row; counts and checksums match the manifest; `last_version` is set.
- [ ] **Incremental:** an edit in UCC arrives; a row added in UCC arrives; a row removed in UCC is soft-deleted; a returning key clears `deleted_at`; an edit to a field UCC does not send (e.g. `hallmark_rate`) triggers no sync.
- [ ] **Race guard:** change a master in UCC while a sync is running; the next round picks it up and the saved token matches the applied data.
- [ ] **Conflict:** same ID with a different key (and the reverse) is skipped and logged; the rest still syncs.
- [ ] **Atomicity:** a failure mid-sync leaves `last_version` unchanged and no partial rows.
- [ ] **Revalidate Masters:** detects a deleted row (missing), an edited row (changed), a manually added row (extra) and an orphaned mapped customer; "Sync missing" and "Soft-delete extra" fix them; a UCC row with no key shows as a source gap.
- [ ] **Webhook:** invalid signature rejected; valid one queues a sync and returns fast; replay rejected.
- [ ] **Migration (Phase 4):** run on a copy of the database first; no unmatched rows remain and all foreign keys resolve.

## 6. Open items

| Item | Status |
|---|---|
| Spider MariaDB version | Needs 10.2.7+ for `JSON`. Check with `SELECT VERSION();` |
| `gold_multiplier` in `ucc_purity` | Included by default. If Spider never needs it, tell UCC so it is dropped from the contract |
| Framework | Assumed CodeIgniter 4 (spark command, filters). Adapt names if different |
| Who may use Revalidate Masters | Which role or permission |
| Hosting | Same server as UCC or separate; only affects UCC's IP allow-list |
