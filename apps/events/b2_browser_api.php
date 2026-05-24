<?php
/**
 * b2_browser_api.php
 * Proxy API for Backblaze B2 (S3-compatible).
 * Keeps credentials server-side. Supports three actions:
 *
 *   GET  ?action=list&event_id=123
 *        → lists all objects under concerts/{event_id}/memories/
 *        → returns { files: [ { key, url, size, last_modified } ] }
 *
 *   POST { action:'upload', event_id:123 }  + multipart file field 'file'
 *        → uploads to concerts/{event_id}/memories/{filename}
 *        → returns { success, url, key }
 *
 *   POST { action:'delete', key:'concerts/123/memories/photo.jpg' }
 *        → deletes that object
 *        → returns { success }
 *
 * Requires upload_helper.php in the same directory for the B2 constants
 * (B2_ENDPOINT, B2_BUCKET, B2_REGION, B2_KEY_ID, B2_APP_KEY, B2_PUBLIC_URL).
 */

require_once 'upload_helper.php';

header('Content-Type: application/json');

// ── CORS / method gate ────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];

if (!in_array($method, ['GET', 'POST'], true)) {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ── Shared signing helper ─────────────────────────────────────────────────────

/**
 * Build an AWS4-HMAC-SHA256 signed cURL request and execute it.
 *
 * @param string $httpMethod   GET | PUT | DELETE | HEAD
 * @param string $path         URL path (after endpoint), e.g. "/bucket/key"
 * @param array  $extraHeaders Additional HTTP headers (name => value)
 * @param string $body         Request body (empty string for no body)
 * @param array  $curlOpts     Extra curl_setopt_array options
 * @return array ['code' => int, 'body' => string, 'error' => string]
 */
function b2SignedRequest(string $httpMethod, string $path, array $extraHeaders = [], string $body = '', array $curlOpts = []): array
{
    $endpoint = rtrim(B2_ENDPOINT, '/');
    $bucket   = B2_BUCKET;
    $region   = B2_REGION;
    $keyId    = B2_KEY_ID;
    $appKey   = B2_APP_KEY;
    $host     = parse_url($endpoint, PHP_URL_HOST);

    $dateTime    = gmdate('Ymd\THis\Z');
    $date        = gmdate('Ymd');
    $payloadHash = hash('sha256', $body);

    // Build canonical headers (must be sorted)
    $allHeaders = array_merge([
        'host'                 => $host,
        'x-amz-content-sha256' => $payloadHash,
        'x-amz-date'           => $dateTime,
    ], array_change_key_case($extraHeaders, CASE_LOWER));
    ksort($allHeaders);

    $canonicalHeaderStr = '';
    $signedHeadersList  = [];
    foreach ($allHeaders as $k => $v) {
        $canonicalHeaderStr .= "$k:$v\n";
        $signedHeadersList[] = $k;
    }
    $signedHeaders = implode(';', $signedHeadersList);

    $canonicalRequest = implode("\n", [
        $httpMethod,
        $path,
        '',  // no query string
        $canonicalHeaderStr,
        $signedHeaders,
        $payloadHash,
    ]);

    $credentialScope = "$date/$region/s3/aws4_request";
    $stringToSign    = implode("\n", [
        'AWS4-HMAC-SHA256',
        $dateTime,
        $credentialScope,
        hash('sha256', $canonicalRequest),
    ]);

    $signingKey = hash_hmac('sha256', 'aws4_request',
                    hash_hmac('sha256', 's3',
                      hash_hmac('sha256', $region,
                        hash_hmac('sha256', $date, 'AWS4' . $appKey, true),
                      true),
                    true),
                  true);

    $signature  = hash_hmac('sha256', $stringToSign, $signingKey);
    $authHeader = "AWS4-HMAC-SHA256 Credential=$keyId/$credentialScope, SignedHeaders=$signedHeaders, Signature=$signature";

    $curlHeaders = ["Authorization: $authHeader", "x-amz-content-sha256: $payloadHash", "x-amz-date: $dateTime"];
    foreach ($extraHeaders as $k => $v) {
        $curlHeaders[] = "$k: $v";
    }

    $ch = curl_init($endpoint . $path);
    // Use individual curl_setopt() calls instead of curl_setopt_array()
    // to avoid ValueError when array_merge produces unexpected keys.
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST,  $httpMethod);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER,     $curlHeaders);
    if ($httpMethod === 'PUT' && $body !== '') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    // Apply any extra curlOpts individually
    foreach ($curlOpts as $opt => $val) {
        curl_setopt($ch, $opt, $val);
    }

    $responseBody = curl_exec($ch);
    $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError    = curl_error($ch);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => $responseBody, 'error' => $curlError];
}

/**
 * Build the public URL for an object key.
 * Uses B2_PUBLIC_URL if defined in upload_helper.php, otherwise falls back
 * to the endpoint + bucket style URL.
 */
