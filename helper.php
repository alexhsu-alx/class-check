<?php
session_start();

function getDbConnection() {
    static $mysqli = null;
    if ($mysqli === null) {
        $mysqli = new mysqli("localhost", "root", "", "user");
        if ($mysqli->connect_error) {
            die("資料庫連線失敗：" . $mysqli->connect_error);
        }
    }
    return $mysqli;
}

/**
 * 嘗試登入使用者
 * @param string $username 使用者名稱
 * @param string $password 密碼
 * @return array ['success' => bool, 'message' => string]
 */
function loginUser($username, $password) {
    $mysqli = getDbConnection();

    $sql = "SELECT id, username, password, is_admin FROM users WHERE username = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        return ['success' => false, 'message' => "SQL 準備失敗：" . $mysqli->error];
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            return ['success' => true, 'message' => '登入成功'];
        } else {
            return ['success' => false, 'message' => '密碼錯誤'];
        }
    } else {
        return ['success' => false, 'message' => '找不到使用者'];
    }
}
