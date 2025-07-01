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

$id = $_GET['id'] ?? '';
if (!$id) {
    echo "❌ 잘못된 접근입니다.";
    exit;
}

$stmt = $conn->prepare("SELECT p.*, u.username FROM board1_posts p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();
$stmt->close();

if (!$post) {
    echo "❌ 게시글을 찾을 수 없습니다.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($post['title']) ?></title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f9f9f9;
      margin: 0;
      padding: 30px;
    }
    .container {
      max-width: 800px;
      margin: 0 auto;
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    h2 {
      margin-bottom: 10px;
      color: #2c3e50;
    }
    .meta {
      color: #777;
      font-size: 14px;
      margin-bottom: 25px;
    }
    .content {
      margin-bottom: 30px;
      font-size: 16px;
      line-height: 1.6;
      white-space: pre-line;
    }
    .file-download-box {
      background-color: #f2f5f8;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 25px;
      font-size: 15px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .download-btn {
      background-color: #3498db;
      color: white;
      padding: 8px 16px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: bold;
      transition: background-color 0.2s;
    }
    .download-btn:hover {
      background-color: #2980b9;
    }
    .action-buttons {
      margin-bottom: 30px;
    }
    .action-buttons a button {
      background-color: #1e90ff;
      color: white;
      border: none;
      padding: 10px 16px;
      border-radius: 8px;
      margin-right: 10px;
      font-size: 14px;
      cursor: pointer;
    }
    .action-buttons a button:hover {
      background-color: #006ad1;
    }
    .back-link {
      display: inline-block;
      margin-top: 10px;
      color: purple;
      text-decoration: none;
      font-weight: bold;
    }
    .back-link:hover {
      text-decoration: underline;
    }
    .comment-section {
      margin-top: 40px;
    }
    .comment-section h3 {
      margin-bottom: 10px;
    }
    .comment-box {
      display: flex;
      margin-top: 10px;
      gap: 10px;
    }
    .comment-box input[type="text"] {
      flex: 1;
      padding: 10px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }
    .comment-box button {
      padding: 10px 16px;
      background-color: #555;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }
    .comment-box button:hover {
      background-color: #333;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2><?= htmlspecialchars($post['title']) ?></h2>
    <div class="meta">작성자: <?= htmlspecialchars($post['username']) ?> | 작성일: <?= $post['created_at'] ?></div>
    <div class="content"><?= nl2br(htmlspecialchars($post['content'])) ?></div>

    <?php if ($post["filename"]): ?>
      <div class="file-download-box">
        📎 첨부 파일:
        <a href="download.php?id=<?= $post["id"] ?>" class="download-btn">
          <?= htmlspecialchars($post["filename"]) ?>
        </a>
      </div>
    <?php endif; ?>

    <?php
    $current_user_id = $_SESSION["user_id"] ?? null;
    $is_admin = $_SESSION["is_admin"] ?? 0;
    ?>

    <?php if ($current_user_id === $post["user_id"] || $is_admin): ?>
      <div class="action-buttons">
        <a href="edit.php?id=<?= $post['id'] ?>"><button>수정</button></a>
        <a href="delete.php?id=<?= $post['id'] ?>" onclick="return confirm('정말 삭제하시겠습니까?')">
          <button>삭제</button>
        </a>
      </div>
    <?php endif; ?>

    <a href="list.php" class="back-link">← 목록으로</a>

    <!-- 댓글 작성 및 출력 -->
    <div class="comment-section">
      <h3>💬 댓글</h3>

      <form method="post" action="comment_insert.php">
        <input type="hidden" name="post_id" value="<?= $post["id"] ?>">
        <div class="comment-box">
          <input type="text" name="comment" placeholder="댓글을 입력하세요" required>
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <button type="submit">댓글 작성</button>
        </div>
      </form>

      <div class="comment-list" style="margin-top: 20px;">
        <?php
        $stmt = $conn->prepare("SELECT c.*, u.username FROM board1_comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? ORDER BY c.created_at ASC");
        $stmt->bind_param("i", $post['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($comment = $result->fetch_assoc()):
        ?>
          <div class="comment-item" style="padding: 10px 0; border-bottom: 1px solid #eee;">
            <strong><?= htmlspecialchars($comment['username']) ?></strong>
            <span style="color: gray; font-size: 12px;">(<?= $comment['created_at'] ?>)</span>
            <div style="margin: 5px 0;"><?= nl2br(htmlspecialchars($comment['content'])) ?></div>

            <?php if ($_SESSION['user_id'] == $comment['user_id'] || ($_SESSION['is_admin'] ?? false)): ?>
              <div style="margin-top: 5px;">
                <form method="post" action="comment_delete.php" style="display:inline;" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                  <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                  <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                  <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                  <button type="submit" style="font-size:12px; color: red; border: none; background: none; cursor: pointer;">삭제</button>
                </form>
                <form method="get" action="comment_edit.php" style="display:inline;">
                  <input type="hidden" name="id" value="<?= $comment['id'] ?>">
                  <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                  <button type="submit" style="font-size:12px; color: blue; border: none; background: none; cursor: pointer;">수정</button>
                </form>
              </div>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
  </div>
</body>
</html>