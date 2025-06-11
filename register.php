<?php
$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "user";

$conn1 = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn1->connect_error) {
    die("資料庫連線失敗: " . $conn1->connect_error);
}

$message = '';
$message_type = 'error';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $password = trim($_POST["password"] ?? '');
    $gender = $_POST["gender"] ?? 'other';
    $birthday = !empty($_POST["birthday"]) ? $_POST["birthday"] : null;
    $bio = trim($_POST["bio"] ?? '');

    if (empty($username) || empty($email) || empty($password)) {
        $message = "請填寫所有必填欄位（使用者名稱、Gmail、密碼）";
    } else {
        $stmt = $conn1->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn1->error);
        }
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "使用者名稱或 Gmail 已被註冊";
        } else {
            if ($message === '') {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $insertStmt = $conn1->prepare("INSERT INTO users (username, email, password, gender, birthday, bio) VALUES (?, ?, ?, ?, ?, ?)");
                if (!$insertStmt) {
                    die("Prepare failed: " . $conn1->error);
                }

                $bindBirthday = $birthday ?? '';
                $insertStmt->bind_param("ssssss", $username, $email, $hashed_password, $gender, $bindBirthday, $bio);

                if ($insertStmt->execute()) {
                    $message_type = 'success';
                    $message = "✅ 註冊成功！<a href='login.php'>前往登入</a>";
                } else {
                    $message = "❌ 註冊失敗，請稍後再試。錯誤：" . $insertStmt->error;
                }
                $insertStmt->close();
            }
        }
        $stmt->close();
    }
}
$conn1->close();
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8" />
    <title>註冊</title>
    <style>
        body {
            background-color: black;
            color: white;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 400px;
            margin: 30px auto;
            background: #222;
            padding: 20px;
            border-radius: 8px;
        }
        label {
            display: block;
            margin-top: 10px;
        }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 4px;
            border-radius: 4px;
            border: none;
            font-size: 16px;
            background: #333;
            color: #fff;
        }
        button {
            margin-top: 15px;
            padding: 10px;
            width: 100%;
            background: #f44336;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            border-radius: 4px;
        }
        button:hover {
            background: #d32f2f;
        }
        .message {
            margin-top: 10px;
        }
        .message.error {
            color: #f44336;
        }
        .message.success {
            color: #4CAF50;
        }
        a {
            color: #f44336;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>註冊帳號</h1>
        <?php if ($message !== ''): ?>
            <div class="message <?= $message_type ?>"><?= $message ?></div>
        <?php endif; ?>
        <form method="post" novalidate>
            <label for="username">使用者名稱 (必填)：</label>
            <input type="text" id="username" name="username" required maxlength="50"
                   pattern="^[a-zA-Z0-9_]{3,50}$"
                   title="只能包含英數字與底線，3-50字元"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" />

            <label for="email">Gmail (必填)：</label>
            <input type="email" id="email" name="email"
                   pattern="^[a-zA-Z0-9._%+-]+@gmail\.com$"
                   required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />

            <label for="password">密碼 (必填)：</label>
            <input type="password" id="password" name="password" required minlength="6"
                   pattern="(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}"
                   title="密碼至少6字，且包含至少1個字母與1個數字" />

            <label for="gender">性別：</label>
            <select id="gender" name="gender">
                <option value="male" <?= (($_POST['gender'] ?? '') === 'male') ? 'selected' : '' ?>>男</option>
                <option value="female" <?= (($_POST['gender'] ?? '') === 'female') ? 'selected' : '' ?>>女</option>
                <option value="other" <?= (($_POST['gender'] ?? '') === 'other' || !isset($_POST['gender'])) ? 'selected' : '' ?>>其他</option>
            </select>

            <label for="birthday">生日：</label>
            <input type="date" id="birthday" name="birthday" value="<?= htmlspecialchars($_POST['birthday'] ?? '') ?>" />

            <label for="bio">自我介紹：</label>
            <textarea id="bio" name="bio" rows="4"><?= htmlspecialchars($_POST['bio'] ?? '') ?></textarea>

            <button type="submit">確認註冊</button>
        </form>
        <p style="margin-top:15px;">已經有帳戶？<a href="login.php">登入</a></p>
    </div>
</body>
</html>
