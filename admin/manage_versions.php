<?php
require_once '../config.php';

session_start();
// 检查管理员登录状态
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// 验证App ID
if (!isset($_GET['app_id']) || !is_numeric($_GET['app_id'])) {
    header('Location: index.php?error=无效的App ID');
    exit;
}
$appId = $_GET['app_id'];

// 获取App信息
$app = null;
$getAppSql = "SELECT * FROM apps WHERE id = ?";
$stmt = $conn->prepare($getAppSql);
$stmt->bind_param("i", $appId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header('Location: index.php?error=App不存在');
    exit;
}
$app = $result->fetch_assoc();

// 获取所有版本
$versions = [];
$getVersionsSql = "SELECT * FROM app_versions WHERE app_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($getVersionsSql);
$stmt->bind_param("i", $appId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $versions[] = $row;
}

$success = '';
$error = '';

// 处理添加版本
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_version'])) {
    $version = $_POST['version'];
    $changelog = $_POST['changelog'];

    if (empty($version)) {
        $error = '版本号不能为空';
    } elseif (empty($_FILES['app_file']['name'])) {
        $error = '请上传App文件';
    } else {
        $uploadDir = '../files/';
        $fileName = basename($_FILES['app_file']['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['app_file']['tmp_name'], $targetPath)) {
            $insertVersionSql = "INSERT INTO app_versions (app_id, version, changelog, file_path, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($insertVersionSql);
            $stmt->bind_param("isss", $appId, $version, $changelog, $targetPath);

            if ($stmt->execute() === TRUE) {
                header('Location: manage_versions.php?app_id=' . $appId . '&success=版本添加成功');
                exit;
            } else {
                $error = '版本添加失败: ' . $conn->error;
                unlink($targetPath); // 删除已上传的文件
            }
        } else {
            $error = '文件上传失败';
        }
    }
}

// 处理删除版本
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $versionId = $_GET['delete_id'];

    // 获取版本信息
    $getVersionSql = "SELECT file_path FROM app_versions WHERE id = ? AND app_id = ?";
    $stmt = $conn->prepare($getVersionSql);
    $stmt->bind_param("ii", $versionId, $appId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $version = $result->fetch_assoc();

        // 删除文件
        if (file_exists($version['file_path'])) {
            unlink($version['file_path']);
        }

        // 删除数据库记录
        $deleteVersionSql = "DELETE FROM app_versions WHERE id = ? AND app_id = ?";
        $stmt = $conn->prepare($deleteVersionSql);
        $stmt->bind_param("ii", $versionId, $appId);

        if ($stmt->execute() === TRUE) {
            header('Location: manage_versions.php?app_id=' . $appId . '&success=版本删除成功');
            exit;
        } else {
            $error = '版本删除失败: ' . $conn->error;
        }
    } else {
        $error = '版本不存在';
    }
}

// 处理编辑版本
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_version'])) {
    $versionId = $_POST['version_id'];
    $version = $_POST['version'];
    $changelog = $_POST['changelog'];

    if (empty($version)) {
        $error = '版本号不能为空';
    } else {
        // 检查是否上传了新文件
        $fileUpdate = '';
        $params = ['ss', $version, $changelog, $versionId, $appId];

        if (!empty($_FILES['new_app_file']['name'])) {
            $uploadDir = '../files/';
            $fileName = basename($_FILES['new_app_file']['name']);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['new_app_file']['tmp_name'], $targetPath)) {
                // 获取旧文件路径
                $getOldFileSql = "SELECT file_path FROM app_versions WHERE id = ? AND app_id = ?";
                $stmt = $conn->prepare($getOldFileSql);
                $stmt->bind_param("ii", $versionId, $appId);
                $stmt->execute();
                $result = $stmt->get_result();
                $oldVersion = $result->fetch_assoc();

                // 删除旧文件
                if (file_exists($oldVersion['file_path'])) {
                    unlink($oldVersion['file_path']);
                }

                $fileUpdate = ", file_path = ?";
                $params[0] = 'sss';
                $params[] = $targetPath;
            } else {
                $error = '文件上传失败';
            }
        }

        if (empty($error)) {
            $updateVersionSql = "UPDATE app_versions SET version = ?, changelog = ?" . $fileUpdate . " WHERE id = ? AND app_id = ?";
            $stmt = $conn->prepare($updateVersionSql);

            // 动态绑定参数
            $stmt->bind_param(...$params);

            if ($stmt->execute() === TRUE) {
                header('Location: manage_versions.php?app_id=' . $appId . '&success=版本更新成功');
                exit;
            } else {
                $error = '版本更新失败: ' . $conn->error;
            }
        }
    }
}

