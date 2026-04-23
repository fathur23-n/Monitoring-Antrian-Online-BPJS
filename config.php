<?php
// ─────────────────────────────────────────
//  DATABASE
// ─────────────────────────────────────────
$host     = '127.0.0.1';
$dbname   = 'mlite_com';
$username = 'fathur';
$password = '2000';

// ─────────────────────────────────────────
//  BPJS API CREDENTIALS
// ─────────────────────────────────────────
define('BPJS_CONS_ID',    '');   // isi cons ID
define('BPJS_SECRET_KEY', '');   // isi secret key
define('BPJS_USER_KEY',   '');   // isi user key

// ─────────────────────────────────────────
//  PDO CONNECTION
// ─────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}