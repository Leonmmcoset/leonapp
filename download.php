<?php
ob_start();
require_once 'config.php';

// 验证版本ID
if (!isset($_GET['version_id']) || !is_numeric($_GET['version_id'])) {
    http_response_code(400);
    exit('无效的版本ID');
}
$versionId = $_GET['version_id'];

// 获取版本信息
$version = null;
$getVersionSql = "SELECT * FROM app_versions WHERE id = ?";
$stmt = $conn->prepare($getVersionSql);
$stmt->bind_param("i", $versionId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    http_response_code(404);
    exit('版本不存在');
}
$version = $result->fetch_assoc();

// 获取绝对文件路径
$filePath = realpath(__DIR__ . '/' . $version['file_path']);

// 验证文件存在性
if (!$filePath || !file_exists($filePath)) {
    http_response_code(404);
    exit('文件不存在');
}

// 设置下载响应头
$fileName = basename($filePath);
$fileSize = filesize($filePath);

// 清除输出缓冲区并发送 headers
ob_end_clean();
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($fileName));
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');
header('Pragma: public');

// 输出文件内容
if (!readfile($filePath)) {
    http_response_code(500);
    exit('无法读取文件');
}

exit;
?>