<?php
header('Content-Type: application/json');
require 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => true]); exit;
}

$input    = json_decode(file_get_contents('php://input'), true);
$bookings = $input['bookings'] ?? [];

if (empty($bookings) || !is_array($bookings)) {
    echo json_encode(['data' => []]); exit;
}

$bookings = array_values(array_filter(array_map('trim', $bookings)));
if (empty($bookings)) {
    echo json_encode(['data' => []]); exit;
}

try {
    $ph = implode(',', array_fill(0, count($bookings), '?'));

    // ─── SOURCE 1: log_antrol (by kodebooking) ───
    $stmt1 = $pdo->prepare(
        "SELECT kodebooking, taskid
         FROM log_antrol
         WHERE kodebooking IN ($ph)
         GROUP BY kodebooking, taskid"
    );
    $stmt1->execute($bookings);
    $rows1 = $stmt1->fetchAll();

    // ─── SOURCE 2: referensi_mobilejkn_bpjs_taskid (join via no_rawat) ───
    // Join: referensi_mobilejkn_bpjs (nobooking→no_rawat) → referensi_mobilejkn_bpjs_taskid
    $stmt2 = $pdo->prepare(
        "SELECT r.nobooking AS kodebooking, t.taskid
         FROM referensi_mobilejkn_bpjs_taskid t
         JOIN referensi_mobilejkn_bpjs r ON r.no_rawat = t.no_rawat
         WHERE r.nobooking IN ($ph)
         GROUP BY r.nobooking, t.taskid"
    );
    $stmt2->execute($bookings);
    $rows2 = $stmt2->fetchAll();

    // ─── GABUNG KEDUA SUMBER ─────────────────────
    $result = [];
    foreach (array_merge($rows1, $rows2) as $row) {
        $kb  = $row['kodebooking'];
        $tid = (int)$row['taskid'];
        if (!isset($result[$kb])) $result[$kb] = [];
        if (!in_array($tid, $result[$kb])) $result[$kb][] = $tid;
    }

    // Sort taskid per booking
    foreach ($result as &$tasks) sort($tasks);

    echo json_encode(['data' => $result]);
} catch (PDOException $e) {
    error_log("[get_task_done] " . $e->getMessage());
    echo json_encode(['data' => []]);
}