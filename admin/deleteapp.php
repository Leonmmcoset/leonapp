<?php
require_once '../config.php';

session_start();
// 检查管理员登录状态
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// 验证App ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<script>Swal.fire("错误", "无效的App ID", "error").then(() => { window.location.href = "index.php"; });</script>';
    exit;
}
$appId = $_GET['id'];

// 删除App
$deleteAppSql = "DELETE FROM apps WHERE id = ?";
$stmt = $conn->prepare($deleteAppSql);
$stmt->bind_param("i", $appId);

if ($stmt->execute() === TRUE) {
    // 删除关联的图片
    $deleteImagesSql = "DELETE FROM app_images WHERE app_id = ?";
    $imgStmt = $conn->prepare($deleteImagesSql);
    $imgStmt->bind_param("i", $appId);
    $imgStmt->execute();

    // 删除关联的版本
    $deleteVersionsSql = "DELETE FROM app_versions WHERE app_id = ?";
    $verStmt = $conn->prepare($deleteVersionsSql);
    $verStmt->bind_param("i", $appId);
    $verStmt->execute();

    echo '<script>Swal.fire("成功", "App 删除成功", "success").then(() => { window.location.href = "index.php"; });</script>';
} else {
    echo '<script>Swal.fire("错误", "App 删除失败: '. $conn->error .'", "error").then(() => { window.location.href = "index.php"; });</script>';
}
exit;
?>