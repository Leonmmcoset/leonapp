<?php
require_once '../config.php';
require_once '../includes/logger.php';

session_start();
// 检查开发者登录状态
if (!isset($_SESSION['developer_id'])) {
    header('Location: login.php');
    exit;
}
$developerId = $_SESSION['developer_id'];

// 验证App ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php?error=无效的App ID');
    exit;
}
$appId = $_GET['id'];

// 获取App信息并验证所有权
$app = null;
$getAppSql = "SELECT * FROM apps WHERE id = ? AND developer_id = ?";
$stmt = $conn->prepare($getAppSql);
if (!$stmt) {
    log_error("应用所有权验证查询准备失败: " . $conn->error, __FILE__, __LINE__);
    header('Location: dashboard.php?error=验证应用所有权失败');
    exit;
}
$stmt->bind_param("ii", $appId, $developerId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header('Location: dashboard.php?error=App不存在或无权访问');
    exit;
}
$app = $result->fetch_assoc();
$platforms = json_decode($app['platforms'], true);

$success = '';
$error = '';
// 处理版本上传请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_version'])) {
    // 验证版本信息
    if (empty($_POST['version']) || empty($_FILES['app_file']['name'])) {
        $error = '版本号和安装包不能为空';
    } else {
        // 处理App文件上传
        $uploadDir = '../files/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = basename($_FILES['app_file']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['app_file']['tmp_name'], $targetPath)) {
            $version = $_POST['version'];
            $changelog = $_POST['changelog'] ?? '';
            
            // 插入新版本记录
            $insertVersionSql = "INSERT INTO app_versions (app_id, version, changelog, file_path) VALUES (?, ?, ?, ?)";
            $verStmt = $conn->prepare($insertVersionSql);
            if (!$verStmt) {
                log_error("版本插入准备失败: " . $conn->error, __FILE__, __LINE__);
                $error = '版本保存失败，请稍后再试';
                unlink($targetPath); // 清理已上传文件
            } else {
                $verStmt->bind_param("isss", $appId, $version, $changelog, $targetPath);
                
                if ($verStmt->execute()) {
                    // 更新应用表中的最新版本
                    // 更新应用表中的最新版本
                    $updateAppSql = "UPDATE apps SET version = ? WHERE id = ?";
                    $updStmt = $conn->prepare($updateAppSql);
                    if (!$updStmt) {
                        log_error("应用版本更新准备失败: " . $conn->error, __FILE__, __LINE__);
                        $error = '更新应用版本失败，请稍后再试';
                        unlink($targetPath); // 数据库更新失败，删除文件
                    } else {
                        $updStmt->bind_param("si", $version, $appId);
                        $updStmt->execute();
                        $success = '版本上传成功';
                    }
                } else {
                    $error = '版本保存失败: '. $conn->error;
                    unlink($targetPath); // 数据库更新失败，删除文件
                }
            }
        } else {
            $error = '文件上传失败';
        }
    }
}

