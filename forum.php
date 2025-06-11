<?php
session_start();
$conn1 = new mysqli("localhost", "root", "", "user");
$conn1->set_charset("utf8mb4");
if ($conn1->connect_error) die("連線失敗：" . $conn1->connect_error);

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;
$is_admin = 0;
$is_helper = 0;

if ($user_id) {
    $result = $conn1->query("SELECT is_admin, is_helper FROM users WHERE id = $user_id");
    if ($result && $row = $result->fetch_assoc()) {
        $is_admin = (int)$row['is_admin'];
        $is_helper = (int)$row['is_helper'];
    }
}

// 處理按讚 AJAX 請求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_post_id']) && $user_id) {
    $post_id = (int)$_POST['like_post_id'];
    $check = $conn1->query("SELECT * FROM likes WHERE post_id = $post_id AND user_id = $user_id");
    if ($check->num_rows > 0) {
        $conn1->query("DELETE FROM likes WHERE post_id = $post_id AND user_id = $user_id");
    } else {
        $conn1->query("INSERT INTO likes (post_id, user_id) VALUES ($post_id, $user_id)");
    }
    $count = $conn1->query("SELECT COUNT(*) AS c FROM likes WHERE post_id = $post_id")->fetch_assoc()['c'];
    header('Content-Type: application/json');
    echo json_encode(['like_count' => $count]);
    exit;
}

// 發文
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_content'])) {
    if ($user_id === null) die("請先登入才能發文");
    $content = trim($_POST['post_content']);
    if ($content === '') die("內容不可為空");
    $stmt = $conn1->prepare("INSERT INTO post (user_id, content, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $user_id, $content);
    $stmt->execute();
    $stmt->close();
    header("Location: forum.php");
    exit;
}

// 刪除貼文
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post_id'])) {
    $pid = (int)$_POST['delete_post_id'];
    $check = $conn1->query("SELECT user_id FROM post WHERE id = $pid")->fetch_assoc();
    if (!$check || ($check['user_id'] != $user_id && !$is_admin && !$is_helper)) die("無權刪除貼文");
    $conn1->query("DELETE FROM post WHERE id = $pid");
    header("Location: forum.php");
    exit;
}

// 編輯貼文
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_post_id'], $_POST['edit_post_content'])) {
    $pid = (int)$_POST['edit_post_id'];
    $text = trim($_POST['edit_post_content']);
    if ($text === '') die("不可為空");
    $check = $conn1->query("SELECT user_id FROM post WHERE id = $pid")->fetch_assoc();
    if (!$check || $check['user_id'] != $user_id) die("無權編輯");
    $stmt = $conn1->prepare("UPDATE post SET content=? WHERE id=?");
    $stmt->bind_param("si", $text, $pid);
    $stmt->execute();
    $stmt->close();
    header("Location: forum.php");
    exit;
}

// 刪除留言 (小幫手或管理員)
if (($is_admin || $is_helper) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment_id'])) {
    $cid = (int)$_POST['delete_comment_id'];
    $conn1->query("DELETE FROM comment WHERE id = $cid");
    header("Location: forum.php");
    exit;
}

// 新增留言
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_post_id'], $_POST['comment_content'])) {
    if ($user_id === null) die("請先登入才能留言");
    $post_id = (int)$_POST['comment_post_id'];
    $comment_content = trim($_POST['comment_content']);
    if ($comment_content === '') die("留言內容不可為空");
    $stmt = $conn1->prepare("INSERT INTO comment (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $post_id, $user_id, $comment_content);
    $stmt->execute();
    $stmt->close();
    header("Location: forum.php#comments-$post_id");
    exit;
}

// 刪除使用者(管理員)
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $uid = (int)$_POST['delete_user_id'];
    $conn1->query("DELETE FROM comment WHERE user_id = $uid");
    $conn1->query("DELETE FROM post WHERE user_id = $uid");
    $conn1->query("DELETE FROM likes WHERE user_id = $uid");
    $conn1->query("DELETE FROM users WHERE id = $uid");
    header("Location: forum.php");
    exit;
}

// 切換小幫手身分(管理員)
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_helper_id'])) {
    $uid = (int)$_POST['toggle_helper_id'];
    $conn1->query("UPDATE users SET is_helper = 1 - is_helper WHERE id = $uid AND is_admin = 0");
    header("Location: forum.php");
    exit;
}

