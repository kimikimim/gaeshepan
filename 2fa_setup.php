<?php
require_once __DIR__ . '/vendor/autoload.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$ga = new PHPGangsta_GoogleAuthenticator();
$secret = $ga->createSecret();
$_SESSION['2fa_secret'] = $secret; // 세션에 저장

$qrCodeUrl = $ga->getQRCodeGoogleUrl('Admin2FA@MySite', $secret);

echo "<h2>📱 Google OTP 설정</h2>";
echo "<p>아래 QR코드를 Google Authenticator로 스캔하세요:</p>";
echo "<img src='" . htmlspecialchars($qrCodeUrl) . "'><br><br>";
echo "<form method='POST' action='verify_otp.php'>
        <label>OTP 입력: <input type='text' name='otp' required></label>
        <button type='submit'>확인</button>
      </form>";
?>
