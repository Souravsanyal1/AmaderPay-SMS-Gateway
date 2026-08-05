<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, x-api-key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$db = getDB();

// Get action from query string or request path
$action = $_GET['action'] ?? '';
if (!$action) {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (strpos($uri, '/verify') !== false) {
        $action = 'verify';
    } else {
        $action = 'create';
    }
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// ─── 1. Create Checkout Session ──────────────────────────────────────────────
if ($action === 'create') {
    // Authenticate Merchant via Header or JSON
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $input['api_key'] ?? '';
    $apiKey = str_replace('Bearer ', '', $apiKey);

    if (!$apiKey) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized: API Key is required in x-api-key header!'], 401);
    }

    $stmt = $db->prepare("SELECT id FROM merchants WHERE api_key = ?");
    $stmt->execute([$apiKey]);
    $merchant = $stmt->fetch();

    if (!$merchant) {
        jsonResponse(['success' => false, 'message' => 'Unauthorized: Invalid API Key!'], 401);
    }

    $amount = floatval($input['amount'] ?? 0);
    if ($amount <= 0) {
        jsonResponse(['success' => false, 'message' => 'Amount must be greater than 0'], 400);
    }

    $customerName = trim($input['customer_name'] ?? '');
    $customerEmail = trim($input['customer_email'] ?? '');
    $redirectUrl = trim($input['redirect_url'] ?? '');

    $sessionId = 'pay_sess_' . bin2hex(random_bytes(10));
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'amader-pay-sms-gateway.vercel.app';
    $checkoutUrl = "$protocol://$host/checkout?session_id=$sessionId";

    try {
        $stmt = $db->prepare("INSERT INTO checkout_sessions (session_id, merchant_id, amount, customer_name, customer_email, redirect_url, status) VALUES (?, ?, ?, ?, ?, ?, 'PENDING')");
        $stmt->execute([$sessionId, $merchant['id'], $amount, $customerName, $customerEmail, $redirectUrl]);

        jsonResponse([
            'success' => true,
            'message' => 'Checkout session created successfully',
            'data' => [
                'session_id' => $sessionId,
                'checkout_url' => $checkoutUrl,
                'amount' => $amount,
                'status' => 'PENDING',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ], 201);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'Failed to create session: ' . $e->getMessage()], 500);
    }
}

// ─── 2. Verify Session Status ────────────────────────────────────────────────
elseif ($action === 'verify') {
    $sessionId = $_GET['id'] ?? $input['session_id'] ?? '';
    if (!$sessionId) {
        jsonResponse(['success' => false, 'message' => 'Session ID is required'], 400);
    }

    $stmt = $db->prepare("SELECT s.*, m.name as merchant_name FROM checkout_sessions s JOIN merchants m ON s.merchant_id = m.id WHERE s.session_id = ?");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();

    if (!$session) {
        jsonResponse(['success' => false, 'message' => 'Session not found'], 404);
    }

    // Check if there is a matching transaction
    $txnDetails = null;
    if (!empty($session['trx_id'])) {
        $tStmt = $db->prepare("SELECT * FROM transactions WHERE trx_id = ?");
        $tStmt->execute([$session['trx_id']]);
        $txnDetails = $tStmt->fetch();
    }

    jsonResponse([
        'success' => true,
        'data' => [
            'session_id' => $session['session_id'],
            'merchant_name' => $session['merchant_name'],
            'amount' => floatval($session['amount']),
            'status' => $session['status'],
            'trx_id' => $session['trx_id'],
            'customer_name' => $session['customer_name'],
            'payment_method' => $txnDetails['payment_method'] ?? 'UNKNOWN',
            'sender_phone' => $txnDetails['sender_phone'] ?? '',
            'created_at' => $session['created_at']
        ]
    ]);
}

// ─── 3. Submit Transaction (Manual/Customer verification) ────────────────────
elseif ($action === 'submit_trx') {
    $sessionId = trim($input['session_id'] ?? '');
    $trxId = strtoupper(trim($input['trx_id'] ?? ''));
    $method = strtoupper(trim($input['payment_method'] ?? 'BKASH'));
    $senderPhone = trim($input['sender_phone'] ?? '');

    if (!$sessionId || !$trxId) {
        jsonResponse(['success' => false, 'message' => 'Session ID and TrxID are required'], 400);
    }

    $stmt = $db->prepare("SELECT * FROM checkout_sessions WHERE session_id = ?");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();

    if (!$session) {
        jsonResponse(['success' => false, 'message' => 'Session not found'], 404);
    }

    if ($session['status'] === 'COMPLETED') {
        jsonResponse(['success' => true, 'message' => 'পেমেন্ট ইতোমধ্যেই সম্পূর্ণ হয়েছে!']);
    }

    // Update Session
    $updateStmt = $db->prepare("UPDATE checkout_sessions SET status = 'COMPLETED', trx_id = ? WHERE session_id = ?");
    $updateStmt->execute([$trxId, $sessionId]);

    // Record Transaction
    try {
        $tStmt = $db->prepare("INSERT INTO transactions (trx_id, merchant_id, payment_method, sender_phone, amount, status, session_id) VALUES (?, ?, ?, ?, ?, 'COMPLETED', ?)");
        $tStmt->execute([$trxId, $session['merchant_id'], $method, $senderPhone ?: '01700000000', $session['amount'], $sessionId]);
    } catch (Exception $e) {
        // TrxID may exist
    }

    jsonResponse([
        'success' => true,
        'message' => 'পেমেন্ট সফলভাবে ভেরিফাই ও সম্পন্ন হয়েছে!',
        'redirect_url' => $session['redirect_url']
    ]);
}

else {
    jsonResponse(['success' => false, 'message' => 'Invalid endpoint action'], 400);
}
