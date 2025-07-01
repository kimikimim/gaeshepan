<?php
require_once "../db.php";
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require_once "../user/auth_check.php"; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if (
      !isset($_POST['csrf_token']) ||
      !isset($_SESSION['csrf_token']) ||
      $_POST['csrf_token'] !== $_SESSION['csrf_token']
  ) {
      die("⚠️ CSRF 토큰 검증 실패");
  }
}

$id = (int)($_GET["id"] ?? 0);

$stmt = $conn->prepare("SELECT * FROM board1_posts WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

$current_user_id = $_SESSION["user_id"];
$is_admin = $_SESSION["is_admin"] ?? 0;

if ($comment["user_id"] != $current_user_id && !$is_admin) {
    die("❌ 권한이 없습니다.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $title   = $_POST["title"];
  $content = $_POST["content"];

  $filename  = $post["filename"];
  $save_name = $post["save_name"];

  if (!empty($_FILES["upload"]["name"])) {
      $original_name = basename($_FILES["upload"]["name"]);
      $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
      $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt'];

      if (!in_array($ext, $allowed_ext)) {
          die("❌ 업로드할 수 없는 파일 형식입니다.");
      }

      $unique_name = uniqid("upload_", true) . "." . $ext;
      $upload_path = __DIR__ . "/uploads/" . $unique_name;

      if (!move_uploaded_file($_FILES["upload"]["tmp_name"], $upload_path)) {
          die("❌ 파일 업로드 실패");
      }

      $filename  = $original_name;
      $save_name = $unique_name;
  }

  $u = $conn->prepare("UPDATE board1_posts SET title=?, content=?, filename=?, save_name=?, updated_at=NOW() WHERE id=?");
  $u->bind_param("ssssi", $title, $content, $filename, $save_name, $id);
  $u->execute();

  header("Location: view.php?id=$id");
  exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>글 수정</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f8f9fa;
      margin: 0;
      padding: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      min-height: 100vh;
    }

    h2 {
      color: #343a40;
      margin-top: 40px;
    }

    form {
      background: white;
      padding: 25px;
      border-radius: 10px;
      width: 90%;
      max-width: 600px;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
      margin-top: 20px;
    }

    label {
      display: block;
      margin-top: 15px;
      margin-bottom: 5px;
      font-weight: bold;
    }

    input[type="text"],
    textarea,
    input[type="file"] {
      width: 100%;
      padding: 10px;
      border: 1px solid #ced4da;
      border-radius: 6px;
      font-size: 1em;
      box-sizing: border-box;
    }

    button {
      margin-top: 20px;
      background: #007bff;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      font-size: 1em;
      cursor: pointer;
    }

    button:hover {
      background: #0056b3;
    }

    .current-file {
      margin-top: 10px;
      font-size: 0.9em;
      color: #495057;
    }

    .link-back {
      margin-top: 15px;
      text-align: center;
    }

    .link-back a {
      text-decoration: none;
      color: #007bff;
    }
  </style>
</head>
<body>

<h2>✏️ 글 수정</h2>

<form method="post" enctype="multipart/form-data">
  <label>제목</label>
  <input type="text" name="title" value="<?= htmlspecialchars($post["title"]) ?>" required>

  <label>내용</label>
  <textarea name="content" rows="10" required><?= htmlspecialchars($post["content"]) ?></textarea>

  <?php if ($post["filename"]): ?>
    <div class="current-file">📎 현재 파일: <?= htmlspecialchars($post["filename"]) ?></div>
  <?php endif; ?>

  <label>파일 교체</label>
  <input type="file" name="upload">
  <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
  <button>수정 완료</button>
</form>

<div class="link-back">
  <a href="view.php?id=<?= $id ?>">취소</a>
</div>

</body>
</html>