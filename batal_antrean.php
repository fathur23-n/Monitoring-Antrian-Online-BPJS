<?php
header('Content-Type: application/json');
require 'config.php';
session_start();

$input        = json_decode(file_get_contents('php://input'), true);
$kodebooking  = trim($input['kodebooking']  ?? '');
$keterangan   = trim($input['keterangan']   ?? 'Batal tidak hadir');
$norekammedis = trim($input['norekammedis'] ?? '');

if (!$kodebooking) {
    echo json_encode(['metadata' => ['code' => 400, 'message' => 'Data tidak lengkap']]);
    exit;
}
if (!$keterangan) $keterangan = 'Batal tidak hadir';

// ─── CEK referensi_mobilejkn_bpjs_batal ──────
try {
    $cek = $pdo->prepare(
        "SELECT nobooking, statuskirim FROM referensi_mobilejkn_bpjs_batal
         WHERE nobooking = ? LIMIT 1"
    );
    $cek->execute([$kodebooking]);
    $existing = $cek->fetch();

    if ($existing && $existing['statuskirim'] === 'Sudah') {
        echo json_encode(['metadata' => ['code' => 409, 'message' => 'Antrean ini sudah dibatalkan sebelumnya']]);
        exit;
    }
} catch (PDOException $e) {
    error_log("[batal] cek error: " . $e->getMessage());
    echo json_encode(['metadata' => ['code' => 500, 'message' => 'Database error: ' . $e->getMessage()]]);
    exit;
}

// ─── AMBIL DATA DARI referensi_mobilejkn_bpjs ─
$nomorreferensi = '0';
$no_rawat_batal = '';
$norm           = $norekammedis; // fallback dari frontend

try {
    $ref = $pdo->prepare(
        "SELECT no_rawat, nomorreferensi, norm
         FROM referensi_mobilejkn_bpjs
         WHERE nobooking = ? LIMIT 1"
    );
    $ref->execute([$kodebooking]);
    $refRow = $ref->fetch();
    if ($refRow) {
        if (!empty($refRow['nomorreferensi'])) $nomorreferensi = $refRow['nomorreferensi'];
        if (!empty($refRow['no_rawat']))       $no_rawat_batal = $refRow['no_rawat'];
        if (!empty($refRow['norm']))           $norm           = $refRow['norm'];
    }
} catch (PDOException $e) { /* pakai default */ }

// ─── SIGNATURE BPJS ─────────────────────────
date_default_timezone_set('UTC');
$tStamp    = (string) time();
$signature = base64_encode(
    hash_hmac('sha256', BPJS_CONS_ID . "&" . $tStamp, BPJS_SECRET_KEY, true)
);

$headers = [
    "x-cons-id: "   . BPJS_CONS_ID,
    "x-timestamp: " . $tStamp,
    "x-signature: " . $signature,
    "user_key: "    . BPJS_USER_KEY,
    "Content-Type: application/json",
];

// ─── CURL KE BPJS ───────────────────────────
$ch = curl_init("https://apijkn.bpjs-kesehatan.go.id/antreanrs/antrean/batal");
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS     => json_encode([
        'kodebooking' => $kodebooking,
        'keterangan'  => $keterangan,
    ]),
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

$result = json_decode($response, true);
$code   = (int)($result['metadata']['code'] ?? $httpCode);
$msg    = $result['metadata']['message']    ?? 'Gagal membatalkan antrean';

if ($code !== 200) {
    echo json_encode(['metadata' => ['code' => $code, 'message' => $msg]]);
    exit;
}

// ─── SUKSES: SIMPAN / UPDATE DB ─────────────
$now      = (new DateTime('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d H:i:s');
$tglBatal = $now;

try {
    if ($existing) {
        // Update statuskirim jadi 'Sudah'
        $upd = $pdo->prepare(
            "UPDATE referensi_mobilejkn_bpjs_batal
             SET statuskirim = 'Sudah', keterangan = ?, tanggalbatal = ?
             WHERE nobooking = ?"
        );
        $upd->execute([$keterangan, $tglBatal, $kodebooking]);
    } else {
        // Insert baru — statuskirim enum('Sudah','Belum')
        $ins = $pdo->prepare(
            "INSERT INTO referensi_mobilejkn_bpjs_batal
             (no_rkm_medis, no_rawat_batal, nomorreferensi, tanggalbatal, keterangan, statuskirim, nobooking)
             VALUES (?, ?, ?, ?, ?, 'Sudah', ?)"
        );
        $ins->execute([
            $norm,
            $no_rawat_batal ?: '-',
            $nomorreferensi,
            $tglBatal,
            $keterangan,
            $kodebooking,
        ]);
    }

    // Update status di referensi_mobilejkn_bpjs jadi 'Batal'
    $updRef = $pdo->prepare(
        "UPDATE referensi_mobilejkn_bpjs SET status = 'Batal' WHERE nobooking = ?"
    );
    $updRef->execute([$kodebooking]);

} catch (PDOException $e) {
    error_log("[batal] DB save error: " . $e->getMessage());
    // BPJS sudah sukses, tetap return sukses tapi log errornya
}

// ─── LOG TRACKER ────────────────────────────
try {
    $ip    = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '-')[0];
    $uname = $_SESSION['username'] ?? '-';
    $fname = $_SESSION['fullname'] ?? '-';
    $ket   = "Batal antrean $kodebooking — $keterangan";
    $lg    = $pdo->prepare(
        "INSERT INTO log_tracker (user, fullname, aksi, keterangan, ip, created_at)
         VALUES (?, ?, 'batal_antrean', ?, ?, ?)"
    );
    $lg->execute([$uname, $fname, $ket, $ip, $now]);
} catch (Exception $e) { /* silent */ }

// ─── UPDATE CACHE LOKAL ─────────────────────
// Ambil tanggal dari referensi_mobilejkn_bpjs
$tglCache = date('Y-m-d');
try {
    $tglRow = $pdo->prepare("SELECT tanggalperiksa FROM referensi_mobilejkn_bpjs WHERE nobooking = ? LIMIT 1");
    $tglRow->execute([$kodebooking]);
    $tglData = $tglRow->fetch();
    if ($tglData && !empty($tglData['tanggalperiksa'])) {
        $tglCache = $tglData['tanggalperiksa'];
    }
} catch (Exception $e) { /* pakai hari ini */ }

try {
    $cacheRow = $pdo->prepare(
        "SELECT id, data_json FROM cache_antrean WHERE tanggal = ? AND kodebooking = ? LIMIT 1"
    );
    $cacheRow->execute([$tglCache, $kodebooking]);
    $cRow = $cacheRow->fetch();
    if ($cRow) {
        $cData = json_decode($cRow['data_json'], true);
        if ($cData) {
            $cData['status'] = 'Dibatalkan';
            $pdo->prepare("UPDATE cache_antrean SET data_json = ?, is_stale = 0 WHERE id = ?")
                ->execute([json_encode($cData), $cRow['id']]);
        }
    }
} catch (Exception $e) { /* silent */ }

echo json_encode([
    'metadata' => ['code' => 200, 'message' => 'Antrean berhasil dibatalkan'],
    'tanggal'  => $tglCache,
    'kodebooking' => $kodebooking,
]);