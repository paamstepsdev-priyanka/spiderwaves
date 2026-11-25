<?php

namespace App\Services;

use App\Exceptions\UccException;
use App\Exceptions\UccNotConfiguredException;
use App\Exceptions\UccAuthException;
use App\Exceptions\UccCursorExpiredException;
use App\Exceptions\UccRateLimitException;
use App\Exceptions\UccClientException;

class UccClient
{
    protected ?string $baseUrl;
    protected ?string $bearerToken;
    protected ?string $hmacSecret;
    protected int $connectTimeout;
    protected int $timeout;
    protected bool $verifyTls;

    public function __construct(
        ?string $baseUrl = null,
        ?string $bearerToken = null,
        ?string $hmacSecret = null,
        int $connectTimeout = 10,
        int $timeout = 30,
        bool $verifyTls = true
    ) {
        $this->baseUrl        = $baseUrl ?? (env('UCC_API_BASE_URL') ?: (getenv('UCC_API_BASE_URL') ?: (env('UCC_BASE_URL') ?: (getenv('UCC_BASE_URL') ?: 'https://ucc.veeragold.in/spider_api/v1'))));
        $this->bearerToken    = $bearerToken ?? (env('UCC_API_BEARER_KEY') ?: (getenv('UCC_API_BEARER_KEY') ?: (env('UCC_BEARER_TOKEN') ?: (getenv('UCC_BEARER_TOKEN') ?: ''))));
        $this->hmacSecret     = $hmacSecret ?? (env('UCC_API_HMAC_SECRET') ?: (getenv('UCC_API_HMAC_SECRET') ?: (env('UCC_HMAC_SECRET') ?: (getenv('UCC_HMAC_SECRET') ?: ''))));
        $this->connectTimeout = (int)($connectTimeout ?: (env('UCC_CONNECT_TIMEOUT', 10)));
        $this->timeout        = (int)($timeout ?: (env('UCC_TIMEOUT', 30)));
        $this->verifyTls      = (bool)($verifyTls ?? (env('UCC_VERIFY_TLS', true)));

        // Normalize base URL (trim trailing slashes)
        if (!empty($this->baseUrl)) {
            $this->baseUrl = rtrim($this->baseUrl, '/');
        }
    }

    /**
     * Verify whether all necessary UCC credentials are configured in .env.
     */
    public function isConfigured(): bool
    {
        return !empty($this->baseUrl) && !empty($this->bearerToken) && !empty($this->hmacSecret);
    }

    /**
     * Handshake endpoint: GET /health
     * Returns server_time, api_version, master_version.
     */
    public function getHealth(): array
    {
        return $this->request('GET', '/health');
    }

    /**
     * Current master version: GET /masters/version
     */
    public function getMasterVersion(): string
    {
        $res = $this->request('GET', '/masters/version');
        return (string)($res['data']['master_version'] ?? '');
    }

    /**
     * Snapshot pagination: GET /masters/snapshot
     */
    public function getSnapshot(string $entity, int $afterId = 0, int $limit = 200): array
    {
        $normalizedEntity = UccHasher::normalizeEntityName($entity);
        return $this->request('GET', '/masters/snapshot', [
            'entity'   => $normalizedEntity,
            'after_id' => $afterId,
            'limit'    => $limit,
        ]);
    }

    /**
     * Manifest comparison: GET /masters/manifest
     */
    public function getManifest(?string $entity = null, bool $keys = false, int $afterId = 0, int $limit = 200): array
    {
        $params = [];
        if ($entity !== null) {
            $params['entity'] = UccHasher::normalizeEntityName($entity);
        }
        if ($keys) {
            $params['keys'] = 1;
            if ($afterId > 0) {
                $params['after_id'] = $afterId;
            }
            if ($limit > 0) {
                $params['limit'] = $limit;
            }
        }

        return $this->request('GET', '/masters/manifest', $params);
    }

    /**
     * Batch fetch rows by private keys: POST /masters/rows
     */
    public function getRows(string $entity, array $privateKeys): array
    {
        $normalizedEntity = UccHasher::normalizeEntityName($entity);
        return $this->request('POST', '/masters/rows', [], [
            'entity'       => $normalizedEntity,
            'private_keys' => array_values($privateKeys),
        ]);
    }

    /**
     * Core signed HTTP request execution with retries and status handling.
     */
    public function request(string $method, string $subPath, array $queryParams = [], ?array $body = null): array
    {
        if (!$this->isConfigured()) {
            throw new UccNotConfiguredException(
                "UCC API connection credentials are not configured. Please supply UCC_API_BASE_URL, UCC_BEARER_TOKEN, and UCC_HMAC_SECRET in .env."
            );
        }

        $method = strtoupper($method);

        // Build exact path with query string (preserving query parameter ordering)
        $pathWithQuery = $this->buildPathWithQuery($subPath, $queryParams);
        
        $urlParts = parse_url($this->baseUrl);
        $scheme   = $urlParts['scheme'] ?? 'https';
        $host     = $urlParts['host'] ?? '';
        $port     = isset($urlParts['port']) ? ':' . $urlParts['port'] : '';
        $fullUrl  = "{$scheme}://{$host}{$port}" . (str_starts_with($pathWithQuery, '/') ? $pathWithQuery : '/' . $pathWithQuery);

        $bodyString = ($body !== null && $method !== 'GET') 
            ? json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) 
            : '';