$editing_post_id = $_GET['edit_post_id'] ?? 0;

$posts = $conn1->query("
    SELECT p.*, u.username,
    (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count,
    (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = $user_id) AS liked_by_me
    FROM post p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC
");

$users = ($is_admin || $is_helper) ? $conn1->query("SELECT id, username, is_helper, is_admin FROM users") : null;
if ($users === false && ($is_admin || $is_helper)) {
    die("查詢使用者失敗：" . $conn1->error);
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
  <meta charset="UTF-8" />
  <title>九大行星論壇</title>
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0; padding: 0; display: flex;
      font-family: "Segoe UI", sans-serif;
      background: #f3f3f3;
    }
    .sidebar-left {
      width: 350px; background: #fff; padding: 20px;
      border-right: 1px solid #ddd;
      height: 100vh; overflow-y: auto;
    }
    .container {
      flex: 1;
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
      background: #fff;
    }
    .user-item {
      margin-bottom: 16px;
      padding: 8px; border: 1px solid #eee;
      border-radius: 8px;
    }
    .user-actions form {
      display: inline;
    }
    h2 { margin-top: 0; }
    .logout-link { float: right; font-size: 14px; }

    textarea {
      width: 100%; padding: 10px;
      border-radius: 8px; border: 1px solid #ccc;
      resize: vertical; font-size: 14px;
    }
    button {
      padding: 6px 12px;
      border: none; border-radius: 6px;
      background: #333; color: #fff;
      cursor: pointer; margin-top: 5px;
    }
    .post-card {
      display: flex; padding: 15px 0;
      border-bottom: 1px solid #eee;
      flex-direction: column;
    }
    .content { flex: 1; }
    .username { font-weight: bold; }
    .timestamp { color: #999; font-size: 12px; margin-left: 6px; }
    .actions {
      margin-top: 5px;
      font-size: 14px; color: #555;
    }
    .actions a, .actions form, .actions button {
      margin-right: 10px; display: inline-block;
      text-decoration: none; color: #333;
      background: none; border: none;
      cursor: pointer; font-size: 14px;
    }

    .like-btn {
      border: none;
      background: none;
      cursor: pointer;
      font-size: 14px;
      color: #e0245e;
      user-select: none;
      margin-right: 10px;
    }
    .like-btn.liked {
      font-weight: bold;
      color: #b81d4f;
    }
  </style>
</head>
<body>

<?php if ($is_admin || $is_helper): ?>
<div class="sidebar-left">
  <h3>🛠 <?= $is_admin ? '管理中心' : '小幫手區' ?></h3>
  <?php while($u = $users->fetch_assoc()): ?>
    <div class="user-item">
      <strong><?= htmlspecialchars($u['username']) ?></strong>

      <?php if ($u['is_admin']): ?>
        <span style="color:#c00;">(管理員)</span>
      <?php elseif ($u['is_helper']): ?>
        <span style="color:#080;">(小幫手)</span>
      <?php else: ?>
        <span style="color:#555;">(一般使用者)</span>
      <?php endif; ?>

      <div class="user-actions" style="margin-top:6px;">
        <?php if ($is_admin && !$u['is_admin']): ?>
          <form method="post" style="display:inline;">
            <input type="hidden" name="toggle_helper_id" value="<?= $u['id'] ?>">
            <button type="submit"><?= $u['is_helper'] ? '移除小幫手' : '設為小幫手' ?></button>
          </form>
          <form method="post" style="display:inline;" onsubmit="return confirm('確定刪除使用者 <?= htmlspecialchars($u['username']) ?>？');">
            <input type="hidden" name="delete_user_id" value="<?= $u['id'] ?>">
            <button type="submit" style="background:#c00;">刪除帳號</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  <?php endwhile; ?>
</div>
<?php endif; ?>

<div class="container">
  <h2>九大行星論壇</h2>
  <?php if ($user_id): ?>
    <div style="margin-bottom: 20px;">
      您好，<strong>
      <?php
      $uinfo = $conn1->query("SELECT username FROM users WHERE id = $user_id")->fetch_assoc();
      echo htmlspecialchars($uinfo['username']);
      ?>
      </strong> | <a href="?logout=1" class="logout-link">登出</a>
    </div>
    <form method="post" style="margin-bottom: 30px;">
      <textarea name="post_content" rows="3" placeholder="想說什麼呢？" required></textarea>
      <button type="submit">發表貼文</button>
    </form>
  <?php else: ?>
    <p><a href="login.php">登入</a>後才能發表貼文與留言。</p>
  <?php endif; ?>

  <?php while ($post = $posts->fetch_assoc()): ?>
    <div class="post-card" id="post-<?= $post['id'] ?>">
      <div>
        <span class="username"><?= htmlspecialchars($post['username']) ?></span>
        <span class="timestamp"><?= $post['created_at'] ?></span>
      </div>

      <?php if ($editing_post_id == $post['id'] && $post['user_id'] == $user_id): ?>
        <form method="post">
          <textarea name="edit_post_content" rows="3" required><?= htmlspecialchars($post['content']) ?></textarea>
          <input type="hidden" name="edit_post_id" value="<?= $post['id'] ?>">
          <button type="submit">儲存</button>
          <a href="forum.php">取消</a>
        </form>
      <?php else: ?>
        <div class="content" style="white-space: pre-wrap;"><?= htmlspecialchars($post['content']) ?></div>
        <div class="actions">
          <form method="post" style="display:inline;" class="like-form" data-post-id="<?= $post['id'] ?>">
           <?php if ($user_id): ?>
              <button class="like-btn <?= $post['liked_by_me'] ? 'liked' : '' ?>" data-post-id="<?= $post['id'] ?>">
                ❤️ <span class="like-count"><?= $post['like_count'] ?></span>
              </button>
            <?php else: ?>
              <span>❤️ <?= $post['like_count'] ?></span>
            <?php endif; ?>
        </button>
        <a href="comment.php?post_id=<?= $post['id'] ?>">💬 留言</a>  
          </form>

          <?php if ($post['user_id'] == $user_id): ?>
            <a href="forum.php?edit_post_id=<?= $post['id'] ?>">編輯</a>
            <form method="post" style="display:inline;" onsubmit="return confirm('確定刪除貼文？');">
              <input type="hidden" name="delete_post_id" value="<?= $post['id'] ?>">
              <button type="submit" style="color:#c00; background:none; border:none; cursor:pointer;">刪除</button>
            </form>
          <?php elseif ($is_admin || $is_helper): ?>
            <form method="post" style="display:inline;" onsubmit="return confirm('確定刪除貼文？');">
              <input type="hidden" name="delete_post_id" value="<?= $post['id'] ?>">
              <button type="submit" style="color:#c00; background:none; border:none; cursor:pointer;">刪除</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- 留言區 -->
      <div id="comments-<?= $post['id'] ?>" style="margin-top: 10px; padding-left: 20px; border-left: 3px solid #eee;">
        <?php
        $comments = $conn1->query("SELECT c.*, u.username FROM comment c JOIN users u ON c.user_id = u.id WHERE post_id = {$post['id']} ORDER BY c.created_at ASC");
        while ($comment = $comments->fetch_assoc()):
        ?>
          <div style="margin-bottom: 8px;">
            <strong><?= htmlspecialchars($comment['username']) ?></strong>：
            <?= nl2br(htmlspecialchars($comment['content'])) ?>
            <?php if ($is_admin || $is_helper): ?>
              <form method="post" style="display:inline;" onsubmit="return confirm('確定刪除留言？');">
                <input type="hidden" name="delete_comment_id" value="<?= $comment['id'] ?>">
                <button type="submit" style="color:#c00; background:none; border:none; cursor:pointer;">刪除</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>

        <!-- 留言輸入框已移除 -->
      </div>

    </div>
  <?php endwhile; ?>
</div>

<script>
document.querySelectorAll('.like-form').forEach(form => {
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    const postId = this.getAttribute('data-post-id');
    fetch('forum.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ like_post_id: postId })
    })
    .then(res => res.json())
    .then(data => {
      const btn = this.querySelector('.like-btn');
      const countSpan = btn.querySelector('.like-count');
      countSpan.textContent = data.like_count;
      btn.classList.toggle('liked');
    });
  });
});
</script>

</body>
</html>
