<?php
require_once '../config.php';
session_start();

// 检查管理员登录状态
if (!isset($_SESSION['admin']) || !isset($_SESSION['admin']['id'])) {
    header('Location: login.php');
    exit;
}

// 检查权限 - 只允许all权限
if ($_SESSION['admin']['permission'] !== 'all') {
    header('Location: announcements.php?error=没有删除公告的权限');
    exit();
}

// 验证公告ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: announcements.php?error=无效的公告ID');
    exit();
}

$announcement_id = intval($_GET['id']);

// 执行删除操作
$stmt = $conn->prepare('DELETE FROM announcements WHERE id = ?');
$stmt->bind_param('i', $announcement_id);

if ($stmt->execute()) {
    $success = '公告已成功删除';
} else {
    $error = '删除公告失败: ' . $conn->error;
}

$stmt->close();
$conn->close();

// 重定向回公告管理页面并显示结果
$redirect = 'announcements.php?' . ($success ? 'success=' . urlencode($success) : 'error=' . urlencode($error));
header('Location: ' . $redirect);
exit;