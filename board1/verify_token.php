use PHPMailer\PHPMailer\PHPMailer;

require 'vendor/autoload.php';  

function sendTokenEmail($email, $token) {
    $mail = new PHPMailer();
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; 
    $mail->SMTPAuth = true;
    $mail->Username = 'your_gmail@gmail.com';
    $mail->Password = '앱 비밀번호';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('your_gmail@gmail.com', '인증 시스템');
    $mail->addAddress($email);
    $mail->Subject = '이메일 인증번호';
    $mail->Body    = "당신의 인증번호는: $token 입니다.";

    return $mail->send();
}
