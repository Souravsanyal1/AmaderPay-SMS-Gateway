<?php
session_start();
require_once __DIR__ . '/config/db.php';

$isLoggedIn = !empty($_SESSION['merchant_id']);
$merchantData = null;
$transactions = [];
$stats = ['total_count' => 0, 'successful_count' => 0, 'total_volume' => 0];

if ($isLoggedIn) {
    $db = getDB();
    $merchantId = $_SESSION['merchant_id'];

    $stmt = $db->prepare("SELECT * FROM merchants WHERE id = ?");
    $stmt->execute([$merchantId]);
    $merchantData = $stmt->fetch();

    if ($merchantData) {
        $tStmt = $db->prepare("SELECT * FROM transactions WHERE merchant_id = ? ORDER BY id DESC LIMIT 50");
        $tStmt->execute([$merchantId]);
        $transactions = $tStmt->fetchAll();

        $sStmt = $db->prepare("SELECT 
            COUNT(*) as total_count, 
            SUM(CASE WHEN status='COMPLETED' THEN amount ELSE 0 END) as total_volume,
            SUM(CASE WHEN status='COMPLETED' THEN 1 ELSE 0 END) as successful_count
        FROM transactions WHERE merchant_id = ?");
        $sStmt->execute([$merchantId]);
        $stats = $sStmt->fetch();
    } else {
        session_destroy();
        $isLoggedIn = false;
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merchant Portal - AmaderPay</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- Header -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">
                AmaderPay <span class="logo-badge">PORTAL</span>
            </a>
            <ul class="nav-links">
                <li><a href="index.php" class="nav-link">হোম</a></li>
                <li><a href="docs.php" class="nav-link">API Docs</a></li>
                <li><a href="merchant.php" class="nav-link active">মার্চেন্ট পোর্টাল</a></li>
                <li><a href="privacy.php" class="nav-link">প্রাইভেসি পলিসি</a></li>
            </ul>
        </div>
    </nav>

    <div class="container" style="padding: 40px 24px;">

    <?php if ($isLoggedIn && $merchantData): ?>
        <!-- =================================================================== -->
        <!-- LOGGED-IN MERCHANT DASHBOARD                                        -->
        <!-- =================================================================== -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; flex-wrap: wrap; gap: 16px;">
            <div>
                <h1 style="font-family: var(--font-heading); font-size: 2rem;">স্বাগতম, <?php echo htmlspecialchars($merchantData['name']); ?>!</h1>
                <p style="color: var(--text-muted); font-size: 0.95rem;">আপনার মার্চেন্ট অ্যাকাউন্ট ড্যাশবোর্ড ও API কন্ট্রোল প্যানেল</p>
            </div>
            <button onclick="handleLogout()" class="btn-secondary"><i class="fa-solid fa-right-from-bracket"></i> লগআউট</button>
        </div>

        <!-- Stats Grid -->
        <div class="grid-3" style="margin-bottom: 36px;">
            <div class="glass-card">
                <div class="card-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
                <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 4px;">মোট পেমেন্ট ভলিউম</div>
                <div style="font-family: var(--font-heading); font-size: 2.2rem; font-weight: 800; color: #34d399;">
                    ৳ <?php echo number_format($stats['total_volume'] ?? 0, 2); ?>
                </div>
            </div>

            <div class="glass-card">
                <div class="card-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 4px;">সফল লেনদেন</div>
                <div style="font-family: var(--font-heading); font-size: 2.2rem; font-weight: 800; color: #60a5fa;">
                    <?php echo intval($stats['successful_count'] ?? 0); ?> টি
                </div>
            </div>

            <div class="glass-card">
                <div class="card-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 4px;">মোট রিকোয়েস্ট</div>
                <div style="font-family: var(--font-heading); font-size: 2.2rem; font-weight: 800; color: #c4b5fd;">
                    <?php echo intval($stats['total_count'] ?? 0); ?> টি
                </div>
            </div>
        </div>

        <!-- API Keys & Device Key Configuration -->
        <div class="glass-card" style="margin-bottom: 36px; padding: 32px;">
            <h3 style="font-family: var(--font-heading); font-size: 1.4rem; margin-bottom: 20px;">
                <i class="fa-solid fa-key" style="color: var(--primary);"></i> আপনার সিকিউরিটি ও কানেক্টিভিটি ক্রেডেনশিয়াল
            </h3>

            <div class="grid-3" style="margin: 0;">
                <div>
                    <label class="form-label">API Key (আপনার ওয়েবসাইটের জন্য)</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="merchant-api-key" class="form-input" readonly value="<?php echo htmlspecialchars($merchantData['api_key']); ?>">
                        <button class="copy-btn" data-copy-target="merchant-api-key"><i class="fa-regular fa-copy"></i> Copy</button>
                    </div>
                </div>

                <div>
                    <label class="form-label">Device Key (Android APK পেয়ারিং)</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="merchant-device-key" class="form-input" readonly value="<?php echo htmlspecialchars($merchantData['device_key']); ?>">
                        <button class="copy-btn" data-copy-target="merchant-device-key"><i class="fa-regular fa-copy"></i> Copy</button>
                    </div>
                </div>

                <div>
                    <label class="form-label">Webhook URL (Callback)</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="url" id="merchant-webhook-url" class="form-input" placeholder="https://yoursite.com/webhook" value="<?php echo htmlspecialchars($merchantData['webhook_url'] ?? ''); ?>">
                        <button onclick="saveWebhookUrl()" class="btn-primary" style="padding: 0 16px;">Save</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction History Table -->
        <div class="glass-card" style="padding: 32px;">
            <h3 style="font-family: var(--font-heading); font-size: 1.4rem; margin-bottom: 20px;">
                <i class="fa-solid fa-list-check" style="color: var(--primary);"></i> সাম্প্রতিক লেনদেনের তালিকা (Transactions)
            </h3>

            <?php if (empty($transactions)): ?>
                <div style="text-align: center; padding: 40px 0; color: var(--text-muted);">
                    <i class="fa-solid fa-inbox" style="font-size: 3rem; margin-bottom: 12px; display: block;"></i>
                    এখনো কোনো পেমেন্ট রেকর্ড নেই। API এর মাধ্যমে প্রথম টেস্ট পেমেন্ট করুন।
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>TrxID</th>
                                <th>Method</th>
                                <th>Sender Phone</th>
                                <th>Amount (৳)</th>
                                <th>Status</th>
                                <th>Session ID</th>
                                <th>Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $t): ?>
                                <tr>
                                    <td><strong style="font-family: monospace; color: #a78bfa;"><?php echo htmlspecialchars($t['trx_id']); ?></strong></td>
                                    <td>
                                        <span class="mfs-pill mfs-<?php echo strtolower($t['payment_method']); ?>" style="padding: 4px 8px; font-size: 0.75rem;">
                                            <?php echo htmlspecialchars($t['payment_method']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($t['sender_phone']); ?></td>
                                    <td><strong>৳ <?php echo number_format($t['amount'], 2); ?></strong></td>
                                    <td>
                                        <?php if ($t['status'] === 'COMPLETED'): ?>
                                            <span class="badge badge-success">COMPLETED</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning"><?php echo htmlspecialchars($t['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-family: monospace; font-size: 0.85rem; color: var(--text-muted);"><?php echo htmlspecialchars($t['session_id']); ?></td>
                                    <td style="font-size: 0.85rem; color: var(--text-muted);"><?php echo htmlspecialchars($t['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- =================================================================== -->
        <!-- LOGIN & REGISTRATION FORMS (GUEST VIEW)                             -->
        <!-- =================================================================== -->
        <div style="max-width: 480px; margin: 0 auto;">
            <div class="tabs-container glass-card" style="padding: 36px;">
                
                <div class="tabs-nav" style="justify-content: center; margin-bottom: 28px;">
                    <button class="tab-btn active" data-tab="login-tab">সাইন ইন (Login)</button>
                    <button class="tab-btn" data-tab="register-tab">নতুন মার্চেন্ট একাউন্ট</button>
                </div>

                <!-- Login Form -->
                <div id="login-tab" class="tab-content">
                    <h2 style="font-family: var(--font-heading); font-size: 1.5rem; text-align: center; margin-bottom: 8px;">মার্চেন্ট লগইন</h2>
                    <p style="text-align: center; color: var(--text-muted); font-size: 0.9rem; margin-bottom: 24px;">আপনার নিবন্ধিত ইমেইল ও পাসওয়ার্ড প্রদান করুন</p>

                    <form id="form-login" onsubmit="handleLogin(event)">
                        <div class="form-group">
                            <label class="form-label">ইমেইল এড্রেস</label>
                            <input type="email" id="login-email" class="form-input" placeholder="merchant@example.com" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">পাসওয়ার্ড</label>
                            <input type="password" id="login-password" class="form-input" placeholder="••••••••" required>
                        </div>
                        <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; padding: 14px;">
                            <i class="fa-solid fa-right-to-bracket"></i> লগইন করুন
                        </button>
                    </form>
                </div>

                <!-- Register Form -->
                <div id="register-tab" class="tab-content" style="display: none;">
                    <h2 style="font-family: var(--font-heading); font-size: 1.5rem; text-align: center; margin-bottom: 8px;">মার্চেন্ট রেজিস্ট্রেশন</h2>
                    <p style="text-align: center; color: var(--text-muted); font-size: 0.9rem; margin-bottom: 24px;">বিনামূল্যে নতুন মার্চেন্ট একাউন্ট তৈরি করুন</p>

                    <form id="form-register" onsubmit="handleRegister(event)">
                        <div class="form-group">
                            <label class="form-label">মার্চেন্ট / ব্যবসা প্রতিষ্ঠানের নাম</label>
                            <input type="text" id="reg-name" class="form-input" placeholder="যেমন: Apon Enterprise" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">ইমেইল এড্রেস</label>
                            <input type="email" id="reg-email" class="form-input" placeholder="merchant@example.com" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">মোবাইল নম্বর</label>
                            <input type="tel" id="reg-phone" class="form-input" placeholder="01700000000" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">পাসওয়ার্ড</label>
                            <input type="password" id="reg-password" class="form-input" placeholder="নূন্যতম ৬ অক্ষর" required>
                        </div>
                        <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; padding: 14px;">
                            <i class="fa-solid fa-user-check"></i> অ্যাকাউন্ট তৈরি করুন
                        </button>
                    </form>
                </div>

            </div>
        </div>
    <?php endif; ?>

    </div>

    <script src="assets/js/main.js"></script>
    <script>
        async function handleLogin(e) {
            e.preventDefault();
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;

            try {
                const res = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ email, password })
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('সার্ভার কানেকশন ত্রুটি!', 'error');
            }
        }

        async function handleRegister(e) {
            e.preventDefault();
            const name = document.getElementById('reg-name').value;
            const email = document.getElementById('reg-email').value;
            const phone = document.getElementById('reg-phone').value;
            const password = document.getElementById('reg-password').value;

            try {
                const res = await fetch('api/auth.php?action=register', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ name, email, phone, password })
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('সার্ভার কানেকশন ত্রুটি!', 'error');
            }
        }

        async function handleLogout() {
            await fetch('api/auth.php?action=logout');
            showToast('লগআউট করা হয়েছে', 'info');
            setTimeout(() => location.reload(), 600);
        }

        async function saveWebhookUrl() {
            const url = document.getElementById('merchant-webhook-url').value;
            try {
                const res = await fetch('api/auth.php?action=update_settings', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ webhook_url: url })
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Webhook URL সফলভাবে আপডেট হয়েছে!', 'success');
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('আপডেট ব্যর্থ হয়েছে!', 'error');
            }
        }
    </script>
</body>
</html>
