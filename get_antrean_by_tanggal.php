<?php
header('Content-Type: application/json');
require 'config.php';

// ─── Autoload LZString jika ada ─────────────
$vendor = __DIR__ . '/vendor/autoload.php';
if (file_exists($vendor)) require_once $vendor;

// ─── Parameter ──────────────────────────────
$tanggal = isset($_GET['tanggal']) ? trim($_GET['tanggal']) : date('Y-m-d');

// Validasi format tanggal YYYY-MM-DD
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    echo json_encode(['metadata' => ['code' => 400, 'message' => 'Format tanggal tidak valid']]);
    exit;
}

// ─── Signature ──────────────────────────────
date_default_timezone_set('UTC');
$tStamp    = (string) time();
$signature = base64_encode(
    hash_hmac('sha256', BPJS_CONS_ID . "&" . $tStamp, BPJS_SECRET_KEY, true)
);

// ─── Endpoint ───────────────────────────────
$url = "https://apijkn.bpjs-kesehatan.go.id/antreanrs/antrean/pendaftaran/tanggal/" . urlencode($tanggal);

$headers = [
    "x-cons-id: "   . BPJS_CONS_ID,
    "x-timestamp: " . $tStamp,
    "x-signature: " . $signature,
    "user_key: "    . BPJS_USER_KEY,
    "Content-Type: application/json",
    "Accept: application/json",
];

// ─── cURL ────────────────────────────────────
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);
$response = curl_exec($ch);
$curlErr  = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlErr) {
    echo json_encode(['metadata' => ['code' => 500, 'message' => "cURL Error: $curlErr"]]);
    exit;
}
if ($httpCode !== 200) {
    echo json_encode(['metadata' => ['code' => $httpCode, 'message' => "HTTP Error $httpCode dari BPJS"]]);
    exit;
}

// ─── Parse & Decrypt ────────────────────────
$data = json_decode($response, true);

if (isset($data['response']) && is_string($data['response'])) {
    $cipherB64 = $data['response'];
    $keyString  = BPJS_CONS_ID . BPJS_SECRET_KEY . $tStamp;

    $plain = bpjsDecrypt($cipherB64, $keyString);

    if ($plain === '' || $plain === false) {
        echo json_encode([
            'metadata' => ['code' => 500, 'message' => 'Gagal dekripsi response BPJS'],
            'debug'    => ['tStamp_used' => $tStamp],
        ]);
        exit;
    }

    // Decompress LZString jika library tersedia
    $jsonText = null;
    if (class_exists('\LZCompressor\LZString')) {
        $dc = \LZCompressor\LZString::decompressFromEncodedURIComponent($plain);
        if (is_string($dc) && $dc !== '') {
            $jsonText = $dc;
        }
    }

    // Fallback: langsung parse plain
    if ($jsonText === null) {
        $jsonText = $plain;
    }

    $decoded = json_decode($jsonText, true);
    if (is_array($decoded)) {
        echo json_encode([
            'metadata' => $data['metadata'] ?? ['code' => 200, 'message' => 'OK'],
            'response' => $decoded,
        ]);
        exit;
    }

    echo json_encode([
        'metadata' => ['code' => 500, 'message' => 'Gagal parse JSON setelah decrypt'],
        'debug'    => [
            'plain_preview' => substr($plain, 0, 120) . '...',
            'len_plain'     => strlen($plain),
            'has_lzstring'  => class_exists('\LZCompressor\LZString') ? 'yes' : 'no',
        ],
    ]);
    exit;
}

// Tidak terenkripsi → langsung return
echo $response;

// ─── Decrypt AES-256-CBC ─────────────────────
function bpjsDecrypt(string $cipherB64, string $keyString): string
{
    $keyHashBin = hex2bin(hash('sha256', $keyString)); // 32 byte
    $iv         = substr($keyHashBin, 0, 16);           // 16 byte
    $cipherRaw  = base64_decode($cipherB64, true);
    if ($cipherRaw === false) return '';
    $plain = openssl_decrypt($cipherRaw, 'AES-256-CBC', $keyHashBin, OPENSSL_RAW_DATA, $iv);
    return $plain === false ? '' : $plain;
}