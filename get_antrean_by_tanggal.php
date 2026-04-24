<?php
header('Content-Type: application/json');
require 'config.php';

$vendor = __DIR__ . '/vendor/autoload.php';
if (file_exists($vendor)) require_once $vendor;

// ─── PARAMETER ───────────────────────────────
$tanggal = isset($_GET['tanggal']) ? trim($_GET['tanggal']) : date('Y-m-d');
$force   = isset($_GET['force']) && $_GET['force'] === '1'; // paksa fetch BPJS

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    echo json_encode(['metadata' => ['code' => 400, 'message' => 'Format tanggal tidak valid']]);
    exit;
}

// ─── CACHE CONFIG ────────────────────────────
// Tanggal hari ini → cache 15 menit (data aktif berubah)
// Tanggal lampau  → cache 24 jam (data historis statis)
$today     = date('Y-m-d');
$isPast    = ($tanggal < $today);
$ttlMinute = $isPast ? 1440 : 15; // menit

// ─── CEK CACHE DB ────────────────────────────
if (!$force) {
    try {
        $cacheStmt = $pdo->prepare(
            "SELECT data_json, cached_at FROM cache_antrean
             WHERE tanggal = ?
             AND is_stale = 0
             AND cached_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
             ORDER BY id ASC"
        );
        $cacheStmt->execute([$tanggal, $ttlMinute]);
        $cachedRows = $cacheStmt->fetchAll();

        if (!empty($cachedRows)) {
            $list      = [];
            $cachedAt  = $cachedRows[0]['cached_at'];
            foreach ($cachedRows as $crow) {
                $item = json_decode($crow['data_json'], true);
                if (is_array($item)) $list[] = $item;
            }
            if (!empty($list)) {
                echo json_encode([
                    'metadata'   => ['code' => 200, 'message' => 'OK (cached)'],
                    'response'   => $list,
                    'from_cache' => true,
                    'cached_at'  => $cachedAt,
                ]);
                exit;
            }
        }
    } catch (PDOException $e) {
        error_log("[cache] read error: " . $e->getMessage());
        // Lanjut fetch BPJS jika cache gagal dibaca
    }
}

// ─── FETCH DARI BPJS ─────────────────────────
date_default_timezone_set('UTC');
$tStamp    = (string) time();
$signature = base64_encode(
    hash_hmac('sha256', BPJS_CONS_ID . "&" . $tStamp, BPJS_SECRET_KEY, true)
);

$url     = "https://apijkn.bpjs-kesehatan.go.id/antreanrs/antrean/pendaftaran/tanggal/" . urlencode($tanggal);
$headers = [
    "x-cons-id: "   . BPJS_CONS_ID,
    "x-timestamp: " . $tStamp,
    "x-signature: " . $signature,
    "user_key: "    . BPJS_USER_KEY,
    "Content-Type: application/json",
    "Accept: application/json",
];

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
    echo json_encode(['metadata' => ['code' => 500, 'message' => "cURL Error: $curlErr"]]); exit;
}
if ($httpCode !== 200) {
    echo json_encode(['metadata' => ['code' => $httpCode, 'message' => "HTTP Error $httpCode dari BPJS"]]); exit;
}

// ─── PARSE & DECRYPT ─────────────────────────
$data = json_decode($response, true);
$list = null;

if (isset($data['response']) && is_string($data['response'])) {
    $plain = bpjsDecrypt($data['response'], $tStamp);
    if ($plain === '' || $plain === false) {
        echo json_encode(['metadata' => ['code' => 500, 'message' => 'Gagal dekripsi response BPJS']]); exit;
    }
    $jsonText = null;
    if (class_exists('\LZCompressor\LZString')) {
        $dc = \LZCompressor\LZString::decompressFromEncodedURIComponent($plain);
        if (is_string($dc) && $dc !== '') $jsonText = $dc;
    }
    if ($jsonText === null) $jsonText = $plain;
    $decoded = json_decode($jsonText, true);
    if (is_array($decoded)) {
        $list = isset($decoded['list']) ? $decoded['list'] : $decoded;
    }
} elseif (isset($data['response'])) {
    $raw  = $data['response'];
    $list = is_array($raw) ? (isset($raw['list']) ? $raw['list'] : $raw) : null;
}

if (!is_array($list)) {
    echo json_encode(['metadata' => ['code' => 500, 'message' => 'Gagal parse response BPJS']]); exit;
}

// ─── SIMPAN KE CACHE ─────────────────────────
try {
    $nowWib = (new DateTime('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d H:i:s');

    // Hapus cache lama untuk tanggal ini dulu
    $del = $pdo->prepare("DELETE FROM cache_antrean WHERE tanggal = ?");
    $del->execute([$tanggal]);

    // Insert per booking
    $ins = $pdo->prepare(
        "INSERT INTO cache_antrean (tanggal, kodebooking, data_json, cached_at, is_stale)
         VALUES (?, ?, ?, ?, 0)
         ON DUPLICATE KEY UPDATE data_json = VALUES(data_json), cached_at = VALUES(cached_at), is_stale = 0"
    );
    foreach ($list as $item) {
        if (isset($item['kodebooking'])) {
            $ins->execute([$tanggal, $item['kodebooking'], json_encode($item), $nowWib]);
        }
    }
} catch (PDOException $e) {
    error_log("[cache] write error: " . $e->getMessage());
    // Tetap return data meski cache gagal disimpan
}

echo json_encode([
    'metadata'   => $data['metadata'] ?? ['code' => 200, 'message' => 'OK'],
    'response'   => $list,
    'from_cache' => false,
]);

// ─── DECRYPT HELPER ──────────────────────────
function bpjsDecrypt(string $cipherB64, string $tStamp): string {
    $keyHashBin = hex2bin(hash('sha256', BPJS_CONS_ID . BPJS_SECRET_KEY . $tStamp));
    $iv         = substr($keyHashBin, 0, 16);
    $cipherRaw  = base64_decode($cipherB64, true);
    if ($cipherRaw === false) return '';
    $plain = openssl_decrypt($cipherRaw, 'AES-256-CBC', $keyHashBin, OPENSSL_RAW_DATA, $iv);
    return $plain === false ? '' : $plain;
}