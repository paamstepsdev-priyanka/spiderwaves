<?php

namespace App\Services;

class UccHasher
{
    /**
     * Canonical schemas defined in openapi.yaml.
     * Order of properties matches the exact order required for canonical serialization.
     */
    public const SCHEMAS = [
        'customers' => [
            'customer_id',
            'private_key',
            'customer_name',
            'is_active',
            'created_at',
            'updated_at',
        ],
        'mapped_customers' => [
            'mapped_customer_id',
            'private_key',
            'mapped_customer_name',
            'customer_id',
            'collection_id',
            'wastage_id',
            'purity_id',
            'stone_type',
            'gold_type',
            'is_active',
            'created_at',
            'updated_at',
        ],
        'wastages' => [
            'wastage_id',
            'private_key',
            'wastage',
            'is_active',
        ],
        'purities' => [
            'purity_id',
            'private_key',
            'purity',
            'gold_multiplier',
            'is_active',
            'created_at',
            'updated_at',
        ],
        'collections' => [
            'collection_id',
            'private_key',
            'parent_id',
            'collection_name',
            'shortcode',
            'is_active',
            'created_at',
            'updated_at',
        ],
    ];

    /**
     * Normalize entity string to lowercase plural form (e.g. "Customer" -> "customers").
     */
    public static function normalizeEntityName(string $entity): string
    {
        $entity = strtolower(trim($entity));
        return match ($entity) {
            'customer', 'customers'               => 'customers',
            'mapped_customer', 'mapped_customers' => 'mapped_customers',
            'wastage', 'wastages'                 => 'wastages',
            'purity', 'purities'                  => 'purities',
            'collection', 'collections'           => 'collections',
            default                               => $entity,
        };
    }

    /**
     * Extract and normalize row values according to canonical types defined in openapi.yaml.
     */
    public static function formatCanonicalRow(string $entity, array $row): array
    {
        $entity = self::normalizeEntityName($entity);
        if (!isset(self::SCHEMAS[$entity])) {
            throw new \InvalidArgumentException("Unknown UCC entity schema: {$entity}");
        }

        $canonical = [];
        foreach (self::SCHEMAS[$entity] as $field) {
            // Check alias mappings for existing local database columns
            $sourceKey = $field;
            if (!array_key_exists($field, $row)) {
                if ($field === 'created_at' && array_key_exists('ucc_created_at', $row)) {
                    $sourceKey = 'ucc_created_at';
                } elseif ($field === 'updated_at' && array_key_exists('ucc_updated_at', $row)) {
                    $sourceKey = 'ucc_updated_at';
                } elseif ($field === 'customer_id' && array_key_exists('cust_id', $row)) {
                    $sourceKey = 'cust_id';
                } elseif ($field === 'customer_name' && array_key_exists('cust_name', $row)) {
                    $sourceKey = 'cust_name';
                } elseif ($field === 'mapped_customer_id' && array_key_exists('mapped_cust_id', $row)) {
                    $sourceKey = 'mapped_cust_id';
                } elseif ($field === 'mapped_name' && array_key_exists('mapped_cust_name', $row)) {
                    $sourceKey = 'mapped_cust_name';
                } elseif ($field === 'phone' && array_key_exists('mobile_no', $row)) {
                    $sourceKey = 'mobile_no';
                }
            }

            $val = $row[$sourceKey] ?? null;

            switch ($field) {
                case 'customer_id':
                case 'mapped_customer_id':
                case 'wastage_id':
                case 'purity_id':
                case 'collection_id':
                case 'parent_id':
                    $canonical[$field] = $val !== null ? (int)$val : null;
                    break;

                case 'is_active':
                    $canonical[$field] = (int)$val;
                    break;

                case 'wastage':
                case 'purity':
                    // Strict 2 decimal places as string
                    $canonical[$field] = number_format((float)$val, 2, '.', '');
                    break;

                case 'gold_multiplier':
                    // Strict 3 decimal places as string or null
                    $canonical[$field] = ($val !== null && $val !== '') ? number_format((float)$val, 3, '.', '') : null;
                    break;

                case 'created_at':
                case 'updated_at':
                    // Strict "Y-m-d H:i:s" or null
                    if ($val === null || $val === '') {
                        $canonical[$field] = null;
                    } else {
                        $time = is_numeric($val) ? (int)$val : strtotime((string)$val);
                        $canonical[$field] = ($time !== false && $time > 0) ? date('Y-m-d H:i:s', $time) : null;
                    }
                    break;

                default:
                    // String fields: preserve empty string "" (only null stays null)
                    $canonical[$field] = $val !== null ? (string)$val : null;
                    break;
            }
        }

        return $canonical;
    }

