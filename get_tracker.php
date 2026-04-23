<?php
header('Content-Type: application/json');
session_start();

// Proteksi: hanya admin + rsdev
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin' ||
    ($_SESSION['username'] ?? '') !== 'rsdev'
) {
    http_response_code(403);
    echo json_encode(['error' => true, 'message' => 'Akses ditolak']);
    exit;
}

require 'config.php';

$start = isset($_GET['start']) ? trim($_GET['start']) : date('Y-m-d');
$end   = isset($_GET['end'])   ? trim($_GET['end'])   : $start;
$user  = isset($_GET['user'])  ? trim($_GET['user'])  : '';
$aksi  = isset($_GET['aksi'])  ? trim($_GET['aksi'])  : '';

$params = [':start' => $start . ' 00:00:00', ':end' => $end . ' 23:59:59'];
$where  = "WHERE created_at BETWEEN :start AND :end";

if ($user !== '') { $where .= " AND user = :user"; $params[':user'] = $user; }
if ($aksi !== '') { $where .= " AND aksi = :aksi"; $params[':aksi'] = $aksi; }

try {
    $stmt = $pdo->prepare("SELECT * FROM log_tracker $where ORDER BY created_at DESC");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Ambil daftar user untuk filter dropdown
    $users = $pdo->query("SELECT DISTINCT user FROM log_tracker ORDER BY user")->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode(['data' => $rows, 'users' => $users]);
} catch (PDOException $e) {
    error_log("[get_tracker] " . $e->getMessage());
    echo json_encode(['data' => [], 'users' => [], 'error' => true]);
}