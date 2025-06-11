<?php
session_start();

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'db.php';
$userId = $_SESSION['user_id'];
$profileId = isset($_GET['user_id']) ? intval($_GET['user_id']) : $userId;

$stmt = $conn1->prepare("SELECT username, email, gender, birthday, bio, created_at FROM users WHERE id=?");
$stmt->bind_param("i", $profileId);
$stmt->execute();
$stmt->bind_result($username, $email, $gender, $birthday, $bio, $createdAt);
$stmt->fetch();
$stmt->close();

$saveSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $profileId === $userId) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $birthday = trim($_POST['birthday'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    $stmt = $conn1->prepare("UPDATE users SET username=?, email=?, gender=?, birthday=?, bio=? WHERE id=?");
    $stmt->bind_param("sssssi", $username, $email, $gender, $birthday, $bio, $userId);
    $executeResult = $stmt->execute();
    $stmt->close();

    if ($executeResult) {
        $saveSuccess = true;
        $_SESSION['username'] = $username;
    }
}

$post_stmt = $conn1->prepare("SELECT content, created_at FROM post WHERE user_id = ? ORDER BY created_at DESC");
$post_stmt->bind_param("i", $profileId);
$post_stmt->execute();
$post_result = $post_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8" />
  <title><?= htmlspecialchars($username) ?> 的個人資料</title>
  <style>
    body {
      background-color: #0b0b0b;
      color: white;
      font-family: Arial, sans-serif;
      padding: 40px;
    }
    .profile-container {
      background-color: #1e1e1e;
      max-width: 600px;
      margin: 0 auto;
      padding: 30px;
      border-radius: 12px;
      position: relative;
    }
    h1 {
      text-align: center;
      margin-bottom: 30px;
    }
    .profile-item {
      margin-bottom: 20px;
    }
    .label {
      color: #aaa;
      font-size: 14px;
      margin-bottom: 5px;
      display: block;
    }
    .value, input, select, textarea {
      font-size: 16px;
      width: 100%;
      padding: 10px 12px;
      border-radius: 5px;
      border: none;
      background-color: #2a2a2a;
      color: white;
      box-sizing: border-box;
    }
    .btn {
      display: block;
      margin: 30px auto 0;
      padding: 10px 20px;
      background-color: #444;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }
    .btn:hover {
      background-color: #666;
    }
    .success-message {
      background-color: #28a745;
      color: white;
      padding: 10px;
      text-align: center;
      border-radius: 6px;
      margin-bottom: 20px;
    }
    .logout-btn {
      position: absolute;
      top: 20px;
      right: 20px;
      background-color: #cc3333;
      padding: 8px 14px;
      border: none;
      border-radius: 6px;
      color: white;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    .logout-btn:hover {
      background-color: #ff4444;
    }
    .post {
      background-color: #2a2a2a;
      padding: 15px;
      margin-top: 15px;
      border-radius: 8px;
    }
  </style>
</head>
<body>
<div class="profile-container">
  <?php if ($profileId === $userId): ?>
    <form method="GET" style="position:absolute; top:20px; right:20px;">
    </form>
  <?php endif; ?>

  <h1><?= htmlspecialchars($username) ?> 的個人資料</h1>

  <?php if ($profileId === $userId): ?>
    <?php if ($saveSuccess): ?>
      <div class="success-message">✅ 資料儲存成功，3 秒後跳回設定頁...</div>
      <script>setTimeout(() => { window.location.href = "index.php"; }, 3000);</script>
    <?php endif; ?>

    <form method="POST" id="profileForm">
      <div class="profile-item">
        <label class="label" for="username">使用者名稱</label>
        <input type="text" name="username" id="username" value="<?= htmlspecialchars($username) ?>" required />
      </div>

      <div class="profile-item">
        <label class="label" for="email">電子郵件</label>
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($email) ?>" required />
      </div>

      <div class="profile-item">
        <label class="label" for="gender">性別</label>
        <select name="gender" id="gender">
          <option value="" <?= $gender == '' ? 'selected' : '' ?>>請選擇</option>
          <option value="male" <?= $gender == 'male' ? 'selected' : '' ?>>男</option>
          <option value="female" <?= $gender == 'female' ? 'selected' : '' ?>>女</option>
          <option value="other" <?= $gender == 'other' ? 'selected' : '' ?>>其他</option>
        </select>
      </div>

      <div class="profile-item">
        <label class="label" for="birthday">生日</label>
        <input type="date" name="birthday" id="birthday" value="<?= htmlspecialchars($birthday) ?>" />
      </div>

      <div class="profile-item">
        <label class="label" for="bio">自我介紹</label>
        <textarea name="bio" id="bio" rows="3"><?= htmlspecialchars($bio) ?></textarea>
      </div>

      <button class="btn" type="submit">儲存資料</button>
    </form>
  <?php else: ?>
    <p>📧 <?= htmlspecialchars($email) ?></p>
    <p>📅 加入日期：<?= htmlspecialchars($createdAt) ?></p>
    <p>👤 <?= nl2br(htmlspecialchars($bio)) ?></p>
  <?php endif; ?>
</div>
</body>
</html>
