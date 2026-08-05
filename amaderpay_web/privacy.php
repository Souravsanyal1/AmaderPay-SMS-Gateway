<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>প্রাইভেসি পলিসি - AmaderPay</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- Header -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="index.php" class="logo">
                AmaderPay <span class="logo-badge">PRIVACY</span>
            </a>
            <ul class="nav-links">
                <li><a href="index.php" class="nav-link">হোম</a></li>
                <li><a href="docs.php" class="nav-link">API Docs</a></li>
                <li><a href="merchant.php" class="nav-link">মার্চেন্ট পোর্টাল</a></li>
                <li><a href="privacy.php" class="nav-link active">প্রাইভেসি পলিসি</a></li>
            </ul>
        </div>
    </nav>

    <div class="container" style="padding: 60px 24px; max-width: 800px;">
        <div class="glass-card" style="padding: 40px;">
            <h1 style="font-family: var(--font-heading); font-size: 2rem; margin-bottom: 20px;">গোপনীয়তা নীতি (Privacy Policy)</h1>
            <p style="color: var(--text-muted); margin-bottom: 24px;">সর্বশেষ আপডেট: ৫ আগস্ট, ২০২৬</p>

            <h3 style="font-size: 1.2rem; margin-bottom: 8px;">১. আমরা কী ধরনের তথ্য সংগ্রহ করি</h3>
            <p style="color: var(--text-muted); line-height: 1.6; margin-bottom: 20px;">
                AmaderPay শুধুমাত্র পেমেন্ট ভেরিফিকেশনের সুবিধার্থে পেমেন্ট প্রদানকারী মোবাইল ফাইন্যান্সিয়াল সার্ভিস (bKash, Nagad, Rocket) থেকে প্রাপ্ত পেমেন্ট সংক্রান্ত SMS তথ্য (যেমন: TrxID, প্রেরকের ফোন নম্বর, টাকার পরিমাণ) প্রসেস করে।
            </p>

            <h3 style="font-size: 1.2rem; margin-bottom: 8px;">২. তথ্যের ব্যবহার ও নিরাপত্তা</h3>
            <p style="color: var(--text-muted); line-height: 1.6; margin-bottom: 20px;">
                মার্চেন্টের নিবন্ধিত Device Key এবং API Key এর মাধ্যমে সংগৃহীত তথ্য এনক্রিপ্ট অবস্থায় প্রসেস করা হয়। কোনো প্রকার ব্যক্তিগত বা সংবেদনশীল SMS তথ্য তৃতীয় পক্ষের সাথে শেয়ার করা হয় না।
            </p>

            <h3 style="font-size: 1.2rem; margin-bottom: 8px;">৩. অ্যান্ড্রয়েড পারমিশন ব্যবহার</h3>
            <p style="color: var(--text-muted); line-height: 1.6; margin-bottom: 20px;">
                আমাদের Android SMS Gateway অ্যাপটি শুধুমাত্র <code>RECEIVE_SMS</code> ও <code>READ_SMS</code> পারমিশন নিয়ে ব্যাকগ্রাউন্ডে ইনকামিং পেমেন্ট বার্তা পর্যবেক্ষণ করে।
            </p>

            <div style="margin-top: 32px; border-top: 1px solid var(--border-glass); padding-top: 20px;">
                <a href="index.php" class="btn-primary"><i class="fa-solid fa-arrow-left"></i> হোমপেজে ফিরে যান</a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer" style="margin-top: auto;">
        <div class="container footer-bottom">
            &copy; <?php echo date('Y'); ?> AmaderPay Gateway. All rights reserved.
        </div>
    </footer>

</body>
</html>
