<?php
session_start();
$conn = new mysqli("localhost", "root", "", "user");
$conn->set_charset("utf8mb4");
if ($conn->connect_error) {
    die("連線失敗：" . $conn->connect_error);
}

$user_id = $_SESSION['user_id'] ?? null;
$is_admin = false;
$is_helper = false;

// 取得目前登入者身份
if ($user_id !== null) {
    $stmt = $conn->prepare("SELECT is_admin, is_helper FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();
    if ($user) {
        $is_admin = (bool)$user['is_admin'];
        $is_helper = (bool)$user['is_helper'];
    }
}

$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
if ($post_id === 0) {
    die("找不到貼文ID");
}

// 取得貼文及作者
$stmt = $conn->prepare("
    SELECT p.content, p.created_at, u.username
    FROM post p 
    JOIN users u ON p.user_id = u.id 
    WHERE p.id = ?
");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post_res = $stmt->get_result();
$post = $post_res->fetch_assoc();
$stmt->close();
if (!$post) {
    die("找不到該貼文");
}

// 新增留言
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content'])) {
    if ($user_id === null) die("請先登入才能留言");
    $comment = trim($_POST['comment_content']);
    if ($comment === '') die("留言不可為空");
    $stmt = $conn->prepare("INSERT INTO comment (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $post_id, $user_id, $comment);
    $stmt->execute();
    $stmt->close();
    header("Location: comment.php?post_id=$post_id");
    exit;
}

// 刪除留言（本人、小幫手、管理員可刪）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment_id'])) {
    $delete_comment_id = (int)$_POST['delete_comment_id'];
    if ($user_id === null) die("請先登入才能刪除留言");
    $stmt = $conn->prepare("SELECT user_id FROM comment WHERE id = ?");
    $stmt->bind_param("i", $delete_comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $comment_owner = $result->fetch_assoc();
    $stmt->close();

    if ($comment_owner && ($comment_owner['user_id'] == $user_id || $is_admin || $is_helper)) {
        $stmt = $conn->prepare("DELETE FROM comment WHERE id = ?");
        $stmt->bind_param("i", $delete_comment_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: comment.php?post_id=$post_id");
    exit;
}

// 讀留言
$comment_stmt = $conn->prepare("
    SELECT c.content, c.created_at, u.username, u.is_helper, u.is_admin, c.user_id, c.id 
    FROM comment c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.post_id = ? 
    ORDER BY c.created_at ASC
");
$comment_stmt->bind_param("i", $post_id);
$comment_stmt->execute();
$comment_res = $comment_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
<meta charset="UTF-8" />
<title>貼文留言 - 九大行星論壇</title>
<style>
  body {
     font-family: sans-serif; 
     max-width: 700px; 
     margin: 20px auto;
     background: #f9f9f9; 
     padding: 10px; 
  }
  .post, .comment { 
    background: white; 
    padding: 15px; 
    border-radius: 10px;
    margin-bottom: 15px; 
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
  }
  .username { 
    font-weight: bold;
  }
  .helper-tag, .admin-tag { 
    font-size: 12px; 
    border-radius: 4px;
    padding: 2px 4px;
    margin-left: 5px;
  }
  .helper-tag {
    color: #007bff;
    border: 1px solid #007bff;
  }
  .admin-tag {
    color: #c00;
    border: 1px solid #c00;
  }
  .timestamp { 
    color: #888; 
    font-size: 12px;
    margin-left: 5px; 
  }
  textarea { 
    width: 100%;
    min-height: 60px;
    padding: 10px;
    border-radius: 8px; 
    border: 1px solid #ccc; 
    resize: vertical; 
  }
  button { 
    margin-top: 8px;
    padding: 8px 16px;
    border: none; 
    background: #111; 
    color: white; 
    border-radius: 6px; 
    cursor: pointer; 
  }
  .delete-btn { 
    background: #c33; 
    padding: 4px 8px; 
    font-size: 12px; 
    margin-left: 10px;
  }
  a { color: #111; text-decoration: none; }
</style>
</head>
<body>

<a href="forum.php">&larr; 返回論壇</a>

<div class="post">
  <span class="username"><?=htmlspecialchars($post['username'])?></span>
  <span class="timestamp"><?=htmlspecialchars($post['created_at'])?></span>
  <div style="margin-top:10px;"><?=nl2br(htmlspecialchars($post['content']))?></div>
</div>

<h3>留言區</h3>
<?php while ($comment = $comment_res->fetch_assoc()): ?>
  <div class="comment">
    <span class="username">
      <?=htmlspecialchars($comment['username'])?>
      <?php if ($comment['is_admin']): ?>
        <span class="admin-tag">管理員</span>
      <?php elseif ($comment['is_helper']): ?>
        <span class="helper-tag">小幫手</span>
      <?php endif; ?>
    </span>
    <span class="timestamp"><?=htmlspecialchars($comment['created_at'])?></span>
    <div style="margin-top:5px; display: inline-block;"><?=nl2br(htmlspecialchars($comment['content']))?></div>
    <?php if ($user_id !== null && ($comment['user_id'] == $user_id || $is_admin || $is_helper)): ?>
      <form method="post" style="display:inline;">
        <input type="hidden" name="delete_comment_id" value="<?= $comment['id'] ?>">
        <button type="submit" class="delete-btn" onclick="return confirm('確定要刪除此留言嗎？')">刪除</button>
      </form>
    <?php endif; ?>
  </div>
<?php endwhile; ?>
<?php $comment_stmt->close(); ?>

<?php if ($user_id !== null): ?>
  <form method="post">
    <textarea name="comment_content" placeholder="留言..." required></textarea>
    <button type="submit">送出留言</button>
  </form>
<?php else: ?>
  <p>請 <a href="login.php">登入</a> 後才能留言。</p>
<?php endif; ?>

</body>
</html>