        $timestamp = (string)time();
        $nonce = bin2hex(random_bytes(16));
        $signature = UccHasher::signRequest($method, $pathWithQuery, $timestamp, $nonce, $bodyString, $this->hmacSecret);

        $headers = [
            'Authorization: Bearer ' . $this->bearerToken,
            'X-Timestamp: ' . $timestamp,
            'X-Nonce: ' . $nonce,
            'X-Signature: ' . $signature,
            'Accept: application/json',
        ];

        if ($method !== 'GET' && !empty($bodyString)) {
            $headers[] = 'Content-Type: application/json';
        }

        $maxRetries = 3;
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            $attempt++;
            try {
                return $this->executeCurl($method, $fullUrl, $headers, $bodyString);
            } catch (UccAuthException | UccCursorExpiredException | UccRateLimitException $e) {
                // Do not retry client auth failures or cursor expiration
                throw $e;
            } catch (\Throwable $e) {
                $lastException = $e;
                if ($attempt < $maxRetries) {
                    usleep(300000 * $attempt); // Exponential backoff (300ms, 600ms)
                }
            }
        }

        throw $lastException ?? new UccClientException("Request to UCC API failed after {$maxRetries} attempts.");
    }

    /**
     * Build relative path with query parameters preserving exact order.
     */
    protected function buildPathWithQuery(string $subPath, array $queryParams): string
    {
        // Extract URI path prefix from baseUrl if present (e.g., /spider_api/v1)
        $urlParts = parse_url($this->baseUrl);
        $basePath = rtrim($urlParts['path'] ?? '', '/');

        $fullPath = $basePath . '/' . ltrim($subPath, '/');

        if (!empty($queryParams)) {
            $pairs = [];
            foreach ($queryParams as $k => $v) {
                if ($v !== null) {
                    $pairs[] = urlencode((string)$k) . '=' . urlencode((string)$v);
                }
            }
            if (!empty($pairs)) {
                $fullPath .= '?' . implode('&', $pairs);
            }
        }

        return $fullPath;
    }

    /**
     * Low-level cURL call.
     */
    protected function executeCurl(string $method, string $url, array $headers, string $bodyString): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifyTls);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        // Ensure robust CA bundle resolution on Windows/WAMP environments
        if ($this->verifyTls) {
            $caBundle = env('UCC_CA_BUNDLE') ?: (ini_get('curl.cainfo') ?: (ini_get('openssl.cafile') ?: null));
            if (empty($caBundle) || !file_exists($caBundle)) {
                $localCert = WRITEPATH . 'certs/cacert.pem';
                if (file_exists($localCert)) {
                    $caBundle = $localCert;
                }
            }
            if (!empty($caBundle) && file_exists($caBundle)) {
                curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
            }
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyString);
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if (!empty($bodyString)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyString);
            }
        }

        $rawResponse = curl_exec($ch);
        $httpCode    = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError   = curl_error($ch);
        $curlErrno   = curl_errno($ch);
        curl_close($ch);

        if ($curlErrno !== 0) {
            throw new UccClientException("UCC API cURL Error [{$curlErrno}]: {$curlError}");
        }

        $data = json_decode((string)$rawResponse, true);

        if ($httpCode === 401 || $httpCode === 403) {
            $msg = $data['message'] ?? "Unauthorized / Forbidden ($httpCode)";
            throw new UccAuthException("UCC Authentication Error ($httpCode): " . $msg);
        }

        if ($httpCode === 410) {
            $msg = $data['message'] ?? 'Cursor older than retained change log.';
            throw new UccCursorExpiredException("UCC Change Log Expired (410 Gone): " . $msg);
        }

        if ($httpCode === 429) {
            $msg = $data['message'] ?? 'Too many requests.';
            throw new UccRateLimitException("UCC API Rate Limit Exceeded (429): " . $msg);
        }

        if ($httpCode >= 500) {
            $msg = !empty($data['message']) ? $data['message'] : (!empty($rawResponse) ? trim(substr(strip_tags((string)$rawResponse), 0, 300)) : 'Internal Server Error');
            throw new UccClientException("UCC Server Error ($httpCode): " . $msg);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $msg = $data['message'] ?? "HTTP Status $httpCode";
            throw new UccClientException("UCC API Error ($httpCode): " . $msg);
        }

        if (!is_array($data)) {
            throw new UccClientException("UCC API returned non-JSON payload: " . substr((string)$rawResponse, 0, 150));
        }

        return $data;
    }
}

