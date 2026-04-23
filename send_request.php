<?php
header('Content-Type: application/json');
require 'config.php';

// ─── Input Validation ───────────────────────
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['kodebooking'], $input['taskid'], $input['waktu'])) {
    echo json_encode(['metadata' => ['code' => 400, 'message' => 'Data tidak lengkap']]);
    exit;
}

$kodebooking = trim($input['kodebooking']);
$taskid      = (int) $input['taskid'];
$waktuMs     = $input['waktu']; // epoch milliseconds dari JS

if ($kodebooking === '' || $taskid < 3 || $taskid > 7 || !is_numeric($waktuMs)) {
    echo json_encode(['metadata' => ['code' => 400, 'message' => 'Parameter tidak valid']]);
    exit;
}

// ─── Build Signature ────────────────────────
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

// ─── Payload ke BPJS (waktu tetap epoch ms) ─
$postData = [
    'kodebooking' => $kodebooking,
    'taskid'      => $taskid,
    'waktu'       => (int) $waktuMs,
];

// ─── cURL Request ───────────────────────────
$ch = curl_init("https://apijkn.bpjs-kesehatan.go.id/antreanrs/antrean/updatewaktu");
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS     => json_encode($postData),
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);

$response = curl_exec($ch);
$curlErr  = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// ─── cURL Error ─────────────────────────────
if ($curlErr) {
    error_log("[send_request] cURL Error: $curlErr");
    echo json_encode(['metadata' => ['code' => 500, 'message' => "cURL Error: $curlErr"]]);
    exit;
}

// ─── Parse Response ─────────────────────────
$responseData = json_decode($response, true);

if ($httpCode === 200
    && isset($responseData['metadata']['code'])
    && (int)$responseData['metadata']['code'] === 200
) {
    // Simpan log ke DB
    saveLog($pdo, $kodebooking, $taskid, (int) $waktuMs);

    // Log tracker
    session_start();
    try {
        $ip      = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '-')[0];
        $now     = (new DateTime('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d H:i:s');
        $uname   = $_SESSION['username'] ?? '-';
        $fname   = $_SESSION['fullname'] ?? '-';
        $ket     = "Kirim TASK $taskid untuk booking $kodebooking";
        $lg = $pdo->prepare("INSERT INTO log_tracker (user,fullname,aksi,keterangan,ip,created_at) VALUES (?,?,?,?,?,?)");
        $lg->execute([$uname, $fname, 'kirim_task', $ket, $ip, $now]);
    } catch (Exception $e) { /* silent */ }

    echo json_encode(['metadata' => ['code' => 200, 'message' => 'Berhasil dikirim']]);
} else {
    $msg  = $responseData['metadata']['message'] ?? 'Gagal mengirim ke BPJS';
    $code = (int)($responseData['metadata']['code'] ?? $httpCode);
    error_log("[send_request] BPJS Error $code: $msg");
    echo json_encode(['metadata' => ['code' => $code, 'message' => $msg]]);
}

// ─── Save Log ───────────────────────────────
function saveLog(PDO $pdo, string $kodebooking, int $taskid, int $waktuMs): void
{
    try {
        // Konversi epoch ms → datetime WIB (Asia/Jakarta)
        $seconds = $waktuMs / 1000;
        $dt      = new DateTime("@$seconds");
        $dt->setTimezone(new DateTimeZone('Asia/Jakarta'));
        $waktuStr = $dt->format('Y-m-d H:i:s');

        $now        = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
        $created_at = $now->format('Y-m-d H:i:s');

        $stmt = $pdo->prepare(
            "INSERT INTO log_antrol (kodebooking, taskid, waktu, created_at)
             VALUES (:kodebooking, :taskid, :waktu, :created_at)"
        );
        $stmt->bindParam(':kodebooking', $kodebooking);
        $stmt->bindParam(':taskid',      $taskid,     PDO::PARAM_INT);
        $stmt->bindParam(':waktu',       $waktuStr);
        $stmt->bindParam(':created_at',  $created_at);
        $stmt->execute();
    } catch (PDOException $e) {
        error_log("[saveLog] DB Error: " . $e->getMessage());
    }
}