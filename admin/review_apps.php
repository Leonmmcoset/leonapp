<?php
require_once '../config.php';
require_once '../vendor/autoload.php';
require_once '../includes/logger.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
// 检查管理员登录状态
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';

// 处理审核操作
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_action'])) {
    $appId = $_POST['app_id'];
    $action = $_POST['review_action'];
    $rejectionReason = urldecode($_POST['rejection_reason'] ?? '');

    // 验证应用ID
    if (!is_numeric($appId)) {
        $error = '无效的应用ID';
    } else {
        // 检查数据库连接
        if (!($conn instanceof mysqli)) {
            log_error('数据库连接错误: 连接不是MySQLi实例', __FILE__, __LINE__);
            $error = '数据库连接错误，请检查配置';
        } else {
            // 更新应用状态
            $status = $action === 'approve' ? 'approved' : 'rejected';
            $stmt = $conn->prepare("UPDATE apps SET status = ?, rejection_reason = ? WHERE id = ?");
            if (!$stmt) {
                $error = "数据库错误: " . $conn->error;
            } else {
                $stmt->bind_param("ssi", $status, $rejectionReason, $appId);
                if ($stmt->execute()) {
                    // 获取应用信息和开发者邮箱
                    $getAppStmt = $conn->prepare("SELECT name, developer_email FROM apps WHERE id = ?");
                    $getAppStmt->bind_param("i", $appId);
                    $getAppStmt->execute();
                    $appResult = $getAppStmt->get_result();
                    $appInfo = $appResult->fetch_assoc();
                    $getAppStmt->close();

                    $success = '应用审核已更新';
                    $appName = $appInfo['name'] ?? '未知应用';
                    $devEmail = $appInfo['developer_email'] ?? '';

                    // 发送邮件通知
                    if (!empty($devEmail)) {
                        $mail = new PHPMailer(true);
                        try {
                            // 服务器配置
                            $mail->isSMTP();
                            $mail->Host = SMTP_HOST;
                            $mail->Port = SMTP_PORT;
                            $mail->SMTPSecure = SMTP_ENCRYPTION;
                            $mail->SMTPAuth = true;
                            $mail->Username = SMTP_USERNAME;
                            $mail->Password = SMTP_PASSWORD;
                        $mail->CharSet = 'UTF-8';
                        $mail->isHTML(true);
                        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                            $mail->addAddress($devEmail);

                            // 邮件内容
                            if ($status === 'approved') {
                                $mail->Subject = '应用审核通过通知';
                                $mail->Body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px;'>
                                    <h2 style='color: #2c3e50;'>应用审核通过通知</h2>
                                    <p>您好，</p>
                                    <p>您的应用 <strong>{$appName}</strong> 已成功通过审核！</p>
                                    <p>现在可以在应用商店中查看您的应用。</p>
                                    <p style='margin-top: 20px; color: #666;'>此致<br>应用商店团队</p>
                                </div>";
                            } else {
                                $mail->Subject = '应用审核未通过通知';
                                $mail->Body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px;'>
                                    <h2 style='color: #e74c3c;'>应用审核未通过通知</h2>
                                    <p>您好，</p>
                                    <p>您的应用 <strong>{$appName}</strong> 未通过审核。</p>
                                    <p>原因：<br>{$rejectionReason}</p>
                                    <p style='margin-top: 20px; color: #666;'>此致<br>应用商店团队</p>
                                </div>";
                            }

                            $mail->send();
                            $success .= '，邮件通知已发送';
                        } catch (Exception $e) {
                            log_error("邮件发送失败: {$mail->ErrorInfo}", __FILE__, __LINE__);
                            $error = "审核状态已更新，但邮件发送失败: {$mail->ErrorInfo}";
                        }
                    }
                } else {
                    $error = '更新审核状态失败: ' . $conn->error;
                }
                $stmt->close();
            }
        }
    }
}

