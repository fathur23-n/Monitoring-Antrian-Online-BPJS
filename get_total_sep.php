<?php
header('Content-Type: application/json');
require 'config.php';

$start = isset($_GET['start']) ? trim($_GET['start']) : (isset($_GET['tanggal']) ? trim($_GET['tanggal']) : '');
$end   = isset($_GET['end'])   ? trim($_GET['end'])   : $start;

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
    echo json_encode(['count' => 0, 'message' => 'Format tanggal tidak valid']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS total
         FROM bridging_sep
         WHERE tglsep BETWEEN :start AND :end
           AND kdpolitujuan NOT IN ('IRM', 'IGD', 'HDL')"
    );
    $stmt->execute([':start' => $start, ':end' => $end]);
    $row = $stmt->fetch();
    echo json_encode(['count' => (int)($row['total'] ?? 0)]);
} catch (PDOException $e) {
    error_log("[get_total_sep] " . $e->getMessage());
    echo json_encode(['count' => 0, 'message' => 'Database error']);
}