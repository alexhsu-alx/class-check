<?php
session_start();
require 'db.php';  // 你自己的資料庫連線設定

// 確認使用者是否管理員(假設 role_id=1 是管理員)
if ($_SESSION['role_id'] != 1) {
    die('只有管理員可操作此頁面');
}

// 取得角色 id (helper 和 member)
$roles_res = $conn->query("SELECT id, role_name FROM roles WHERE role_name IN ('helper', 'member')");
$roles = [];
while ($row = $roles_res->fetch_assoc()) {
    $roles[$row['role_name']] = $row['id'];
}

// 如果表單送出
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];

    if ($action === 'make_helper') {
        $new_role_id = $roles['helper'];
    } elseif ($action === 'remove_helper') {
        $new_role_id = $roles['member'];
    } else {
        $new_role_id = null;
    }

    if ($new_role_id !== null) {
        $stmt = $conn->prepare("UPDATE users SET role_id = ? WHERE id = ?");
        $stmt->bind_param('ii', $new_role_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: manage_helpers.php'); // 刷新頁面避免重複提交
    exit;
}

// 撈出所有使用者和角色名稱
$sql = "SELECT u.id, u.username, u.display_name, r.role_name 
        FROM users u LEFT JOIN roles r ON u.role_id = r.id ORDER BY u.id ASC";
$res = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>管理小幫手</title>
</head>
<body>
    <h1>管理小幫手</h1>
    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>使用者ID</th>
                <th>帳號(username)</th>
                <th>使用者名稱</th>
                <th>目前角色</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $res->fetch_assoc()): ?>
            <tr>
                <td><?=htmlspecialchars($row['id'])?></td>
                <td><?=htmlspecialchars($row['username'])?></td>
                <td><?=htmlspecialchars($row['display_name'])?></td>
                <td><?=htmlspecialchars($row['role_name'])?></td>
                <td>
                    <?php if ($row['role_name'] !== 'helper'): ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                        <input type="hidden" name="action" value="make_helper">
                        <button type="submit">設為小幫手</button>
                    </form>
                    <?php else: ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                        <input type="hidden" name="action" value="remove_helper">
                        <button type="submit">移除小幫手</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
