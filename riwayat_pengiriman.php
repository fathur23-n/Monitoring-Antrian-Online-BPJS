<?php
require_once 'config.php';
header('Content-Type: application/json');

// Mengambil data dari permintaan POST
$data = json_decode(file_get_contents("php://input"), true);
$start_date = $data['start_date'] ?? null;
$end_date = $data['end_date'] ?? null;

// Membangun query untuk mengambil riwayat pengiriman
$query = "SELECT * FROM log_antrol WHERE 1=1";

// Jika kedua tanggal ada, tambahkan kondisi pada query
if ($start_date && $end_date) {
    // Menggunakan kolom waktu untuk filter (misalnya, kolom 'waktu')
    $query .= " AND waktu BETWEEN :start_date AND :end_date"; 
}

// Persiapkan pernyataan
$stmt = $pdo->prepare($query);

// Mengikat parameter jika tanggal disediakan
if ($start_date && $end_date) {
    // Menambahkan waktu di awal dan akhir tanggal untuk batas yang tepat
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
}

// Eksekusi query
try {
    $stmt->execute();
    $riwayat = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Mengembalikan data dalam format JSON
    echo json_encode($riwayat);
} catch (PDOException $e) {
    // Tangani kesalahan SQL dan kembalikan pesan kesalahan
    echo json_encode(['error' => $e->getMessage()]);
}
