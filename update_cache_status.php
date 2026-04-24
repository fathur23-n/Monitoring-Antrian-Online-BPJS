<?php
header('Content-Type: application/json');
require 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']); exit;
}

$input       = json_decode(file_get_contents('php://input'), true);
$kodebooking = trim($input['kodebooking'] ?? '');
$tanggal     = trim($input['tanggal']     ?? '');
$newStatus   = trim($input['status']      ?? '');

if (!$kodebooking || !$tanggal || !$newStatus) {
    echo json_encode(['ok' => false, 'message' => 'Parameter tidak lengkap']); exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT id, data_json FROM cache_antrean
         WHERE tanggal = ? AND kodebooking = ? LIMIT 1"
    );
    $stmt->execute([$tanggal, $kodebooking]);
    $row = $stmt->fetch();

    if (!$row) {
        echo json_encode(['ok' => false, 'message' => 'Cache tidak ditemukan']); exit;
    }

    $data = json_decode($row['data_json'], true);
    if (!$data) {
        echo json_encode(['ok' => false, 'message' => 'Data cache tidak valid']); exit;
    }

    // Update status di JSON
    $data['status'] = $newStatus;

    $upd = $pdo->prepare(
        "UPDATE cache_antrean SET data_json = ?, is_stale = 0
         WHERE id = ?"
    );
    $upd->execute([json_encode($data), $row['id']]);

    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    error_log("[update_cache_status] " . $e->getMessage());
    echo json_encode(['ok' => false, 'message' => 'DB error']);
}