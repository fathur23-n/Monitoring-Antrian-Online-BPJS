<?php
header('Content-Type: application/json');
require 'config.php';

$input  = json_decode(file_get_contents('php://input'), true);
$start  = trim($input['start']  ?? $input['tanggal'] ?? '');
$end    = trim($input['end']    ?? $start);
$norawat = $input['norawat'] ?? [];

if (empty($start) || !is_array($norawat) || empty($norawat)) {
    echo json_encode(['data' => []]); exit;
}

$norawat = array_values(array_filter(array_map('trim', $norawat)));
if (empty($norawat)) {
    echo json_encode(['data' => []]); exit;
}

try {
    $ph     = implode(',', array_fill(0, count($norawat), '?'));
    $params = array_merge([$start, $end], $norawat);
    $stmt   = $pdo->prepare(
        "SELECT bs.nomr, bs.`user`, p.nm_pasien
         FROM bridging_sep bs
         LEFT JOIN pasien p ON CAST(p.no_rkm_medis AS UNSIGNED) = CAST(bs.nomr AS UNSIGNED)
         WHERE bs.tglsep BETWEEN ? AND ?
           AND bs.nomr IN ($ph)
           AND bs.kdpolitujuan NOT IN ('IRM','IGD','HDL')"
    );
    $stmt->execute($params);
    echo json_encode(['data' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    error_log("[get_belum_user] " . $e->getMessage());
    echo json_encode(['data' => [], 'error' => true]);
}