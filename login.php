<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 資料庫連線
$servername1 = "localhost";
$username1 = "root";
$password1 = "";
$database1 = "user";

$conn1 = new mysqli($servername1, $username1, $password1, $database1);
if ($conn1->connect_error) {
    die("資料庫連線失敗: " . $conn1->connect_error);
}

$message = '';

// 處理登入表單提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (!empty($email) && !empty($password)) {
        $check = $conn1->prepare("SELECT * FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // 成功登入：儲存 Session 並導向首頁
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['avatar'] = $user['avatar'];
                header("Location: index.php");
                exit();
            } else {
                $message = "❌ 密碼錯誤";
            }
        } else {
            $message = "⚠️ 此 Gmail 尚未註冊";
        }
        $check->close();
    } else {
        $message = "請填寫所有欄位";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>登入</title>
  <link rel="stylesheet" href="register.css">
 <style>
    .back-button { 
      display: inline-block; 
      padding: 10px 15px; 
      background-color: black;
       color:white;
        text-decoration: none; 
        border-radius: 5px;
         font-size: 18px; 
        }
    .back-button:hover {
       background-color: black;
       }
    body { 
      margin: 0;
       overflow: hidden;
        background-color: black;
       }
    .star {
       position: fixed;
        width: 2px; 
        height: 2px;
         background: white; 
         animation: move 2s linear infinite;
          z-index: 0; 
        }
    @keyframes move { 
      0% { 
        transform: translateZ(0);
         opacity: 1;
         } 100% { transform: translateZ(-1000px); 
          opacity: 0; } 
        }
    input[type="text"], 
    input[type="password"],
     input[type="email"]
      {
      width: 100%;
       padding: 8px; 
       font-size: 16px;
        margin-bottom: 10px; 
        box-sizing: border-box;
    }
    .login-link {
      margin-top: 10px;
      display: block;
      text-align: center;
      color: red;
    }
  </style>
</head>
<body>
  <a href="register.php" class="back-button">⬅︎ 返回</a>

  <script>
    // 產生星星動畫
    for (let i = 0; i < 500; i++) {
        const star = document.createElement('div');
        star.className = 'star';
        document.body.appendChild(star);
        star.style.left = `${Math.random() * 100}vw`;
        star.style.top = `${Math.random() * 100}vh`;
        star.style.animationDuration = `${Math.random() * 3 + 2}s`;
    }
  </script>

  <div class="content">
    <header class="header">
      <div class="logo">九大行星</div>
    </header>

    <div class="form-container">
      <h1>登入</h1>
      <?php if (!empty($message)): ?>
        <p style="color:red; text-align: center;"><?= $message ?></p>
      <?php endif; ?>
      <form method="post">
        <label for="email">Gmail：</label>
        <input type="email" id="email" name="email" pattern="^[a-zA-Z0-9._%+-]+@gmail\.com$" required>
        <label for="password">密碼：</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">登入</button>
      </form>
    </div>
  </div>
</body>
</html>