function publicUrl(string $key): string
{
    if (defined('B2_PUBLIC_URL') && B2_PUBLIC_URL) {
        return rtrim(B2_PUBLIC_URL, '/') . '/' . ltrim($key, '/');
    }
    return rtrim(B2_ENDPOINT, '/') . '/' . B2_BUCKET . '/' . ltrim($key, '/');
}

// ── Validate event_id helper ──────────────────────────────────────────────────
function validEventId(mixed $val): int
{
    $id = (int)$val;
    return $id > 0 ? $id : 0;
}

// ═════════════════════════════════════════════════════════════════════════════
// GET — list files
// ═════════════════════════════════════════════════════════════════════════════
if ($method === 'GET') {
    $action  = $_GET['action'] ?? '';
    $eventId = validEventId($_GET['event_id'] ?? 0);

    if ($action !== 'list' || !$eventId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing or invalid action/event_id']);
        exit;
    }

    // S3 list-objects-v2: GET /{bucket}?list-type=2&prefix=...
    $prefix   = 'concerts/' . $eventId . '/memories/';
    $bucket   = B2_BUCKET;
    $endpoint = rtrim(B2_ENDPOINT, '/');
    $host     = parse_url($endpoint, PHP_URL_HOST);
    $region   = B2_REGION;
    $keyId    = B2_KEY_ID;
    $appKey   = B2_APP_KEY;

    $dateTime    = gmdate('Ymd\THis\Z');
    $date        = gmdate('Ymd');
    $payloadHash = hash('sha256', '');

    $queryString = 'list-type=2&prefix=' . rawurlencode($prefix);

    $canonicalHeaders = "host:$host\nx-amz-content-sha256:$payloadHash\nx-amz-date:$dateTime\n";
    $signedHeaders    = 'host;x-amz-content-sha256;x-amz-date';

    $canonicalRequest = implode("\n", [
        'GET',
        "/$bucket/",
        $queryString,
        $canonicalHeaders,
        $signedHeaders,
        $payloadHash,
    ]);

    $credentialScope = "$date/$region/s3/aws4_request";
    $stringToSign    = "AWS4-HMAC-SHA256\n$dateTime\n$credentialScope\n" . hash('sha256', $canonicalRequest);

    $signingKey = hash_hmac('sha256', 'aws4_request',
                    hash_hmac('sha256', 's3',
                      hash_hmac('sha256', $region,
                        hash_hmac('sha256', $date, 'AWS4' . $appKey, true),
                      true),
                    true),
                  true);

    $signature  = hash_hmac('sha256', $stringToSign, $signingKey);
    $authHeader = "AWS4-HMAC-SHA256 Credential=$keyId/$credentialScope, SignedHeaders=$signedHeaders, Signature=$signature";

    $ch = curl_init("$endpoint/$bucket/?$queryString");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            "Authorization: $authHeader",
            "x-amz-content-sha256: $payloadHash",
            "x-amz-date: $dateTime",
        ],
    ]);
    $xml      = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        http_response_code(502);
        echo json_encode(['error' => 'B2 connection error: ' . $curlErr]);
        exit;
    }

    if ($httpCode !== 200) {
        http_response_code(502);
        echo json_encode(['error' => "B2 returned HTTP $httpCode", 'detail' => $xml]);
        exit;
    }

    // Parse XML response
    $files = [];
    try {
        $dom = new SimpleXMLElement($xml);
        // Handle namespace if present
        $contents = $dom->Contents ?? [];

        foreach ($contents as $item) {
            $key  = (string)$item->Key;
            $size = (int)$item->Size;
            $mod  = (string)$item->LastModified;

            // Skip "folder" placeholder objects (0 bytes, ends with /)
            if ($size === 0 && str_ends_with($key, '/')) continue;

            $files[] = [
                'key'           => $key,
                'url'           => publicUrl($key),
                'size'          => $size,
                'last_modified' => $mod,
                'filename'      => basename($key),
            ];
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to parse B2 response: ' . $e->getMessage()]);
        exit;
    }

    echo json_encode(['files' => $files, 'prefix' => $prefix]);
    exit;
}