// 获取URL参数中的成功/错误消息
if (isset($_GET['success'])) {
    $success = $_GET['success'];
} elseif (isset($_GET['error'])) {
    $error = $_GET['error'];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理版本 - <?php echo htmlspecialchars($app['name']); ?></title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="../css/animations.css">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <!-- 自定义CSS -->
    <link rel="stylesheet" href="../styles.css">
    <style>
        .version-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .version-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .action-btn {
            margin: 0 2px;
        }

        .modal-backdrop {
            backdrop-filter: blur(5px);
        }
    </style>
</head>

<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="index.php"><?php echo APP_STORE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">App列表</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="editapp.php?id=<?php echo $appId; ?>">返回编辑App</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="manage_versions.php?app_id=<?php echo $appId; ?>">管理版本</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?logout=true">退出登录</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <h1>管理版本: <?php echo htmlspecialchars($app['name']); ?></h1>
                <p class="text-muted">管理该应用的所有版本</p>
            </div>
            <div class="col text-end">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVersionModal">
                    添加新版本
                </button>
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (empty($versions)): ?>
            <div class="alert alert-info">
                暂无版本记录
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($versions as $version): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card version-card h-100">
                            <div class="card-body">
                                <h5 class="card-title">版本 <?php echo htmlspecialchars($version['version']); ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted">发布日期: <?php echo date('Y-m-d H:i', strtotime($version['created_at'])); ?></h6>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($version['changelog'])); ?></p>
                            </div>
                            <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
                                <small class="text-muted">文件大小: <?php
                                                                $filePath = $version['file_path'];
                                                                if (file_exists($filePath)) {
                                                                    echo filesize($filePath) > 0 ? number_format(filesize($filePath) / 1024 / 1024, 2) . ' MB' : '未知';
                                                                } else {
                                                                    echo '文件不存在';
                                                                }
                                                                ?></small>
                                <div> <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editVersionModal_<?php echo $version['id']; ?>">编辑</button>
                                    <a href="../<?php echo htmlspecialchars($version['file_path']); ?>" class="btn btn-sm btn-primary" download>下载</a>
                                    <a href="?app_id=<?php echo $appId; ?>&delete_id=<?php echo $version['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定要删除此版本吗?');">删除</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 编辑版本模态框 -->
                    <div class="modal fade" id="editVersionModal_<?php echo $version['id']; ?>" tabindex="-1" aria-labelledby="editVersionModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editVersionModalLabel">编辑版本 <?php echo htmlspecialchars($version['version']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="post" enctype="multipart/form-data">
                                    <div class="modal-body">
                                        <input type="hidden" name="version_id" value="<?php echo $version['id']; ?>">
                                        <div class="form-floating mb-3">
                                            <input type="text" class="form-control" id="version_<?php echo $version['id']; ?>" name="version" value="<?php echo htmlspecialchars($version['version']); ?>" required>
                                            <label for="version_<?php echo $version['id']; ?>">版本号</label>
                                        </div>
                                        <div class="form-floating mb-3">
                                            <textarea class="form-control" id="changelog_<?php echo $version['id']; ?>" name="changelog" rows="3" required><?php echo htmlspecialchars($version['changelog']); ?></textarea>
                                            <label for="changelog_<?php echo $version['id']; ?>">更新日志</label>
                                        </div>
                                        <div class="mb-3">
                                            <label for="new_app_file_<?php echo $version['id']; ?>" class="form-label">更新App文件 (可选)</label>
                                            <input class="form-control" type="file" id="new_app_file_<?php echo $version['id']; ?>" name="new_app_file">
                                            <div class="form-text">当前文件: <?php echo basename($version['file_path']); ?></div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                                        <button type="submit" class="btn btn-primary" name="edit_version">保存更改</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- 添加版本模态框 -->
    <div class="modal fade" id="addVersionModal" tabindex="-1" aria-labelledby="addVersionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addVersionModalLabel">添加新版本</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="version" name="version" placeholder="如: 1.0.0" required>
                            <label for="version">版本号</label>
                        </div>
                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="changelog" name="changelog" rows="3" placeholder="描述本次更新内容" required></textarea>
                            <label for="changelog">更新日志</label>
                        </div>
                        <div class="mb-3">
                            <label for="app_file" class="form-label">App文件</label>
                            <input class="form-control" type="file" id="app_file" name="app_file" required>
                            <a href="<?php echo htmlspecialchars($version['file_path']); ?>" class="btn btn-sm btn-primary" download>下载</a>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary" name="add_version">添加版本</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 导航栏滚动效果
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 10) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>

</html>