    /**
     * Serialize canonical row to JSON matching exact openapi/test vector formatting.
     */
    public static function toCanonicalJson(string $entity, array $row): string
    {
        $canonicalRow = self::formatCanonicalRow($entity, $row);
        return json_encode($canonicalRow, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Compute SHA256 row_hash of a canonical row.
     */
    public static function computeRowHash(string $entity, array $row): string
    {
        $json = self::toCanonicalJson($entity, $row);
        return hash('sha256', $json);
    }

    /**
     * Compute manifest checksum for an entity from active live rows.
     * Expects rows sorted by ID ascending, each containing {id, private_key, row_hash}.
     */
    public static function computeManifestChecksum(array $liveRows): string
    {
        $lines = '';
        foreach ($liveRows as $r) {
            $id = $r['id'] ?? $r['customer_id'] ?? $r['mapped_customer_id'] ?? $r['wastage_id'] ?? $r['purity_id'] ?? $r['collection_id'];
            $key = $r['private_key'];
            $hash = $r['row_hash'];
            $lines .= "{$id}:{$key}:{$hash}\n";
        }

        return hash('sha256', $lines);
    }

    /**
     * Compute master_version token from entity manifest checksums.
     * Rule: sha256 hex of "{entity}:{checksum}\n" for the five entities in this fixed order:
     * customers, mapped_customers, wastages, purities, collections
     */
    public static function computeMasterVersion(array $checksums): string
    {
        $lines = '';
        foreach (['customers', 'mapped_customers', 'wastages', 'purities', 'collections'] as $entity) {
            $cs = $checksums[$entity] ?? '';
            $lines .= "{$entity}:{$cs}\n";
        }
        return hash('sha256', $lines);
    }

    /**
     * Sign an API request string according to the official plan specifications:
     * string_to_sign = METHOD + "\n" + PATH_WITH_QUERY + "\n" + X-Timestamp + "\n" + X-Nonce + "\n" + sha256_hex(BODY)
     * X-Signature    = hex(HMAC_SHA256(hmac_secret, string_to_sign))
     */
    public static function signRequest(string $method, string $pathWithQuery, string $timestamp, string $nonce, string $body, string $secret): string
    {
        $bodySha256 = hash('sha256', $body);
        $stringToSign = strtoupper($method) . "\n" . $pathWithQuery . "\n" . $timestamp . "\n" . $nonce . "\n" . $bodySha256;
        return hash_hmac('sha256', $stringToSign, $secret);
    }

    /**
     * Verify an incoming signature against timestamp and nonce.
     */
    public static function verifySignature(
        string $method,
        string $pathWithQuery,
        string $timestamp,
        string $nonce,
        string $body,
        string $providedSignature,
        string $secret,
        int $toleranceSeconds = 300
    ): bool {
        // Timestamp window check
        if (abs(time() - (int)$timestamp) > $toleranceSeconds) {
            return false;
        }

        $expectedSignature = self::signRequest($method, $pathWithQuery, $timestamp, $nonce, $body, $secret);
        return hash_equals($expectedSignature, strtolower($providedSignature));
    }

    /**
     * Verify one webhook call as specified in webhook_receiver_example.php and plan.md.
     *
     * @param array    $headers        request headers (any letter case)
     * @param string   $method         HTTP method, expected "POST"
     * @param string   $pathWithQuery  path (plus "?query" if any) of the request URL exactly as received
     * @param string   $rawBody        raw request body
     * @param string   $expectedBearer the webhook bearer key issued to UCC
     * @param string   $secret         the webhook HMAC secret issued to UCC
     * @param callable $rememberNonce  function (string $nonce): bool. Store the nonce for at least 10 minutes
     *                                 and return true if it was NEW, false if it had been seen before.
     * @param int|null $now            current unix time (inject for tests)
     * @return array{0:int,1:array}    [HTTP status to answer with, JSON body to answer with]
     */
    public static function verifyWebhook(
        array $headers,
        string $method,
        string $pathWithQuery,
        string $rawBody,
        string $expectedBearer,
        string $secret,
        callable $rememberNonce,
        ?int $now = null
    ): array {
        $now = $now ?? time();
        $h = [];
        foreach ($headers as $k => $v) {
            $h[strtolower((string)$k)] = trim((string)$v);
        }

        if (strtoupper($method) !== 'POST') {
            return [405, ['status' => 'error', 'message' => 'POST only']];
        }

        // 1. Bearer key (constant-time compare)
        if (!hash_equals('Bearer ' . $expectedBearer, $h['authorization'] ?? '')) {
            return [401, ['status' => 'error', 'message' => 'Invalid credentials']];
        }

        // 2. Timestamp within 300 seconds, nonce present and <= 64 chars
        $ts = $h['x-timestamp'] ?? '';
        $nonce = $h['x-nonce'] ?? '';
        if ($ts === '' || !ctype_digit($ts) || $nonce === '' || strlen($nonce) > 64) {
            return [401, ['status' => 'error', 'message' => 'Malformed authentication headers']];
        }
        if (abs($now - (int)$ts) > 300) {
            return [401, ['status' => 'error', 'message' => 'Timestamp outside allowed window']];
        }

        // 3. Signature over the RAW body and the exact path
        $stringToSign = 'POST' . "\n" . $pathWithQuery . "\n" . $ts . "\n" . $nonce . "\n" . hash('sha256', $rawBody);
        $expected = hash_hmac('sha256', $stringToSign, $secret);
        if (!hash_equals($expected, strtolower($h['x-signature'] ?? ''))) {
            return [401, ['status' => 'error', 'message' => 'Invalid signature']];
        }

        // 4. Replay protection: only AFTER the signature is proven valid (so strangers cannot fill your nonce store)
        if (!$rememberNonce($nonce)) {
            return [401, ['status' => 'error', 'message' => 'Nonce already used']];
        }

        // 5. The body
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
}
