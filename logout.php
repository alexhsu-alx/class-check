<?php
session_start();
session_unset();      // 清除所有 session 資料
session_destroy();    // 銷毀 session
header("Location: index.php"); // 登出後導回首頁 index.php
exit;
?>