// 获取待审核应用列表
$pendingApps = [];
if (!($conn instanceof mysqli)) {
    log_error('数据库连接错误: 连接不是MySQLi实例', __FILE__, __LINE__);
    $error = '数据库连接错误，请检查配置';
} else {
    $stmt = $conn->prepare("SELECT a.id, a.name, a.description, a.status, a.created_at 
                           FROM apps a
                           WHERE a.status = 'pending'
                           ORDER BY a.created_at DESC");
    if (!$stmt) {
        $error = "数据库错误: " . $conn->error;
    } else {
        $stmt->execute();
        $result = $stmt->get_result();
        $pendingApps = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>应用审核 - <?php echo APP_STORE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- 自定义CSS -->
    <link rel="stylesheet" href="../styles.css">
    <!-- Fluent Design 模糊效果 -->
    <style>
        .blur-bg {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.5);
        }
        .app-card {
            transition: transform 0.2s;
        }
        .app-card:hover {
            transform: scale(1.02);
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-light blur-bg">
        <div class="container">
            <a class="navbar-brand" href="../index.php"><?php echo APP_STORE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">App列表</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="addapp.php">添加App</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="review_apps.php">应用审核</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?logout=true">退出登录</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <h2>应用审核</h2>
        <p class="text-muted">待审核应用: <?php echo count($pendingApps); ?></p>

        <?php if (empty($pendingApps)): ?>
            <div class="alert alert-info">没有待审核的应用</div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($pendingApps as $app): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card app-card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($app['name']); ?></h5>
                            </div>
                            <div class="card-body">
                                <p class="card-text"><?php echo htmlspecialchars($app['description']); ?></p>
                                <?php 
                                    $appId = $app['id'];
                                    // 获取下载链接，假设在 app_versions 表中
                                    $getDownloadLinkStmt = $conn->prepare("SELECT file_path FROM app_versions WHERE app_id = ? ORDER BY created_at DESC LIMIT 1");
                                    if ($getDownloadLinkStmt) {
                                        $getDownloadLinkStmt->bind_param("i", $appId);
                                        $getDownloadLinkStmt->execute();
                                        $downloadLinkResult = $getDownloadLinkStmt->get_result();
                                        $downloadLinkInfo = $downloadLinkResult->fetch_assoc();
                                        $downloadLink = $downloadLinkInfo ? $downloadLinkInfo['file_path'] : '';
                                        $getDownloadLinkStmt->close();
                                    } else {
                                        $downloadLink = '';
                                        log_error('数据库准备语句错误: ' . $conn->error, __FILE__, __LINE__);
                                    }

                                    // 获取应用标签
                                    $getTagsStmt = $conn->prepare("SELECT t.name FROM tags t JOIN app_tags at ON t.id = at.tag_id WHERE at.app_id = ?");
                                    if ($getTagsStmt) {
                                        $getTagsStmt->bind_param("i", $appId);
                                        $getTagsStmt->execute();
                                        $tagsResult = $getTagsStmt->get_result();
                                        $tags = [];
                                        while ($tag = $tagsResult->fetch_assoc()) {
                                            $tags[] = $tag['name'];
                                        }
                                        $tagString = implode(', ', $tags);
                                        $getTagsStmt->close();
                                    } else {
                                        $tagString = '';
                                        log_error('数据库准备语句错误: ' . $conn->error, __FILE__, __LINE__);
                                    }
                                ?>
                                <?php if (!empty($downloadLink)): ?>
                                    <p class="card-text"><strong>下载链接:</strong> <a href="<?php echo htmlspecialchars('../' . $downloadLink); ?>" target="_blank">点击下载</a></p>
                                <?php endif; ?>
                                <?php if (!empty($tagString)): ?>
                                    <p class="card-text"><strong>标签:</strong> <?php echo htmlspecialchars($tagString); ?></p>
                                <?php endif; ?>
                                <p class="card-text"><strong>开发者:</strong> <?php echo htmlspecialchars($app['username']); ?></p>
                                <p class="card-text"><strong>提交时间:</strong> <?php echo htmlspecialchars($app['created_at']); ?></p>
                                <p class="card-text"><strong>描述:</strong> <?php echo nl2br(htmlspecialchars($app['description'])); ?></p>

                                <!-- 获取应用图片 -->
                                <?php
                                $images = [];
                                $stmt = $conn->prepare("SELECT image_path FROM app_images WHERE app_id = ?");
                                $stmt->bind_param("i", $app['id']);
                                $stmt->execute();
                                $imgResult = $stmt->get_result();
                                while ($img = $imgResult->fetch_assoc()) {
                                    $images[] = $img['image_path'];
                                }
                                $stmt->close();
                                ?>

                                <?php if (!empty($images)): ?>
                                    <div class="mb-3">
                                        <strong>预览图片:</strong><br>
                                        <img src="<?php echo htmlspecialchars($images[0]); ?>" alt="应用截图" class="img-thumbnail" style="max-width: 200px;">
                                    </div>
                                <?php endif; ?>

                                <form method="post" class="mt-3">
                                    <input type="hidden" name="app_id" value="<?php echo $app['id']; ?>">
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="review_action" value="approve" class="btn btn-success flex-grow-1">通过</button>
                                        <button type="button" class="btn btn-danger flex-grow-1" onclick="showRejectReason(<?php echo $app['id']; ?>, '<?php echo addslashes(htmlspecialchars($app['name'])); ?>')">拒绝</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <script>
function showRejectReason(appId, appName) {
    Swal.fire({
        title: '拒绝应用: ' + appName,
        html: '<textarea id="rejectionReason" class="swal2-textarea" rows="3" placeholder="请详细说明拒绝原因，帮助开发者改进应用"></textarea>',
        confirmButtonText: '确认拒绝',
        cancelButtonText: '取消',
        showCancelButton: true,
        validationMessage: '请输入拒绝原因',
        preConfirm: () => {
            const reason = document.getElementById('rejectionReason').value;
            if (!reason) {
                Swal.showValidationMessage('请输入拒绝原因');
            }
            return reason;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'post';
            form.innerHTML = `
                <input type="hidden" name="app_id" value="${appId}">
                <input type="hidden" name="review_action" value="reject">
                <input type="hidden" name="rejection_reason" value="${encodeURIComponent(result.value)}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
</body>
</html>