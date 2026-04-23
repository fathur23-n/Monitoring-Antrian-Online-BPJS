<?php
header('Content-Type: application/json');
require 'config.php';

// Bisa dipanggil dari session (internal) atau langsung
session_start();

$input = json_decode(file_get_contents('php://input'), true);

$user       = trim($input['user']      ?? ($_SESSION['username'] ?? ''));
$fullname   = trim($input['fullname']  ?? ($_SESSION['fullname'] ?? ''));
$aksi       = trim($input['aksi']      ?? '');
$keterangan = trim($input['keterangan']?? '');

$allowed = ['login', 'kirim_task', 'batal_antrean'];
if (!$user || !in_array($aksi, $allowed) || !$keterangan) {
    echo json_encode(['ok' => false, 'message' => 'Parameter tidak lengkap']);
    exit;
}

// Ambil IP
$ip = $_SERVER['HTTP_X_FORWARDED_FOR']
    ?? $_SERVER['HTTP_CLIENT_IP']
    ?? $_SERVER['REMOTE_ADDR']
    ?? '-';
$ip = explode(',', $ip)[0];

try {
    $now  = (new DateTime('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d H:i:s');
    $stmt = $pdo->prepare(
        "INSERT INTO log_tracker (user, fullname, aksi, keterangan, ip, created_at)
         VALUES (:user, :fullname, :aksi, :keterangan, :ip, :created_at)"
    );
    $stmt->execute([
        ':user'       => $user,
        ':fullname'   => $fullname,
        ':aksi'       => $aksi,
        ':keterangan' => $keterangan,
        ':ip'         => $ip,
        ':created_at' => $now,
    ]);
    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    error_log("[save_tracker] " . $e->getMessage());
    echo json_encode(['ok' => false, 'message' => 'DB error']);
}