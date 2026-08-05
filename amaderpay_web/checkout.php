<?php
require_once __DIR__ . '/config/db.php';
$sessionId = $_GET['session_id'] ?? '';
$sessionData = null;

if ($sessionId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT s.*, m.name as merchant_name, m.phone as merchant_phone FROM checkout_sessions s JOIN merchants m ON s.merchant_id = m.id WHERE s.session_id = ?");
    $stmt->execute([$sessionId]);
    $sessionData = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AmaderPay Secure Checkout</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 24px;">

    <div style="width: 100%; max-width: 460px;">
        
        <?php if (!$sessionData): ?>
            <div class="glass-card" style="padding: 40px; text-align: center;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 3.5rem; color: #ef4444; margin-bottom: 16px;"></i>
                <h2 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 8px;">সেশন পাওয়া যায়নি!</h2>
                <p style="color: var(--text-muted); margin-bottom: 24px;">প্রদত্ত Checkout Session ID টি সঠিক নয় অথবা মেয়াদ উত্তীর্ণ হয়ে গেছে।</p>
                <a href="index.php" class="btn-primary">মূল পেজে ফিরে যান</a>
            </div>
        <?php elseif ($sessionData['status'] === 'COMPLETED'): ?>
            <div class="glass-card" style="padding: 40px; text-align: center; border-color: #10b981;">
                <i class="fa-solid fa-circle-check" style="font-size: 4rem; color: #10b981; margin-bottom: 16px;"></i>
                <h2 style="font-family: var(--font-heading); font-size: 1.6rem; margin-bottom: 8px;">পেমেন্ট সম্পন্ন হয়েছে!</h2>
                <p style="color: var(--text-muted); margin-bottom: 16px;">
                    TrxID: <strong style="color: #a78bfa; font-family: monospace;"><?php echo htmlspecialchars($sessionData['trx_id']); ?></strong>
                </p>
                <p style="font-size: 1.5rem; font-weight: 800; color: #34d399; margin-bottom: 24px;">
                    ৳ <?php echo number_format($sessionData['amount'], 2); ?>
                </p>
                <?php if (!empty($sessionData['redirect_url'])): ?>
                    <a href="<?php echo htmlspecialchars($sessionData['redirect_url']); ?>" class="btn-primary" style="width: 100%; justify-content: center;">মার্চেন্ট পেজে ফিরে যান</a>
                <?php endif; ?>
            </div>
        <?php else: ?>

            <div class="glass-card" style="padding: 0; overflow: hidden; border-color: var(--border-glow);">
                <!-- Header -->
                <div style="background: linear-gradient(135deg, rgba(139,92,246,0.2), rgba(226,19,110,0.15)); padding: 24px; border-bottom: 1px solid var(--border-glass); text-align: center;">
                    <div class="logo" style="justify-content: center; font-size: 1.3rem; margin-bottom: 4px;">AmaderPay Checkout</div>
                    <p style="color: var(--text-muted); font-size: 0.85rem;">মার্চেন্ট: <strong><?php echo htmlspecialchars($sessionData['merchant_name']); ?></strong></p>
                    
                    <div style="margin-top: 16px;">
                        <span style="font-size: 0.85rem; color: var(--text-muted);">পরিশোধের পরিমাণ</span>
                        <div style="font-family: var(--font-heading); font-size: 2.4rem; font-weight: 800; color: #ffffff;">
                            ৳ <?php echo number_format($sessionData['amount'], 2); ?>
                        </div>
                    </div>
                </div>

                <div style="padding: 24px;">
                    <!-- Payment Method Selector -->
                    <label class="form-label" style="text-align: center; margin-bottom: 12px;">পেমেন্ট মেথড নির্বাচন করুন</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-bottom: 24px;">
                        <button type="button" class="btn-secondary method-btn active-method" onclick="selectMethod('BKASH', this)" style="justify-content: center; font-size: 0.85rem; border-color: var(--accent-bkash);">
                            bKash
                        </button>
                        <button type="button" class="btn-secondary method-btn" onclick="selectMethod('NAGAD', this)" style="justify-content: center; font-size: 0.85rem;">
                            Nagad
                        </button>
                        <button type="button" class="btn-secondary method-btn" onclick="selectMethod('ROCKET', this)" style="justify-content: center; font-size: 0.85rem;">
                            Rocket
                        </button>
                    </div>

                    <!-- Instructions Box -->
                    <div style="background: rgba(13, 17, 23, 0.7); border: 1px solid var(--border-glass); border-radius: var(--radius-md); padding: 16px; margin-bottom: 20px; font-size: 0.88rem; line-height: 1.5;">
                        <div id="instr-method-title" style="font-weight: 700; color: var(--accent-bkash); margin-bottom: 8px;">
                            <i class="fa-solid fa-circle-info"></i> bKash Send Money নির্দেশাবলী:
                        </div>
                        <ol style="padding-left: 18px; color: var(--text-muted);">
                            <li>আপনার bKash অ্যাপ অথবা *247# ডায়াল করুন।</li>
                            <li><strong>Send Money</strong> নির্বাচন করুন।</li>
                            <li>নম্বর: <strong style="color: #ffffff; font-family: monospace; font-size: 1rem;"><?php echo htmlspecialchars($sessionData['merchant_phone'] ?: '01700000000'); ?></strong> (Personal)</li>
                            <li>পরিমাণ: <strong style="color: #34d399; font-size: 1rem;">৳ <?php echo number_format($sessionData['amount'], 2); ?></strong></li>
                            <li>রেফারেন্স ও পিন দিয়ে সেন্ড মানি সম্পন্ন করুন।</li>
                        </ol>
                    </div>

                    <!-- TrxID Submission Form -->
                    <form onsubmit="submitTrx(event)">
                        <div class="form-group">
                            <label class="form-label">আপনার প্রেরক নম্বর (Sender Mobile)</label>
                            <input type="tel" id="checkout-sender" class="form-input" placeholder="017XXXXXXXX" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">TrxID / Transaction ID প্রবেশ করুন</label>
                            <input type="text" id="checkout-trxid" class="form-input" placeholder="যেমন: BKASH12345678" style="font-family: monospace; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;" required>
                        </div>

                        <button type="submit" id="btn-submit-pay" class="btn-primary" style="width: 100%; justify-content: center; padding: 14px; font-size: 1rem;">
                            <i class="fa-solid fa-shield-halved"></i> পেমেন্ট ভেরিফাই করুন
                        </button>
                    </form>
                </div>

                <div style="background: rgba(0,0,0,0.2); padding: 12px; text-align: center; font-size: 0.75rem; color: var(--text-dim); border-top: 1px solid var(--border-glass);">
                    <i class="fa-solid fa-lock"></i> Secured by AmaderPay 256-bit Encrypted SMS Gateway
                </div>
            </div>

        <?php endif; ?>

    </div>

    <script src="assets/js/main.js"></script>
    <script>
        let currentMethod = 'BKASH';
        const sessionId = "<?php echo htmlspecialchars($sessionId); ?>";

        function selectMethod(method, btn) {
            currentMethod = method;
            document.querySelectorAll('.method-btn').forEach(b => {
                b.style.borderColor = 'var(--border-glass)';
                b.classList.remove('active-method');
            });
            btn.classList.add('active-method');

            const title = document.getElementById('instr-method-title');
            if (method === 'BKASH') {
                btn.style.borderColor = 'var(--accent-bkash)';
                title.style.color = 'var(--accent-bkash)';
                title.innerHTML = '<i class="fa-solid fa-circle-info"></i> bKash Send Money নির্দেশাবলী:';
            } else if (method === 'NAGAD') {
                btn.style.borderColor = 'var(--accent-nagad)';
                title.style.color = 'var(--accent-nagad)';
                title.innerHTML = '<i class="fa-solid fa-circle-info"></i> Nagad Send Money / Cash In নির্দেশাবলী:';
            } else {
                btn.style.borderColor = 'var(--accent-rocket)';
                title.style.color = 'var(--accent-rocket)';
                title.innerHTML = '<i class="fa-solid fa-circle-info"></i> Rocket Send Money নির্দেশাবলী:';
            }
        }

        async function submitTrx(e) {
            e.preventDefault();
            const sender = document.getElementById('checkout-sender').value;
            const trxId = document.getElementById('checkout-trxid').value;
            const submitBtn = document.getElementById('btn-submit-pay');

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ভেরিফাই করা হচ্ছে...';

            try {
                const res = await fetch('api/checkout.php?action=submit_trx', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        session_id: sessionId,
                        trx_id: trxId,
                        payment_method: currentMethod,
                        sender_phone: sender
                    })
                });
                const data = await res.json();
                if (data.success) {
                    showToast('পেমেন্ট সফলভাবে ভেরিফাই হয়েছে!', 'success');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showToast(data.message || 'ভেরিফিকেশন ব্যর্থ হয়েছে!', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-solid fa-shield-halved"></i> পেমেন্ট ভেরিফাই করুন';
                }
            } catch (err) {
                showToast('সার্ভার রেসপন্স দিতে পারছে না', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-shield-halved"></i> পেমেন্ট ভেরিফাই করুন';
            }
        }
    </script>
</body>
</html>
