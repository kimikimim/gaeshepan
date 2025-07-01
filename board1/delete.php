<?php
session_start();
require_once "../db.php";
$id = (int)($_GET["id"] ?? 0);

$conn->query("DELETE FROM board1_comments WHERE post_id = $id");
$conn->query("DELETE FROM board1_posts    WHERE id = $id");

header("Location: list.php");
?>
<?php
session_start();
require_once "../db.php";
require_once "../user/auth_check.php";  // 로그인 여부 확인 (보안 강화)

// CSRF 토큰 검증 (POST로 변경하고 확인 필요)
if ($_SERVER["REQUEST_METHOD"] !== "POST" ||
    !isset($_POST["csrf_token"]) ||
    $_POST["csrf_token"] !== ($_SESSION["csrf_token"] ?? '')
) {
    die("⚠️ 잘못된 요청");
}

$id = (int)($_POST["id"] ?? 0);

// 게시글 확인 및 권한 검증
$stmt = $conn->prepare("SELECT * FROM board1_posts WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

$current_user_id = $_SESSION["user_id"] ?? 0;
$is_admin = $_SESSION["is_admin"] ?? 0;
if ($post["user_id"] != $current_user_id && !$is_admin) {
    die("❌ 삭제 권한이 없습니다.");
}

// 댓글 삭제
/*$stmt = $conn->prepare("DELETE FROM board1_comments WHERE post_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();*/

// 게시글 삭제
$stmt = $conn->prepare("DELETE FROM board1_posts WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: list.php");
exit;