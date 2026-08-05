<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
$db = getDB();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$trxId = trim($input['trx_id'] ?? '');

if (!$trxId) {
    jsonResponse(['success' => false, 'message' => 'TrxID is required'], 400);
}

$stmt = $db->prepare("SELECT t.*, m.webhook_url, m.api_key FROM transactions t JOIN merchants m ON t.merchant_id = m.id WHERE t.trx_id = ?");
$stmt->execute([$trxId]);
$txn = $stmt->fetch();

if (!$txn) {
    jsonResponse(['success' => false, 'message' => 'Transaction not found'], 404);
}

if (empty($txn['webhook_url'])) {
    jsonResponse(['success' => false, 'message' => 'Merchant does not have a webhook URL configured.'], 400);
}

$payload = json_encode([
    'event' => 'payment.completed',
    'data' => [
        'trx_id' => $txn['trx_id'],
        'amount' => floatval($txn['amount']),
        'payment_method' => $txn['payment_method'],
        'sender_phone' => $txn['sender_phone'],
        'status' => $txn['status'],
        'session_id' => $txn['session_id'],
        'received_at' => $txn['created_at']
    ]
]);

// Send cURL POST to webhook URL
$ch = curl_init($txn['webhook_url']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-AmaderPay-Signature: ' . hash_hmac('sha256', $payload, $txn['api_key'])
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    jsonResponse(['success' => false, 'message' => 'Webhook delivery failed: ' . $error], 500);
}

jsonResponse([
    'success' => true,
    'message' => "Webhook delivered with HTTP status code $httpCode",
    'response' => $response
]);
