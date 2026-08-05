# ============================================================
#  AmaderPay APK Rebuild & Sign Script
#  Usage: .\rebuild_apk.ps1
# ============================================================

param(
    [string]$SmaliDir     = "c:\Users\kirit\OneDrive\Desktop\New folder (3)\sms",
    [string]$OutputDir    = "c:\Users\kirit\OneDrive\Desktop\New folder (3)\output",
    [string]$ApktoolPath  = "apktool",
    [string]$KeystorePath = "c:\Users\kirit\OneDrive\Desktop\New folder (3)\amaderpay.keystore"
)

Write-Host "`n══════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "  AmaderPay APK Rebuild Script" -ForegroundColor Cyan
Write-Host "══════════════════════════════════════════`n" -ForegroundColor Cyan

# ─── Step 1: Rebuild APK with Apktool ──────────────────────────────────────
Write-Host "[1/4] Rebuilding APK with Apktool..." -ForegroundColor Yellow
New-Item -ItemType Directory -Force -Path $OutputDir | Out-Null

& $ApktoolPath b $SmaliDir -o "$OutputDir\amaderpay_unsigned.apk" --use-aapt2

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Apktool rebuild failed! Check errors above." -ForegroundColor Red
    exit 1
}
Write-Host "✅ APK rebuilt: $OutputDir\amaderpay_unsigned.apk" -ForegroundColor Green

# ─── Step 2: Generate Keystore (if missing) ────────────────────────────────
Write-Host "`n[2/4] Checking keystore..." -ForegroundColor Yellow

if (-not (Test-Path $KeystorePath)) {
    Write-Host "  Keystore not found. Generating new one..." -ForegroundColor Cyan
    & keytool -genkey -v `
        -keystore $KeystorePath `
        -alias amaderpay `
        -keyalg RSA `
        -keysize 2048 `
        -validity 10000 `
        -dname "CN=AmaderPay, OU=Dev, O=Apon, L=Dhaka, S=Dhaka, C=BD" `
        -storepass amaderpay123 `
        -keypass amaderpay123

    Write-Host "✅ Keystore created: $KeystorePath" -ForegroundColor Green
    Write-Host "⚠  Store password: amaderpay123  (change this in production!)" -ForegroundColor Yellow
} else {
    Write-Host "✅ Keystore found: $KeystorePath" -ForegroundColor Green
}

# ─── Step 3: Sign with apksigner ───────────────────────────────────────────
Write-Host "`n[3/4] Signing APK..." -ForegroundColor Yellow
$signedApk = "$OutputDir\amaderpay_signed.apk"

# Try apksigner first (preferred for Android 7+)
$_apksignerCmd = Get-Command apksigner -ErrorAction SilentlyContinue
$apksignerPath = if ($_apksignerCmd) { $_apksignerCmd.Source } else { $null }
if ($apksignerPath) {
    & apksigner sign `
        --ks $KeystorePath `
        --ks-key-alias amaderpay `
        --ks-pass pass:amaderpay123 `
        --key-pass pass:amaderpay123 `
        --out $signedApk `
        "$OutputDir\amaderpay_unsigned.apk"
} else {
    Write-Host "  apksigner not found, trying jarsigner..." -ForegroundColor Cyan
    Copy-Item "$OutputDir\amaderpay_unsigned.apk" $signedApk
    & jarsigner -verbose `
        -sigalg SHA256withRSA `
        -digestalg SHA-256 `
        -keystore $KeystorePath `
        -storepass amaderpay123 `
        -keypass amaderpay123 `
        $signedApk amaderpay

    # Align with zipalign
    $_zipalignCmd = Get-Command zipalign -ErrorAction SilentlyContinue
    $zipalignPath = if ($_zipalignCmd) { $_zipalignCmd.Source } else { $null }
    if ($zipalignPath) {
        $alignedApk = "$OutputDir\amaderpay_aligned.apk"
        & zipalign -v 4 $signedApk $alignedApk
        Rename-Item $alignedApk $signedApk -Force
    }
}

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ APK signing failed!" -ForegroundColor Red
    exit 1
}
Write-Host "✅ APK signed: $signedApk" -ForegroundColor Green

# ─── Step 4: Verify ─────────────────────────────────────────────────────────
Write-Host "`n[4/4] Verifying signature..." -ForegroundColor Yellow
if ($apksignerPath) {
    & apksigner verify --verbose $signedApk
}

# ─── Summary ────────────────────────────────────────────────────────────────
Write-Host "`n══════════════════════════════════════════" -ForegroundColor Green
Write-Host "  ✅ BUILD COMPLETE!" -ForegroundColor Green
Write-Host "══════════════════════════════════════════" -ForegroundColor Green
Write-Host "  Signed APK: $signedApk" -ForegroundColor White
Write-Host "  Install:    adb install `"$signedApk`"" -ForegroundColor White
Write-Host "══════════════════════════════════════════`n" -ForegroundColor Green
