<?php
// 引入配置文件
require_once '../config.php';

session_start();

// 检查开发者是否已登录
if (!isset($_SESSION['developer_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '未授权访问']);
    exit;
}

// 验证请求方法和参数
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['app_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '无效的请求参数']);
    exit;
}

$appId = intval($_POST['app_id']);
$developerId = $_SESSION['developer_id'];

// 检查数据库连接
if (!($conn instanceof mysqli)) {
    log_error('数据库连接错误: 连接不是MySQLi实例', __FILE__, __LINE__);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '数据库连接错误']);
    exit;
}

// 验证应用所有权
$stmt = $conn->prepare('SELECT id FROM apps WHERE id = ? AND developer_id = ?');
if (!$stmt) {
    log_error('验证应用所有权查询准备失败: ' . $conn->error, __FILE__, __LINE__);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '服务器错误']);
    exit;
}

$stmt->bind_param('ii', $appId, $developerId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '您没有权限删除此应用']);
    exit;
}
$stmt->close();

// 开始事务
$conn->begin_transaction();

try {
    // 删除应用的版本记录
    $stmt = $conn->prepare('DELETE FROM app_versions WHERE app_id = ?');
    if (!$stmt) throw new Exception('删除版本记录准备失败: ' . $conn->error);
    $stmt->bind_param('i', $appId);
    if (!$stmt->execute()) throw new Exception('删除版本记录执行失败: ' . $stmt->error);
    $stmt->close();

    // 删除应用记录
    $stmt = $conn->prepare('DELETE FROM apps WHERE id = ? AND developer_id = ?');
    if (!$stmt) throw new Exception('删除应用记录准备失败: ' . $conn->error);
    $stmt->bind_param('ii', $appId, $developerId);
    if (!$stmt->execute()) throw new Exception('删除应用记录执行失败: ' . $stmt->error);
    $stmt->close();

    // 提交事务
    $conn->commit();
    echo json_encode(['success' => true, 'message' => '应用已成功删除']);
} catch (Exception $e) {
    // 回滚事务
    $conn->rollback();
    log_error('删除应用失败: ' . $e->getMessage(), __FILE__, __LINE__);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '删除应用失败: ' . $e->getMessage()]);
}
?>