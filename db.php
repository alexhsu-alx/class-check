<?php
// 資料庫1的連線設定
$servername1 = "localhost";
$username1 = "root";
$password1 = "";
$database1 = "user";  // 第一個資料庫名稱

$conn1 = new mysqli($servername1, $username1, $password1, $database1);

// 檢查資料庫1的連線
if ($conn1->connect_error) {
    die("資料庫1連線失敗: " . $conn1->connect_error);
}

// 資料庫2的連線設定
$servername2 = "localhost";
$username2 = "root";
$password2 = "";
$database2 = "images";  // 第二個資料庫名稱

$conn2 = new mysqli($servername2, $username2, $password2, $database2);

// 檢查資料庫2的連線
if ($conn2->connect_error) {
    die("資料庫2連線失敗: " . $conn2->connect_error);
}
?>
