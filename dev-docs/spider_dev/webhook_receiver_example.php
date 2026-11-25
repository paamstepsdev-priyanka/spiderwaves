<?php
/**
 * REFERENCE webhook receiver for Spider: how to verify the "master_changed" call from UCC.
 *
 * - The function verify_ucc_webhook() has no framework dependency. Copy it into Spider
 *   (CodeIgniter, Laravel, plain PHP ...) and call it from your endpoint.
 * - Run this file on its own to try it:
 *       WEBHOOK_BEARER=whk_test WEBHOOK_SECRET=some_secret \
 *       php -S localhost:8091 webhook_receiver_example.php
 *   then point UCC at http://localhost:8091/webhooks/ucc/master_changed and press "Send test webhook"
 *   in UCC's API Manager.
 *
 * What UCC sends (see plan.md, "What we need from the Spider developer"):
 *   POST <your URL>
 *   Authorization: Bearer <webhook bearer key>
 *   X-Timestamp: <unix seconds>      X-Nonce: <random, unique per call>      X-Signature: <hex hmac>
 *   {"master_version":"<64 hex>","sent_at":"2026-11-23T20:15:00+05:30"}              a change notification
 *   {"master_version":"<64 hex>","sent_at":"...","test":true}                        a test ping: verify, answer 200, do NOT sync
 *
 *   string_to_sign = "POST" . "\n" . PATH_WITH_QUERY . "\n" . X-Timestamp . "\n" . X-Nonce . "\n" . sha256_hex(RAW_BODY)
 *   X-Signature    = hex(HMAC_SHA256(webhook secret, string_to_sign))
 *
 * PATH_WITH_QUERY is the EXACT path of the URL UCC calls, including any hosting sub-folder
 * (e.g. /webhooks/ucc/master_changed, or /spider/webhooks/ucc/master_changed). Use the path of the
 * request URL exactly as received. RAW_BODY is the raw bytes received (never a re-encoded JSON).
 */

/**
 * Verify one webhook call.
 *
 * @param array    $headers        request headers (any letter case)
 * @param string   $method         HTTP method, expected "POST"
 * @param string   $pathWithQuery  path (plus "?query" if any) of the request URL exactly as received
 * @param string   $rawBody        raw request body
 * @param string   $expectedBearer the webhook bearer key you issued to UCC
 * @param string   $secret         the webhook HMAC secret you issued to UCC
 * @param callable $rememberNonce  function (string $nonce): bool. Store the nonce for at least 10 minutes
 *                                 and return true if it was NEW, false if it had been seen before.
 * @param int|null $now            current unix time (inject for tests)
 * @return array{0:int,1:array}    [HTTP status to answer with, JSON body to answer with]
 *                                 On success the body also has 'master_version' and 'is_test'.
 */
function verify_ucc_webhook(array $headers, string $method, string $pathWithQuery, string $rawBody,
                            string $expectedBearer, string $secret, callable $rememberNonce, ?int $now = null): array
{
    $now = $now ?? time();
    $h = [];
    foreach ($headers as $k => $v) { $h[strtolower((string) $k)] = trim((string) $v); }

    if (strtoupper($method) !== 'POST') {
        return [405, ['status' => 'error', 'message' => 'POST only']];
    }

    // 1. bearer key (constant-time compare)
    if (!hash_equals('Bearer ' . $expectedBearer, $h['authorization'] ?? '')) {
        return [401, ['status' => 'error', 'message' => 'Invalid credentials']];
    }

    // 2. timestamp within 300 seconds, nonce present
    $ts = $h['x-timestamp'] ?? '';
    $nonce = $h['x-nonce'] ?? '';
    if ($ts === '' || !ctype_digit($ts) || $nonce === '' || strlen($nonce) > 64) {
        return [401, ['status' => 'error', 'message' => 'Malformed authentication headers']];
    }
    if (abs($now - (int) $ts) > 300) {
        return [401, ['status' => 'error', 'message' => 'Timestamp outside allowed window']];
    }

    // 3. signature over the RAW body and the exact path
    $stringToSign = 'POST' . "\n" . $pathWithQuery . "\n" . $ts . "\n" . $nonce . "\n" . hash('sha256', $rawBody);
    $expected = hash_hmac('sha256', $stringToSign, $secret);
    if (!hash_equals($expected, strtolower($h['x-signature'] ?? ''))) {
        return [401, ['status' => 'error', 'message' => 'Invalid signature']];
    }

    // 4. replay protection: only AFTER the signature is proven valid (so strangers cannot fill your nonce store)
    if (!$rememberNonce($nonce)) {
        return [401, ['status' => 'error', 'message' => 'Nonce already used']];
    }

    // 5. the body
    $json = json_decode($rawBody, true);
    if (!is_array($json) || !isset($json['master_version']) || !is_string($json['master_version']) || $json['master_version'] === '') {
        return [400, ['status' => 'error', 'message' => 'A JSON object with master_version is required']];
    }

    return [200, [
        'status'         => 'ok',
        'master_version' => $json['master_version'],
        'is_test'        => !empty($json['test']),
    ]];
}

// ---------------------------------------------------------------------------------------------
// Demo endpoint: only runs when this file is the entry script of PHP's built-in server.
// ---------------------------------------------------------------------------------------------
if (PHP_SAPI === 'cli-server' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    header('Content-Type: application/json');

    $store = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ucc_webhook_demo_nonces.json';
    $remember = function (string $nonce) use ($store): bool {
        $seen = is_file($store) ? (json_decode((string) file_get_contents($store), true) ?: []) : [];
        $cut = time() - 900;                                    // keep 15 minutes
        $seen = array_filter($seen, fn($t) => $t >= $cut);
        if (isset($seen[$nonce])) { return false; }
        $seen[$nonce] = time();
        file_put_contents($store, json_encode($seen), LOCK_EX);
        return true;
    };

    // $_SERVER['REQUEST_URI'] is the path exactly as received (strip nothing, add nothing).
    $path = $_SERVER['REQUEST_URI'];
    [$status, $body] = verify_ucc_webhook(
        function_exists('getallheaders') ? getallheaders() : [],
        $_SERVER['REQUEST_METHOD'],
        $path,
        file_get_contents('php://input'),
        (string) getenv('WEBHOOK_BEARER'),
        (string) getenv('WEBHOOK_SECRET'),
        $remember
    );

    if ($status === 200 && !$body['is_test']) {
        // Real notification: QUEUE the sync and answer immediately. Do not sync inside this request.
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ucc_webhook_demo_queue.log',
            date('c') . ' sync requested for ' . $body['master_version'] . "\n", FILE_APPEND);
    }

    http_response_code($status);
    echo json_encode($body);
}
