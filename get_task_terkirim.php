<?php
header('Content-Type: application/json');
require 'config.php';

// ─── Autoload LZString jika ada ─────────────
$vendor = __DIR__ . '/vendor/autoload.php';
if (file_exists($vendor)) require_once $vendor;

// ─── Input ──────────────────────────────────
$input       = json_decode(file_get_contents('php://input'), true);
$kodebooking = isset($input['kodebooking']) ? trim($input['kodebooking']) : '';

if ($kodebooking === '') {
    echo json_encode(['metadata' => ['code' => 400, 'message' => 'kodebooking wajib diisi']]);
    exit;
}

// ─── Signature ──────────────────────────────
date_default_timezone_set('UTC');
$tStamp    = (string) time();
$signature = base64_encode(
    hash_hmac('sha256', BPJS_CONS_ID . "&" . $tStamp, BPJS_SECRET_KEY, true)
);

$url = "https://apijkn.bpjs-kesehatan.go.id/antreanrs/antrean/getlisttask";

$headers = [
    "x-cons-id: "   . BPJS_CONS_ID,
    "x-timestamp: " . $tStamp,
    "x-signature: " . $signature,
    "user_key: "    . BPJS_USER_KEY,
    "Content-Type: application/json",
    "Accept: application/json",
];

// ─── cURL POST ──────────────────────────────
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS     => json_encode(['kodebooking' => $kodebooking]),
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT        => 30,
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

// ─── Parse & Decrypt ────────────────────────
$out = json_decode($response, true);

if (isset($out['response']) && is_string($out['response'])) {
    $plain = bpjsDecrypt($out['response'], $tStamp);

    if ($plain === null) {
        echo json_encode(['metadata' => ['code' => 500, 'message' => 'Gagal dekripsi response BPJS']]);
        exit;
    }

    $decoded = json_decode($plain, true);
    if (is_array($decoded)) {
        $out['response'] = $decoded;
    } else {
        echo json_encode(['metadata' => ['code' => 500, 'message' => 'Gagal parse JSON setelah dekripsi']]);
        exit;
    }
}

http_response_code($httpCode);
echo json_encode($out);

// ─── Decrypt Helper ─────────────────────────
function bpjsDecrypt(string $cipherB64, string $tStamp): ?string
{
    $keyRaw = BPJS_CONS_ID . BPJS_SECRET_KEY . $tStamp;
    $key    = hex2bin(hash('sha256', $keyRaw));
    $iv     = substr($key, 0, 16);

    $cipherRaw = base64_decode($cipherB64, true);
    if ($cipherRaw === false) return null;

    $plain = openssl_decrypt($cipherRaw, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    if ($plain === false) return null;

    if (class_exists('\LZCompressor\LZString')) {
        $dc = \LZCompressor\LZString::decompressFromEncodedURIComponent($plain);
        if (is_string($dc) && $dc !== '') return $dc;
    }

    return $plain;
}