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

    <div style="width: 100%; max-width: 480px;">
        
        <?php if (!$sessionData): ?>
            <div class="glass-card" style="padding: 48px 32px; text-align: center;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 4rem; color: #ef4444; margin-bottom: 20px;"></i>
                <h2 style="font-family: var(--font-heading); font-size: 1.6rem; margin-bottom: 10px;">সেশন পাওয়া যায়নি!</h2>
                <p style="color: var(--text-muted); margin-bottom: 28px;">প্রদত্ত Checkout Session ID টি সঠিক নয় অথবা এটি মেয়াদ উত্তীর্ণ হয়ে গেছে।</p>
                <a href="index.php" class="btn-primary"><i class="fa-solid fa-arrow-left"></i> মূল পেজে ফিরে যান</a>
            </div>
        <?php elseif ($sessionData['status'] === 'COMPLETED'): ?>
            <div class="glass-card" style="padding: 48px 32px; text-align: center; border-color: #10b981; box-shadow: 0 0 40px rgba(16, 185, 129, 0.25);">
                <i class="fa-solid fa-circle-check" style="font-size: 4.5rem; color: #10b981; margin-bottom: 20px;"></i>
                <h2 style="font-family: var(--font-heading); font-size: 1.8rem; font-weight: 800; margin-bottom: 8px;">পেমেন্ট সফল হয়েছে!</h2>
                <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 16px;">
                    TrxID: <strong style="color: #a78bfa; font-family: monospace; font-size: 1.1rem;"><?php echo htmlspecialchars($sessionData['trx_id']); ?></strong>
                </p>
                <div style="font-family: var(--font-heading); font-size: 2.5rem; font-weight: 900; color: #34d399; margin-bottom: 28px;">
                    ৳ <?php echo number_format($sessionData['amount'], 2); ?>
                </div>
                <?php if (!empty($sessionData['redirect_url'])): ?>
                    <a href="<?php echo htmlspecialchars($sessionData['redirect_url']); ?>" class="btn-primary" style="width: 100%; justify-content: center; padding: 15px;">
                        <i class="fa-solid fa-right-from-bracket"></i> মার্চেন্ট পেজে ফিরে যান
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>

            <div class="glass-card" style="padding: 0; overflow: hidden; border-color: var(--border-glow); box-shadow: var(--shadow-glow);">
                <!-- Header Card -->
                <div style="background: linear-gradient(135deg, rgba(139,92,246,0.25), rgba(226,19,110,0.2)); padding: 28px; border-bottom: 1px solid var(--border-glass); text-align: center; position: relative;">
                    <div class="logo" style="justify-content: center; font-size: 1.4rem; margin-bottom: 6px;">AmaderPay Checkout</div>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">মার্চেন্ট: <strong style="color: white;"><?php echo htmlspecialchars($sessionData['merchant_name']); ?></strong></p>
                    
                    <div style="margin-top: 20px; background: rgba(0,0,0,0.3); border: 1px solid var(--border-glass); border-radius: var(--radius-md); padding: 16px;">
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 2px;">পরিশোধযোগ্য মোট পরিমাণ</div>
                        <div style="font-family: var(--font-heading); font-size: 2.6rem; font-weight: 900; color: #ffffff; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            ৳ <?php echo number_format($sessionData['amount'], 2); ?>
                            <button class="copy-btn" data-copy-target="pay-amount-val" style="padding: 4px 8px; font-size: 0.75rem;"><i class="fa-regular fa-copy"></i></button>
                            <input type="hidden" id="pay-amount-val" value="<?php echo number_format($sessionData['amount'], 2); ?>">
                        </div>
                    </div>
                </div>

                <div style="padding: 28px;">
                    <!-- Method Selector Badges -->
                    <label class="form-label" style="text-align: center; margin-bottom: 14px;">পেমেন্ট মেথড সিলেক্ট করুন</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 24px;">
                        <button type="button" class="btn-secondary method-btn active-method" onclick="selectMethod('BKASH', this)" style="justify-content: center; font-size: 0.9rem; border-color: var(--accent-bkash); background: rgba(226,19,110,0.15);">
                            bKash
                        </button>
                        <button type="button" class="btn-secondary method-btn" onclick="selectMethod('NAGAD', this)" style="justify-content: center; font-size: 0.9rem;">
                            Nagad
                        </button>
                        <button type="button" class="btn-secondary method-btn" onclick="selectMethod('ROCKET', this)" style="justify-content: center; font-size: 0.9rem;">
                            Rocket
                        </button>
                    </div>

                    <!-- Payment Instructions Card -->
                    <div style="background: rgba(8, 12, 24, 0.85); border: 1px solid var(--border-glass); border-radius: var(--radius-md); padding: 20px; margin-bottom: 24px; font-size: 0.9rem; line-height: 1.6;">
                        <div id="instr-method-title" style="font-weight: 800; font-size: 0.95rem; color: var(--accent-bkash); margin-bottom: 10px;">
                            <i class="fa-solid fa-circle-info"></i> bKash Send Money নির্দেশাবলী:
                        </div>
                        <ol style="padding-left: 20px; color: var(--text-muted);">
                            <li>আপনার bKash অ্যাপ খুলুন অথবা *247# ডায়াল করুন।</li>
                            <li><strong>Send Money</strong> নির্বাচন করুন।</li>
                            <li style="margin: 6px 0;">
                                মার্চেন্ট নম্বর: 
                                <strong style="color: #ffffff; font-family: monospace; font-size: 1.05rem; background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 4px;" id="merchant-num-val"><?php echo htmlspecialchars($sessionData['merchant_phone'] ?: '01700000000'); ?></strong>
                                <button class="copy-btn" data-copy-target="merchant-num-val" type="button" style="padding: 2px 6px; font-size: 0.75rem;"><i class="fa-regular fa-copy"></i> Copy</button>
                            </li>
                            <li>পরিমাণ: <strong style="color: #34d399;">৳ <?php echo number_format($sessionData['amount'], 2); ?></strong></li>
                            <li>পিন নম্বর দিয়ে সেন্ড মানি সম্পন্ন করে TrxID সংগ্রহ করুন।</li>
                        </ol>
                    </div>

                    <!-- TrxID Form -->
                    <form onsubmit="submitTrx(event)">
                        <div class="form-group">
                            <label class="form-label">আপনার প্রেরক ফোন নম্বর (Sender Mobile)</label>
                            <input type="tel" id="checkout-sender" class="form-input" placeholder="017XXXXXXXX" required style="font-family: monospace;">
                        </div>

                        <div class="form-group">
                            <label class="form-label">TrxID / Transaction ID প্রবেশ করুন</label>
                            <input type="text" id="checkout-trxid" class="form-input" placeholder="যেমন: BKASH9876543" style="font-family: monospace; text-transform: uppercase; font-weight: 800; letter-spacing: 1.5px; border-color: var(--primary);" required>
                        </div>

                        <button type="submit" id="btn-submit-pay" class="btn-primary" style="width: 100%; justify-content: center; padding: 16px; font-size: 1.05rem;">
                            <i class="fa-solid fa-shield-halved"></i> পেমেন্ট ভেরিফাই করুন
                        </button>
                    </form>
                </div>

                <div style="background: rgba(0,0,0,0.3); padding: 14px; text-align: center; font-size: 0.8rem; color: var(--text-dim); border-top: 1px solid var(--border-glass);">
                    <i class="fa-solid fa-lock" style="color: var(--accent-emerald);"></i> Encrypted 256-Bit SMS Gateway • Powered by AmaderPay
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
                b.style.background = 'rgba(255,255,255,0.04)';
                b.classList.remove('active-method');
            });
            btn.classList.add('active-method');

            const title = document.getElementById('instr-method-title');
            if (method === 'BKASH') {
                btn.style.borderColor = 'var(--accent-bkash)';
                btn.style.background = 'rgba(226,19,110,0.15)';
                title.style.color = 'var(--accent-bkash)';
                title.innerHTML = '<i class="fa-solid fa-circle-info"></i> bKash Send Money নির্দেশাবলী:';
            } else if (method === 'NAGAD') {
                btn.style.borderColor = 'var(--accent-nagad)';
                btn.style.background = 'rgba(247,147,30,0.15)';
                title.style.color = 'var(--accent-nagad)';
                title.innerHTML = '<i class="fa-solid fa-circle-info"></i> Nagad Send Money / Cash In নির্দেশাবলী:';
            } else {
                btn.style.borderColor = 'var(--accent-rocket)';
                btn.style.background = 'rgba(140,52,148,0.15)';
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
