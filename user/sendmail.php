<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php'; // PHPMailer
require_once __DIR__ . '/../db.php';              // DB 연결

/* 1) 이메일 유효성 검사 */
$email = $_POST['email'] ?? '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("유효한 이메일을 입력해주세요.");
}

/* 2) 6자리 인증번호 생성 */
$code = rand(100000, 999999);

/* 3) DB에 저장 (used=0, fail_count=0, 10분 뒤 만료) */
$expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
$stmt = $conn->prepare("
    INSERT INTO auth_codes (email, code, used, fail_count, expires_at)
    VALUES (?, ?, 0, 0, ?)
");
$stmt->bind_param("sis", $email, $code, $expires);
$stmt->execute();

/* 4) 메일 전송 */
$mail = new PHPMailer(true);
try {
    // ─ SMTP 설정 (Gmail)
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = '1.of.kknock@gmail.com';   // Gmail 주소
    $mail->Password   = 'gtogcmctfsgfwcpp';        // 앱 비밀번호(16자리)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // ─ 인코딩 설정 (한글 깨짐 방지)
    $mail->CharSet    = 'UTF-8';
    $mail->Encoding   = 'base64';

    // ─ 보내는 사람 / 받는 사람
    $mail->setFrom('1.of.kknock@gmail.com', '인증센터');
    $mail->addAddress($email);

    // ─ 메일 내용
    $mail->isHTML(true);
    $mail->Subject = '회원가입 이메일 인증번호';
    $mail->Body    = "
        <p>아래 인증번호를 10분 이내에 입력해주세요.</p>
        <h2 style='color:#333;'>{$code}</h2>
    ";

    $mail->send();
    echo "✅ 인증번호가 이메일로 전송되었습니다.";
} catch (Exception $e) {
    echo "❌ 메일 전송 실패: {$mail->ErrorInfo}";
}
?>