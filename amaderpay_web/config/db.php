<?php
// AmaderPay PHP Database Configuration & Helper (PDO with SQLite fallback)

define('DB_PATH', __DIR__ . '/amaderpay.sqlite');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Enable WAL mode for high performance
            $pdo->exec("PRAGMA journal_mode = WAL;");
            
            // Auto initialize tables
            initDatabase($pdo);
        } catch (PDOException $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
            exit;
        }
    }
    return $pdo;
}

function initDatabase($pdo) {
    // Merchants table
    $pdo->exec("CREATE TABLE IF NOT EXISTS merchants (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        phone TEXT NOT NULL,
        password TEXT NOT NULL,
        api_key TEXT UNIQUE NOT NULL,
        device_key TEXT UNIQUE NOT NULL,
        webhook_url TEXT DEFAULT '',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Transactions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        trx_id TEXT UNIQUE NOT NULL,
        merchant_id INTEGER NOT NULL,
        payment_method TEXT NOT NULL,
        sender_phone TEXT NOT NULL,
        amount REAL NOT NULL,
        status TEXT DEFAULT 'PENDING',
        raw_sms TEXT DEFAULT '',
        session_id TEXT UNIQUE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (merchant_id) REFERENCES merchants(id)
    )");

    // Checkout sessions table
    $pdo->exec("CREATE TABLE IF NOT EXISTS checkout_sessions (
        session_id TEXT PRIMARY KEY,
        merchant_id INTEGER NOT NULL,
        amount REAL NOT NULL,
        customer_name TEXT DEFAULT '',
        customer_email TEXT DEFAULT '',
        redirect_url TEXT DEFAULT '',
        status TEXT DEFAULT 'PENDING',
        trx_id TEXT DEFAULT '',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (merchant_id) REFERENCES merchants(id)
    )");
}

// Global JSON helper
function jsonResponse($data, $statusCode = 200) {
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}
