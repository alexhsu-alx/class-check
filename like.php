<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => '資料庫連線失敗']));
}

$user_id = $_SESSION['user_id'] ?? null;
$post_id = $_POST['post_id'] ?? null;

if (!$user_id || !$post_id) {
    echo json_encode(['success' => false, 'message' => '未登入或缺少參數']);
    exit;
}

// 檢查是否已按讚
$check = $conn->prepare("SELECT * FROM likes WHERE user_id = ? AND post_id = ?");
$check->bind_param("ii", $user_id, $post_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    // 取消讚
    $del = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
    $del->bind_param("ii", $user_id, $post_id);
    $del->execute();
    echo json_encode(['success' => true, 'action' => 'unliked']);
} else {
    // 新增讚
    $ins = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
    $ins->bind_param("ii", $user_id, $post_id);
    $ins->execute();
    echo json_encode(['success' => true, 'action' => 'liked']);
}
