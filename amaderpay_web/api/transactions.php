<?php
require_once __DIR__ . '/../config/db.php';

session_start();
header('Content-Type: application/json');

if (empty($_SESSION['merchant_id'])) {
    jsonResponse(['success' => false, 'message' => 'অননুমোদিত এক্সেস!'], 401);
}

$db = getDB();
$merchantId = $_SESSION['merchant_id'];

// Get transaction list for merchant
$stmt = $db->prepare("SELECT * FROM transactions WHERE merchant_id = ? ORDER BY id DESC LIMIT 50");
$stmt->execute([$merchantId]);
$transactions = $stmt->fetchAll();

// Get stats
$statStmt = $db->prepare("SELECT 
    COUNT(*) as total_count, 
    SUM(CASE WHEN status='COMPLETED' THEN amount ELSE 0 END) as total_volume,
    SUM(CASE WHEN status='COMPLETED' THEN 1 ELSE 0 END) as successful_count
FROM transactions WHERE merchant_id = ?");
$statStmt->execute([$merchantId]);
$stats = $statStmt->fetch();

jsonResponse([
    'success' => true,
    'data' => [
        'transactions' => $transactions,
        'stats' => [
            'total_count' => intval($stats['total_count'] ?? 0),
            'successful_count' => intval($stats['successful_count'] ?? 0),
            'total_volume' => floatval($stats['total_volume'] ?? 0)
        ]
    ]
]);
