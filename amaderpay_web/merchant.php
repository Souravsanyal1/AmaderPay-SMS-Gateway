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
    <title>Merchant Portal - AmaderPay Gateway</title>
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

    <div class="container" style="padding: 50px 24px;">

    <?php if ($isLoggedIn && $merchantData): ?>
        <!-- =================================================================== -->
        <!-- LOGGED-IN MERCHANT DASHBOARD (ULTRA PREMIUM)                        -->
        <!-- =================================================================== -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 36px; flex-wrap: wrap; gap: 20px;">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 6px;">
                    <h1 style="font-family: var(--font-heading); font-size: 2.2rem; font-weight: 900;">স্বাগতম, <?php echo htmlspecialchars($merchantData['name']); ?>!</h1>
                    <span class="badge badge-success" style="font-size: 0.85rem;"><span class="status-dot status-online"></span> Gateway Active</span>
                </div>
                <p style="color: var(--text-muted); font-size: 1rem;">আপনার মার্চেন্ট অ্যাকাউন্ট ড্যাশবোর্ড ও API লাইভ স্টেটাস প্যানেল</p>
            </div>
            <button onclick="handleLogout()" class="btn-secondary"><i class="fa-solid fa-right-from-bracket"></i> লগআউট</button>
        </div>

        <!-- Stats Metric Grid -->
        <div class="grid-3" style="margin-bottom: 40px;">
            <div class="glass-card" style="border-left: 4px solid #10b981;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div style="font-size: 0.9rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">মোট পেমেন্ট ভলিউম</div>
                    <div class="card-icon" style="margin-bottom: 0; width: 44px; height: 44px; font-size: 1.3rem;"><i class="fa-solid fa-sack-dollar"></i></div>
                </div>
                <div style="font-family: var(--font-heading); font-size: 2.5rem; font-weight: 900; color: #10b981;">
                    ৳ <?php echo number_format($stats['total_volume'] ?? 0, 2); ?>
                </div>
            </div>

            <div class="glass-card" style="border-left: 4px solid #60a5fa;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div style="font-size: 0.9rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">সফল লেনদেন (Completed)</div>
                    <div class="card-icon" style="margin-bottom: 0; width: 44px; height: 44px; font-size: 1.3rem;"><i class="fa-solid fa-circle-check"></i></div>
                </div>
                <div style="font-family: var(--font-heading); font-size: 2.5rem; font-weight: 900; color: #60a5fa;">
                    <?php echo intval($stats['successful_count'] ?? 0); ?> <span style="font-size: 1.2rem; color: var(--text-muted);">টি</span>
                </div>
            </div>

            <div class="glass-card" style="border-left: 4px solid #a78bfa;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div style="font-size: 0.9rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">মোট API রিকোয়েস্ট</div>
                    <div class="card-icon" style="margin-bottom: 0; width: 44px; height: 44px; font-size: 1.3rem;"><i class="fa-solid fa-server"></i></div>
                </div>
                <div style="font-family: var(--font-heading); font-size: 2.5rem; font-weight: 900; color: #c4b5fd;">
                    <?php echo intval($stats['total_count'] ?? 0); ?> <span style="font-size: 1.2rem; color: var(--text-muted);">টি</span>
                </div>
            </div>
        </div>

        <!-- Credentials & Device Pairing Settings -->
        <div class="glass-card" style="margin-bottom: 40px; padding: 36px; border-color: var(--border-glow);">
            <h3 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-shield-halved" style="color: var(--primary);"></i> আপনার সিকিউরিটি ক্রেডেনশিয়াল ও APK পেয়ারিং
            </h3>

            <div class="grid-3" style="margin: 0; gap: 24px;">
                <div>
                    <label class="form-label">API Key (আপনার সার্ভারের জন্য)</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="password" id="merchant-api-key" class="form-input" readonly value="<?php echo htmlspecialchars($merchantData['api_key']); ?>" style="font-family: monospace;">
                        <button class="toggle-mask-btn copy-btn" data-target="merchant-api-key" type="button"><i class="fa-regular fa-eye"></i> Show</button>
                        <button class="copy-btn" data-copy-target="merchant-api-key" type="button"><i class="fa-regular fa-copy"></i> Copy</button>
                    </div>
                </div>

                <div>
                    <label class="form-label">Device Key (Android App পেয়ারিং)</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="password" id="merchant-device-key" class="form-input" readonly value="<?php echo htmlspecialchars($merchantData['device_key']); ?>" style="font-family: monospace;">
                        <button class="toggle-mask-btn copy-btn" data-target="merchant-device-key" type="button"><i class="fa-regular fa-eye"></i> Show</button>
                        <button class="copy-btn" data-copy-target="merchant-device-key" type="button"><i class="fa-regular fa-copy"></i> Copy</button>
                    </div>
                </div>

                <div>
                    <label class="form-label">Webhook URL (Callback Notification)</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="url" id="merchant-webhook-url" class="form-input" placeholder="https://yoursite.com/api/webhook" value="<?php echo htmlspecialchars($merchantData['webhook_url'] ?? ''); ?>">
                        <button onclick="saveWebhookUrl()" class="btn-primary" style="padding: 0 20px;">Save</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction History Table with Realtime Filter -->
        <div class="glass-card" style="padding: 36px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
                <h3 style="font-family: var(--font-heading); font-size: 1.5rem; display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-list-check" style="color: var(--primary);"></i> সাম্প্রতিক লেনদেন ইতিহাস (Live Transactions)
                </h3>
                <div style="position: relative; width: 280px;">
                    <input type="text" id="tx-search" class="form-input" placeholder="TrxID বা নম্বর খুঁজুন..." onkeyup="filterTable('tx-search', 'tx-table')" style="padding-left: 38px;">
                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-dim);"></i>
                </div>
            </div>

            <?php if (empty($transactions)): ?>
                <div style="text-align: center; padding: 50px 0; color: var(--text-muted);">
                    <i class="fa-solid fa-inbox" style="font-size: 3.5rem; margin-bottom: 16px; display: block; color: var(--text-dim);"></i>
                    <h4 style="font-size: 1.2rem; color: var(--text-main); margin-bottom: 6px;">এখনো কোনো পেমেন্ট রেকর্ড পাওয়া যায়নি</h4>
                    <p>API প্লেগ্রাউন্ড অথবা টেস্ট সেশনের মাধ্যমে আপনার প্রথম পেমেন্ট পরীক্ষা করুন।</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="table" id="tx-table">
                        <thead>
                            <tr>
                                <th>TrxID</th>
                                <th>Method</th>
                                <th>Sender Phone</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Session ID</th>
                                <th>Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $t): ?>
                                <tr>
                                    <td><strong style="font-family: monospace; color: #a78bfa; font-size: 1rem;"><?php echo htmlspecialchars($t['trx_id']); ?></strong></td>
                                    <td>
                                        <span class="mfs-pill mfs-<?php echo strtolower($t['payment_method']); ?>" style="padding: 4px 12px; font-size: 0.75rem;">
                                            <?php echo htmlspecialchars($t['payment_method']); ?>
                                        </span>
                                    </td>
                                    <td><strong style="font-family: monospace;"><?php echo htmlspecialchars($t['sender_phone']); ?></strong></td>
                                    <td><strong style="color: #34d399;">৳ <?php echo number_format($t['amount'], 2); ?></strong></td>
                                    <td>
                                        <?php if ($t['status'] === 'COMPLETED'): ?>
                                            <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> COMPLETED</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning"><i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($t['status']); ?></span>
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
        <!-- LOGIN & REGISTRATION FORM VIEW                                       -->
        <!-- =================================================================== -->
        <div style="max-width: 500px; margin: 20px auto;">
            <div class="tabs-container glass-card" style="padding: 40px; border-color: var(--border-glow);">
                
                <div class="tabs-nav" style="justify-content: center; margin-bottom: 32px;">
                    <button class="tab-btn active" data-tab="login-tab"><i class="fa-solid fa-right-to-bracket"></i> সাইন ইন (Login)</button>
                    <button class="tab-btn" data-tab="register-tab"><i class="fa-solid fa-user-plus"></i> রেজিস্ট্রেশন</button>
                </div>

                <!-- Login Tab -->
                <div id="login-tab" class="tab-content">
                    <h2 style="font-family: var(--font-heading); font-size: 1.8rem; font-weight: 800; text-align: center; margin-bottom: 8px;">মার্চেন্ট লগইন</h2>
                    <p style="text-align: center; color: var(--text-muted); font-size: 0.95rem; margin-bottom: 28px;">আপনার নিবন্ধিত ইমেইল ও পাসওয়ার্ড প্রবেশ করুন</p>

                    <form id="form-login" onsubmit="handleLogin(event)">
                        <div class="form-group">
                            <label class="form-label">ইমেইল এড্রেস</label>
                            <input type="email" id="login-email" class="form-input" placeholder="merchant@example.com" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">পাসওয়ার্ড</label>
                            <input type="password" id="login-password" class="form-input" placeholder="••••••••" required>
                        </div>
                        <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; padding: 15px; font-size: 1rem;">
                            <i class="fa-solid fa-right-to-bracket"></i> লগইন করুন
                        </button>
                    </form>
                </div>

                <!-- Register Tab -->
                <div id="register-tab" class="tab-content" style="display: none;">
                    <h2 style="font-family: var(--font-heading); font-size: 1.8rem; font-weight: 800; text-align: center; margin-bottom: 8px;">মার্চেন্ট রেজিস্ট্রেশন</h2>
                    <p style="text-align: center; color: var(--text-muted); font-size: 0.95rem; margin-bottom: 28px;">বিনামূল্যে নতুন মার্চেন্ট অ্যাকাউন্ট তৈরি করুন</p>

                    <form id="form-register" onsubmit="handleRegister(event)">
                        <div class="form-group">
                            <label class="form-label">মার্চেন্ট / প্রতিষ্ঠানের নাম</label>
                            <input type="text" id="reg-name" class="form-input" placeholder="যেমন: Apon Pay Store" required>
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
                        <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; padding: 15px; font-size: 1rem;">
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
            showToast('লগআউট সম্পন্ন করা হয়েছে', 'info');
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
                    showToast('Webhook URL আপডেট করা হয়েছে!', 'success');
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