// 处理版本修改请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['version_id'])) {
    $versionId = $_POST['version_id'];
    $version = $_POST['version'];
    $changelog = $_POST['changelog'] ?? '';
    
    // 检查是否有新文件上传
    if (!empty($_FILES['new_app_file']['name'])) {
        $uploadDir = '../files/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = basename($_FILES['new_app_file']['name']);
        $newFilePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['new_app_file']['tmp_name'], $newFilePath)) {
            // 获取旧文件路径并删除
            $getOldPathSql = "SELECT file_path FROM app_versions WHERE id = ?";
            $getOldPathStmt = $conn->prepare($getOldPathSql);
            if (!$getOldPathStmt) {
                log_error("获取旧文件路径查询准备失败: " . $conn->error, __FILE__, __LINE__);
                $error = '版本修改失败，请稍后再试';
                unlink($newFilePath);
            } else {
                $getOldPathStmt->bind_param("i", $versionId);
                $getOldPathStmt->execute();
                $oldPathResult = $getOldPathStmt->get_result();
                if ($oldPathResult->num_rows > 0) {
                    $oldPathRow = $oldPathResult->fetch_assoc();
                    $oldFilePath = $oldPathRow['file_path'];
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }
                
                // 更新版本信息
                $updateVersionSql = "UPDATE app_versions SET version = ?, changelog = ?, file_path = ? WHERE id = ?";
                $updateVersionStmt = $conn->prepare($updateVersionSql);
                if (!$updateVersionStmt) {
                    log_error("版本更新查询准备失败: " . $conn->error, __FILE__, __LINE__);
                    $error = '版本修改失败，请稍后再试';
                    unlink($newFilePath);
                } else {
                    $updateVersionStmt->bind_param("sssi", $version, $changelog, $newFilePath, $versionId);
                    if ($updateVersionStmt->execute()) {
                        $success = '版本修改成功';
                    } else {
                        $error = '版本修改失败: ' . $conn->error;
                        unlink($newFilePath);
                    }
                }
            }
        } else {
            $error = '文件上传失败';
        }
    } else {
        // 仅更新版本号和更新日志
        $updateVersionSql = "UPDATE app_versions SET version = ?, changelog = ? WHERE id = ?";
        $updateVersionStmt = $conn->prepare($updateVersionSql);
        if (!$updateVersionStmt) {
            log_error("版本更新查询准备失败: " . $conn->error, __FILE__, __LINE__);
            $error = '版本修改失败，请稍后再试';
        } else {
            $updateVersionStmt->bind_param("ssi", $version, $changelog, $versionId);
            if ($updateVersionStmt->execute()) {
                $success = '版本修改成功';
            } else {
                $error = '版本修改失败: ' . $conn->error;
            }
        }
    }
}

// 处理版本删除请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_version'])) {
    $versionId = $_POST['version_id'];
    $filePath = $_POST['file_path'];
    
    // 删除文件
    if (file_exists($filePath)) {
        if (!unlink($filePath)) {
            log_error("文件删除失败: " . $filePath, __FILE__, __LINE__);
            $error = '版本删除失败，请稍后再试';
        }
    }
    
    // 从数据库删除版本记录
    $deleteVersionSql = "DELETE FROM app_versions WHERE id = ?";
    $deleteVersionStmt = $conn->prepare($deleteVersionSql);
    if (!$deleteVersionStmt) {
        log_error("版本删除查询准备失败: " . $conn->error, __FILE__, __LINE__);
        $error = '版本删除失败，请稍后再试';
    } else {
        $deleteVersionStmt->bind_param("i", $versionId);
        if ($deleteVersionStmt->execute()) {
            $success = '版本删除成功';
        } else {
            $error = '版本删除失败: ' . $conn->error;
        }
    }

    if (!empty($success)) {
        echo $success;
    } else {
        echo $error;
    }
    exit;
}