// ═════════════════════════════════════════════════════════════════════════════
// POST — upload or delete
// ═════════════════════════════════════════════════════════════════════════════
if ($method === 'POST') {

    // Detect multipart upload vs JSON body
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $isMultipart = str_contains($contentType, 'multipart/form-data');

    if ($isMultipart) {
        // ── UPLOAD ────────────────────────────────────────────────────────────
        $action  = $_POST['action'] ?? '';
        $eventId = validEventId($_POST['event_id'] ?? 0);

        if ($action !== 'upload' || !$eventId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing action or event_id']);
            exit;
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $errCode = $_FILES['file']['error'] ?? -1;
            http_response_code(400);
            echo json_encode(['error' => "File upload error (code $errCode)"]);
            exit;
        }

        $file     = $_FILES['file'];
        $origName = basename($file['name']);
        $mimeType = $file['type'] ?: mime_content_type($file['tmp_name']);
        $tmpPath  = $file['tmp_name'];

        // Sanitise filename — keep extension, strip path characters
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
        $safeName = $safeName . '_' . time() . '.' . $ext;

        // Build key: concerts/{eventId}/memories/{safeName}
        $key      = 'concerts/' . $eventId . '/memories/' . $safeName;
        $bucket   = B2_BUCKET;
        $fileSize = filesize($tmpPath);

        // PUT object — sign manually and stream via CURLOPT_INFILE.
        // Avoids b2SignedRequest() which conflicts CURLOPT_PUT + CURLOPT_CUSTOMREQUEST.
        $endpoint = rtrim(B2_ENDPOINT, '/');
        $region   = B2_REGION;
        $keyId    = B2_KEY_ID;
        $appKey   = B2_APP_KEY;
        $host     = parse_url($endpoint, PHP_URL_HOST);

        $fileContent = file_get_contents($tmpPath);
        $dateTime    = gmdate('Ymd\THis\Z');
        $date        = gmdate('Ymd');
        $payloadHash = hash('sha256', $fileContent);

        $canonicalHeaders = implode("\n", [
            "content-type:$mimeType",
            "host:$host",
            "x-amz-content-sha256:$payloadHash",
            "x-amz-date:$dateTime",
        ]) . "\n";
        $signedHeaders = 'content-type;host;x-amz-content-sha256;x-amz-date';

        $canonicalRequest = implode("\n", [
            'PUT',
            "/$bucket/$key",
            '',
            $canonicalHeaders,
            $signedHeaders,
            $payloadHash,
        ]);

        $credentialScope = "$date/$region/s3/aws4_request";
        $stringToSign    = implode("\n", [
            'AWS4-HMAC-SHA256',
            $dateTime,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        $signingKey = hash_hmac('sha256', 'aws4_request',
                        hash_hmac('sha256', 's3',
                          hash_hmac('sha256', $region,
                            hash_hmac('sha256', $date, 'AWS4' . $appKey, true),
                          true),
                        true),
                      true);

        $signature  = hash_hmac('sha256', $stringToSign, $signingKey);
        $authHeader = "AWS4-HMAC-SHA256 Credential=$keyId/$credentialScope, SignedHeaders=$signedHeaders, Signature=$signature";

        $stream = fopen($tmpPath, 'rb');
        $ch = curl_init("$endpoint/$bucket/$key");
        curl_setopt_array($ch, [
            CURLOPT_PUT            => true,
            CURLOPT_INFILE         => $stream,
            CURLOPT_INFILESIZE     => $fileSize,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                "Authorization: $authHeader",
                "Content-Type: $mimeType",
                "x-amz-content-sha256: $payloadHash",
                "x-amz-date: $dateTime",
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);
        fclose($stream);

        if ($curlErr) {
            http_response_code(502);
            echo json_encode(['error' => 'Upload failed: ' . $curlErr]);
            exit;
        }

        if ($httpCode !== 200) {
            http_response_code(502);
            echo json_encode(['error' => "B2 upload returned HTTP $httpCode", 'detail' => $response]);
            exit;
        }

        echo json_encode([
            'success'  => true,
            'key'      => $key,
            'url'      => publicUrl($key),
            'filename' => $safeName,
        ]);
        exit;

    } else {
        // ── DELETE (JSON body) ────────────────────────────────────────────────
        $body   = json_decode(file_get_contents('php://input'), true);
        $action = $body['action'] ?? '';
        $key    = trim($body['key'] ?? '');

        if ($action !== 'delete' || !$key) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing action or key']);
            exit;
        }

        // Safety: only allow deleting within /Memories/ folders
        if (!preg_match('#^concerts/\d+/memories/.+$#', $key)) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden key path']);
            exit;
        }

        $bucket = B2_BUCKET;

        // --- DEBUG: log values before signing to catch missing env vars ---
        error_log('[B2 DELETE] bucket: ' . var_export($bucket, true));
        error_log('[B2 DELETE] key: '    . var_export($key,    true));
        error_log('[B2 DELETE] endpoint: ' . var_export(B2_ENDPOINT, true));
        error_log('[B2 DELETE] region: '   . var_export(B2_REGION,   true));
        error_log('[B2 DELETE] keyId: '    . var_export(B2_KEY_ID,   true));
        error_log('[B2 DELETE] path will be: /' . $bucket . '/' . $key);
        // --- END DEBUG ---

        $result = b2SignedRequest('DELETE', "/$bucket/$key");

        if ($result['error']) {
            http_response_code(502);
            echo json_encode(['error' => 'Delete failed: ' . $result['error']]);
            exit;
        }

        if (!in_array($result['code'], [200, 204], true)) {
            http_response_code(502);
            echo json_encode(['error' => "B2 delete returned HTTP {$result['code']}", 'detail' => $result['body']]);
            exit;
        }

        echo json_encode(['success' => true, 'key' => $key]);
        exit;
    }
}