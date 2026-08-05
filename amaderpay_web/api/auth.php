<?php
require_once __DIR__ . '/../config/db.php';

session_start();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php_input'), true) ?? $_POST;

$db = getDB();

if ($action === 'register') {
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $password = trim($input['password'] ?? '');

    if (!$name || !$email || !$phone || !$password) {
        jsonResponse(['success' => false, 'message' => 'সকল ফিল্ড পূরণ করা আবশ্যক!'], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'message' => 'সঠিক ইমেইল এড্রেস প্রদান করুন!'], 400);
    }

    // Check existing
    $stmt = $db->prepare("SELECT id FROM merchants WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'এই ইমেইল দিয়ে ইতোমধ্যে মার্চেন্ট অ্যাকাউন্ট তৈরি করা আছে!'], 400);
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $apiKey = 'ap_live_' . bin2hex(random_bytes(16));
    $deviceKey = 'pg_dev_' . bin2hex(random_bytes(12));

    try {
        $stmt = $db->prepare("INSERT INTO merchants (name, email, phone, password, api_key, device_key) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $hashedPassword, $apiKey, $deviceKey]);
        $merchantId = $db->lastInsertId();

        $_SESSION['merchant_id'] = $merchantId;
        $_SESSION['merchant_name'] = $name;
        $_SESSION['merchant_email'] = $email;

        jsonResponse([
            'success' => true,
            'message' => 'মার্চেন্ট রেজিস্ট্রেশন সফল হয়েছে!',
            'data' => [
                'id' => $merchantId,
                'name' => $name,
                'email' => $email,
                'api_key' => $apiKey,
                'device_key' => $deviceKey
            ]
        ]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => 'অ্যাকাউন্ট তৈরি করতে সমস্যা হয়েছে: ' . $e->getMessage()], 500);
    }
}

elseif ($action === 'login') {
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');

    if (!$email || !$password) {
        jsonResponse(['success' => false, 'message' => 'ইমেইল ও পাসওয়ার্ড প্রদান করুন!'], 400);
    }

    $stmt = $db->prepare("SELECT * FROM merchants WHERE email = ?");
    $stmt->execute([$email]);
    $merchant = $stmt->fetch();

    if (!$merchant || !password_verify($password, $merchant['password'])) {
        jsonResponse(['success' => false, 'message' => 'ভুল ইমেইল অথবা পাসওয়ার্ড!'], 401);
    }

    $_SESSION['merchant_id'] = $merchant['id'];
    $_SESSION['merchant_name'] = $merchant['name'];
    $_SESSION['merchant_email'] = $merchant['email'];

    jsonResponse([
        'success' => true,
        'message' => 'লগইন সফল হয়েছে!',
        'data' => [
            'id' => $merchant['id'],
            'name' => $merchant['name'],
            'email' => $merchant['email'],
            'phone' => $merchant['phone'],
            'api_key' => $merchant['api_key'],
            'device_key' => $merchant['device_key'],
            'webhook_url' => $merchant['webhook_url']
        ]
    ]);
}

elseif ($action === 'profile') {
    if (empty($_SESSION['merchant_id'])) {
        jsonResponse(['success' => false, 'message' => 'অননুমোদিত এক্সেস! আগে লগইন করুন।'], 401);
    }

    $stmt = $db->prepare("SELECT id, name, email, phone, api_key, device_key, webhook_url, created_at FROM merchants WHERE id = ?");
    $stmt->execute([$_SESSION['merchant_id']]);
    $merchant = $stmt->fetch();

    jsonResponse([
        'success' => true,
        'data' => $merchant
    ]);
}

elseif ($action === 'update_settings') {
    if (empty($_SESSION['merchant_id'])) {
        jsonResponse(['success' => false, 'message' => 'অননুমোদিত এক্সেস!'], 401);
    }

    $webhookUrl = trim($input['webhook_url'] ?? '');
    $stmt = $db->prepare("UPDATE merchants SET webhook_url = ? WHERE id = ?");
    $stmt->execute([$webhookUrl, $_SESSION['merchant_id']]);

    jsonResponse(['success' => true, 'message' => 'সেটিংস আপডেট সফল হয়েছে!']);
}

elseif ($action === 'logout') {
    session_destroy();
    jsonResponse(['success' => true, 'message' => 'লগআউট সফল হয়েছে!']);
}

else {
    jsonResponse(['success' => false, 'message' => 'Invalid auth action!'], 400);
}