// 获取现有版本列表
$versions = [];
$getVersionsSql = "SELECT * FROM app_versions WHERE app_id = ? ORDER BY id DESC";
$verStmt = $conn->prepare($getVersionsSql);
if (!$verStmt) {
    log_error("版本查询准备失败: " . $conn->error, __FILE__, __LINE__);
    $error = '获取版本列表失败，请稍后再试';
} else {
    $verStmt->bind_param("i", $appId);
    $verStmt->execute();
    $versionsResult = $verStmt->get_result();
    while ($ver = $versionsResult->fetch_assoc()) {
        $versions[] = $ver;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>版本控制 - <?php echo htmlspecialchars($app['name']); ?></title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .blur-bg {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light blur-bg">
        <div class="container">
            <a class="navbar-brand" href="../index.php"><?php echo APP_STORE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">我的应用</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="upload_app.php">上传新应用</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="version_control.php?id=<?php echo $appId; ?>">版本控制</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">个人资料</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">退出登录</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (!empty($success)): ?>
            <!-- <script>Swal.fire('成功', '<?php echo addslashes($success); ?>', 'success');</script> -->
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <!-- <script>Swal.fire('错误', '<?php echo addslashes($error); ?>', 'error');</script> -->
        <?php endif; ?>

        <div class="card blur-bg mb-4">
            <div class="card-header">
                <h2>应用版本控制: <?php echo htmlspecialchars($app['name']); ?></h2>
            </div>
            <div class="card-body">
                <h4>上传新版本</h4>
                <form method="post" enctype="multipart/form-data" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="version" name="version" placeholder="版本号" required>
                                <label for="version">版本号 (如: 1.0.0)</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="app_file" class="form-label">安装包文件</label>
                                <input class="form-control" type="file" id="app_file" name="app_file" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-floating mb-3">
                        <textarea class="form-control" id="changelog" name="changelog" rows="3" placeholder="更新日志"></textarea>
                        <label for="changelog">更新日志</label>
                    </div>
                    <button type="submit" class="btn btn-primary" name="upload_version">上传新版本</button>
                    <a href="dashboard.php" class="btn btn-secondary ms-2">返回</a>
                </form>

                <hr>

                <h4>版本历史</h4>
                <?php if (empty($versions)): ?>
                    <div class="alert alert-info">暂无版本记录</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>版本号</th>
                                    <th>上传时间</th>
                                    <th>更新日志</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($versions as $ver): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ver['version']); ?></td>
                                    <td><?php echo htmlspecialchars($ver['upload_time']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($ver['changelog'] ?: '无')); ?></td>
                                    <td>
                                        <a href="../download.php?id=<?php echo $ver['id']; ?>&type=version" class="btn btn-sm btn-outline-primary">下载</a>
                                        <a href="#" class="btn btn-sm btn-outline-warning ms-2" onclick="openEditModal(<?php echo $ver['id']; ?>, '<?php echo htmlspecialchars($ver['version']); ?>', '<?php echo htmlspecialchars($ver['changelog']); ?>')">修改</a>
                                        <a href="#" class="btn btn-sm btn-outline-danger ms-2" onclick="confirmDelete(<?php echo $ver['id']; ?>, '<?php echo htmlspecialchars($ver['file_path']); ?>')">删除</a>
                                        <?php if ($ver['is_current'] == 1): ?>
                                            <span class="badge bg-success">当前版本</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.js"></script>
    <script>
        function openEditModal(versionId, version, changelog) {
            const modal = `
                <div class="modal fade" id="editVersionModal" tabindex="-1" aria-labelledby="editVersionModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editVersionModalLabel">修改版本</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form id="editVersionForm" method="post" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <input type="hidden" name="version_id" value="${versionId}">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="editVersion" name="version" value="${version}" required>
                                        <label for="editVersion">版本号</label>
                                    </div>
                                    <div class="form-floating mb-3">
                                        <textarea class="form-control" id="editChangelog" name="changelog" rows="3">${changelog}</textarea>
                                        <label for="editChangelog">更新日志</label>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_app_file" class="form-label">更新App文件 (可选)</label>
                                        <input class="form-control" type="file" id="new_app_file" name="new_app_file">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                                    <button type="submit" class="btn btn-primary">保存修改</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>`;
            
            document.body.insertAdjacentHTML('beforeend', modal);
            const editModal = new bootstrap.Modal(document.getElementById('editVersionModal'));
            editModal.show();

            document.getElementById('editVersionForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch('version_control.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    editModal.hide();
                    window.location.reload();
                })
                .catch(error => {
                    editModal.hide();
                    window.location.reload();
                });
            });
        }

        function confirmDelete(versionId, filePath) {
            const formData = new FormData();
            formData.append('delete_version', 'true');
            formData.append('version_id', versionId);
            formData.append('file_path', filePath);
            
            fetch('version_control.php', { method: 'POST', body: formData })
            .then(response => response.text())
            .then(data => {
                window.location.reload();
            })
            .catch(error => {
                window.location.reload();
            });
        }
    </script>
</body>
</html>