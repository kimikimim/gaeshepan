<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: /user/login.php");
    exit;
}
require_once "../user/auth_check.php";   
require_once "../db.php";             
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}   

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if (
      !isset($_POST['csrf_token']) ||
      !isset($_SESSION['csrf_token']) ||
      $_POST['csrf_token'] !== $_SESSION['csrf_token']
  ) {
      die("⚠️ CSRF 토큰 검증 실패");
  }
}

$board_id = 1;                           

$title   = trim($_GET['title']  ?? '');
$author  = trim($_GET['author'] ?? '');
$order   = ($_GET['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

$sql    = "
  SELECT p.id, p.title, p.created_at, u.username
    FROM board1_posts  p
    JOIN users u ON p.user_id = u.id
   WHERE p.board_id = ?
";
$types  = "i";
$params = [$board_id];

if ($title !== '') {
    $sql .= " AND p.title   LIKE ?";
    $params[] = "%$title%";  $types .= "s";
}
if ($author !== '') {
    $sql .= " AND u.username LIKE ?";
    $params[] = "%$author%"; $types .= "s";
}
$sql .= " ORDER BY p.id $order";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$total = $result->num_rows;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Board 1 - 목록</title>
<style>
    body          {font-family:system-ui, sans-serif; margin:0; background:#f5f7fb;}
    .wrap         {max-width:900px; margin:40px auto; background:#fff; padding:24px 32px; box-shadow:0 4px 10px rgba(0,0,0,0.08);}
    h2            {margin:0 0 18px;}
    .toolbar      {display:flex; flex-wrap:wrap; gap:10px; margin-bottom:18px; align-items:center;}
    .toolbar form {display:flex; gap:6px; flex-wrap:wrap;}
    .toolbar input[type=text]{padding:6px 8px; border:1px solid #bbb; border-radius:4px;}
    .btn          {padding:6px 12px; border:1px solid #4676ff; background:#4676ff; color:#fff; border-radius:4px; text-decoration:none; font-size:14px;}
    .btn-outline  {background:#fff; color:#4676ff;}
    .btn:hover    {opacity:.9;}
    table         {width:100%; border-collapse:collapse; font-size:14px;}
    th,td         {padding:10px 8px; text-align:center;}
    th            {background:#f1f3f8; border-bottom:2px solid #dcdfe6;}
    tr:nth-child(even){background:#f9fafc;}
    tr:hover      {background:#eef2ff;}
    td.title      {text-align:left;}
    a.title-link  {color:#222; text-decoration:none;}
    a.title-link:hover{ text-decoration:underline; }
    .bottom       {display:flex; justify-content:flex-end; gap:8px; margin-top:18px;}
    @media (max-width:600px){
        .toolbar form{flex-direction:column;}
        th:nth-child(4), td:nth-child(4){display:none;} /* 작성일 모바일에서 숨김 */
    }
</style>
</head>
<body>
<div class="wrap">
  <h2>📂 게시판 1</h2>

  <div class="toolbar">
    <form method="get">
      <input type="text" name="title"  placeholder="제목"   value="<?= htmlspecialchars($title)  ?>">
      <input type="text" name="author" placeholder="작성자" value="<?= htmlspecialchars($author) ?>">
      <button class="btn" type="submit">검색</button>
    </form>

    <a class="btn btn-outline"
       href="?<?= http_build_query(array_merge($_GET, ['order'=>'desc'])) ?>">최신순</a>
    <a class="btn btn-outline"
       href="?<?= http_build_query(array_merge($_GET, ['order'=>'asc' ])) ?>">오래된순</a>

    <div style="margin-left:auto; display:flex; gap:8px;">
      <a class="btn btn-outline" href="../user/logout.php">로그아웃</a>
      <a class="btn"            href="write.php">글쓰기</a>
    </div>
  </div>

  <table>
    <tr>
      <th>No</th><th>제목</th><th>작성자</th><th>작성일</th>
    </tr>
    <?php
      $no = ($order === 'DESC') ? $total : 1;
      while ($row = $result->fetch_assoc()):
    ?>
    <tr>
      <td><?= $no ?></td>
      <td class="title">
          <a class="title-link" href="view.php?id=<?= $row['id'] ?>">
              <?= htmlspecialchars($row['title']) ?>
          </a>
      </td>
      <td><?= htmlspecialchars($row['username']) ?></td>
      <td><?= substr($row['created_at'], 0, 10) ?></td>
    </tr>
    <?php
        $order === 'DESC' ? $no-- : $no++;
      endwhile;
    ?>
  </table>

  <div class="bottom">
    <a class="btn" href="write.php">글쓰기</a>
  </div>
</div>
</body>
</html>