function generateToken($length = 6) {
    return str_pad(rand(0, 999999), $length, "0", STR_PAD_LEFT);
}

function saveTokenToDB($conn, $email, $token) {
    $stmt = $conn->prepare("INSERT INTO email_tokens (email, token) VALUES (?, ?)");
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
}
