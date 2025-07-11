<?php
require_once('../includes/logger.php');
set_time_limit(0); // 添加此行取消脚本超时限制

// 引入配置文件
require_once '../config.php';

session_start();

// 检查开发者是否已登录
if (!isset($_SESSION['developer_id']) || !is_numeric($_SESSION['developer_id'])) {
    log_error('开发者会话ID不存在或无效', __FILE__, __LINE__);
    header('Location: login.php');
    exit;
}

$developerId = (int)$_SESSION['developer_id'];
log_info("上传应用的开发者ID: $developerId", __FILE__, __LINE__);
log_info("上传应用的开发者ID: $developerId", __FILE__, __LINE__);
$error = '';
$success = '';

// 检查开发者邮箱是否已验证
$stmt = $conn->prepare('SELECT is_verified FROM developers WHERE id = ?');
if (!$stmt) {
    log_error('准备验证状态查询失败: ' . $conn->error, __FILE__, __LINE__);
    $error = '系统错误，请稍后再试';
} else {
    $stmt->bind_param('i', $developerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $developer = $result->fetch_assoc();
    $stmt->close();

    log_info("开发者验证状态: " . ($developer ? ($developer['is_verified'] ? "已验证" : "未验证") : "开发者不存在"), __FILE__, __LINE__);
    if (!$developer) {
        $error = '开发者账号不存在，请重新登录。';
    } elseif (!$developer['is_verified']) {
        $error = '您的邮箱尚未验证，请先验证邮箱后再上传应用。';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 创建上传目录（如果不存在）
    $uploadDirs = ['../uploads/apps', '../uploads/images'];
    foreach ($uploadDirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    // 获取表单数据
    $appName = trim($_POST['name']);
    $appDescription = trim($_POST['description']);
    $tags = $_POST['tags'] ?? [];
    $ageRating = $_POST['age_rating'] ?? '';
    $ageRatingDescription = $_POST['age_rating_description'] ?? '';
    $platforms = isset($_POST['platforms']) ? $_POST['platforms'] : [];
    $version = trim($_POST['version']);
    $changelog = trim($_POST['changelog']);

    // 验证表单数据
    if (empty($appName) || empty($appDescription)) {
        $error = '应用名称和描述不能为空';
    } elseif (empty($changelog)) {
        $error = '更新日志不能为空';
    } elseif (empty($platforms)) {
        $error = '请至少选择一个适用平台';
    } elseif (in_array($ageRating, ['12+', '17+']) && empty($ageRatingDescription)) {
        $error = '年龄分级为12+或以上时，必须提供年龄分级说明';
    } else {
        // 检查数据库连接是否为 MySQLi 对象
if (!($conn instanceof mysqli)) {
    log_error('数据库连接错误: 连接不是MySQLi实例', __FILE__, __LINE__);
    $error = '数据库连接错误，请检查配置';
} else {
    // 处理应用文件上传
        // 获取选中的平台
        $selectedPlatforms = $_POST['platforms'] ?? [];

        // 处理应用文件上传
        $appFile = $_FILES['app_file'] ?? null;
        $appFilePath = '';
        if ($appFile && $appFile['error'] === UPLOAD_ERR_OK) {
            // 验证文件大小 (100MB)
            if ($appFile['size'] > 500 * 1024 * 1024) {
                log_error('应用文件过大: ' . number_format($appFile['size'] / 1024 / 1024, 2) . 'MB', __FILE__, __LINE__);
                        $error = '应用文件大小不能超过500MB';
            }
            $appExtension = pathinfo($appFile['name'], PATHINFO_EXTENSION);
            $appFileName = uniqid() . '.' . $appExtension;
            $appRelativePath = 'uploads/apps/' . $appFileName;
            $appFilePath = __DIR__ . '/../' . $appRelativePath;
                        if (!move_uploaded_file($appFile['tmp_name'], $appFilePath)) {
                            log_error('应用文件移动失败', __FILE__, __LINE__);
                            $error = '应用文件上传失败';
                        }
                    } else {
            // 验证标签ID是否存在
            if (!empty($tags)) {
                $tagIds = implode(',', array_map('intval', $tags));
                $tagCheckStmt = $conn->prepare("SELECT id FROM tags WHERE id IN ($tagIds)");
                if (!$tagCheckStmt) {
                    log_error('标签验证查询准备失败: ' . $conn->error, __FILE__, __LINE__);
                    $error = '系统错误，请稍后再试';
                } else {
                    $tagCheckStmt->execute();
                    $tagResult = $tagCheckStmt->get_result();
                    $validTagIds = [];
                    while ($tag = $tagResult->fetch_assoc()) {
                        $validTagIds[] = $tag['id'];
                    }
                    $tagCheckStmt->close();
                    
                    $invalidTags = array_diff($tags, $validTagIds);
                    if (!empty($invalidTags)) {
                        log_error('无效的标签ID: ' . implode(',', $invalidTags), __FILE__, __LINE__);
                        $error = '选择了无效的标签，请刷新页面重试';
                    }
                }
            }
                        $error = '应用文件上传错误: ' . ($appFile ? $appFile['error'] : '未找到文件');
                    }

        // 处理图片上传
        $imagePaths = [];
        $images = $_FILES['images'] ?? null;
        if ($images && is_array($images['tmp_name'])) {
            foreach ($images['tmp_name'] as $key => $tmpName) {
                if ($images['error'][$key] === UPLOAD_ERR_OK) {
                    // 验证图片大小 (10MB)
                    if ($images['size'][$key] > 10 * 1024 * 1024) {
                        log_error('图片过大: ' . $images['name'][$key] . ' (' . number_format($images['size'][$key] / 1024 / 1024, 2) . 'MB)', __FILE__, __LINE__);
                            $error = '图片 ' . $images['name'][$key] . ' 大小不能超过10MB';
                    }
                    
                    $imageRelativePath = 'uploads/images/' . uniqid() . '.' . pathinfo($images['name'][$key], PATHINFO_EXTENSION);
            $imagePath = __DIR__ . '/../' . $imageRelativePath;
            $target_dir = dirname($imagePath);
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
                    if (move_uploaded_file($tmpName, $imagePath)) {
                        $imagePaths[] = $imageRelativePath;
                    } else {
                        log_error('图片文件移动失败: ' . $images['name'][$key], __FILE__, __LINE__);
                    }
                }
            }
        }

        if (empty($error)) {
            // 开始数据库事务
            $conn->begin_transaction();
            try {
                // 确保必要变量存在，防止空值导致 SQL 错误
                if (!isset($appName) || !isset($appDescription) || !isset($developerId) || !isset($version) || !isset($changelog) || !isset($ageRating) || !isset($ageRatingDescription)) {
                    throw new Exception('缺少必要的上传参数');
                }

                // 获取开发者邮箱
                $userStmt = $conn->prepare('SELECT email FROM developers WHERE id = ?');
                $userStmt->bind_param('i', $developerId);
                $userStmt->execute();
                $userResult = $userStmt->get_result();
                $user = $userResult->fetch_assoc();
                $developerEmail = $user['email'] ?? '';
                $userStmt->close();

                if (empty($developerEmail)) {
                    throw new Exception('无法获取开发者邮箱信息');
                }

                // 插入应用基本信息
                $stmt = $conn->prepare('INSERT INTO apps (name, description, platforms, status, age_rating, age_rating_description, version, changelog, file_path, developer_id, developer_email, created_at) VALUES (?, ?, ?, \'pending\', ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)');
                if (!$stmt) {
                    throw new Exception('应用基本信息查询准备失败: ' . $conn->error);
                }
                // 确保平台数据正确编码
                $platforms = $_POST['platforms'] ?? [];
                $platforms_json = json_encode($platforms);
                // 此处需确认预处理语句占位符数量，确保与 bind_param 参数数量一致，示例仅示意，实际需根据表结构调整
                // 修正参数绑定，添加file_path参数以匹配SQL占位符数量
                // 修正参数类型字符串长度，确保与10个参数匹配
                // 修正类型字符串长度，10个参数对应10个类型字符
                // 最终修正：10个参数对应10个类型字符
                // 根据参数实际类型修正类型字符串（整数用i，字符串用s）
                // 移除多余的$status参数，匹配SQL中9个占位符
                // 修正age_rating_description类型为字符串，并确保9个参数与占位符匹配
                // 修复变量名错误：使用已验证的$appFilePath替换未定义的$file_path
                $stmt->bind_param('ssssssssis', $appName, $appDescription, $platforms_json, $ageRating, $ageRatingDescription, $version, $changelog, $appRelativePath, $developerId, $developerEmail);
                if (!$stmt->execute()) {
                    throw new Exception('应用基本信息查询执行失败: ' . $stmt->error);
                }
                $appId = $stmt->insert_id;
                log_info("应用已插入数据库: ID=$appId, 状态=pending", __FILE__, __LINE__);
                $stmt->close();

                log_info("开始处理应用关联数据: ID=$appId", __FILE__, __LINE__);
                // 插入应用标签关联
                foreach ($tags as $tagId) {
                    $tagStmt = $conn->prepare('INSERT INTO app_tags (app_id, tag_id) VALUES (?, ?)');
                    if (!$tagStmt) {
                        throw new Exception('标签关联查询准备失败: ' . $conn->error);
                    }
                    $tagStmt->bind_param('ii', $appId, $tagId);
                    if (!$tagStmt->execute()) {
                        throw new Exception('标签关联查询执行失败: ' . $tagStmt->error);
                    }
                    $tagStmt->close();
                }

                // 插入应用图片
                foreach ($imagePaths as $imageRelativePath) {
                    $imageStmt = $conn->prepare('INSERT INTO app_images (app_id, image_path) VALUES (?, ?)');
                    if (!$imageStmt) {
                        throw new Exception('图片关联查询准备失败: ' . $conn->error);
                    }
                    $imageStmt->bind_param('is', $appId, $imageRelativePath);
                    if (!$imageStmt->execute()) {
                        throw new Exception('图片关联查询执行失败: ' . $imageStmt->error);
                    }
                    $imageStmt->close();
                }

                // 插入应用版本信息
                $versionStmt = $conn->prepare('INSERT INTO app_versions (app_id, version, changelog, file_path, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)');
                if (!$versionStmt) {
                    throw new Exception('版本信息查询准备失败: ' . $conn->error);
                }
                $versionStmt->bind_param('isss', $appId, $version, $changelog, $appRelativePath);
                if (!$versionStmt->execute()) {
                    throw new Exception('版本信息查询执行失败: ' . $versionStmt->error);
                }
                $versionStmt->close();

                log_info("所有关联数据处理完成，准备提交事务: ID=$appId", __FILE__, __LINE__);
                // 提交事务
                $conn->commit();
                log_info("应用上传成功: ID=$appId, 状态=pending", __FILE__, __LINE__);
                $success = '应用上传成功，请等待管理员审核';
            } catch (Exception $e) {
                // 回滚事务
                $conn->rollback();
                log_error('应用上传事务失败(ID=$appId): ' . $e->getMessage(), __FILE__, __LINE__);
                $error = '上传应用时发生错误，请稍后再试';
            }
        }
}
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>上传应用 - <?php echo APP_STORE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="../css/animations.css">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <!-- 自定义CSS -->
    <link rel="stylesheet" href="../styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Fluent Design 模糊效果 -->
    <style>
        .blur-bg {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.5);
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .btn-primary {
            background-color: #007BFF;
            border-color: #007BFF;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        .back-link {
            text-align: center;
            margin-top: 1rem;
        }
        #ageRatingDescriptionGroup {
            display: none;
        }
    </style>
    <!-- Bootstrap JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>

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
                        <a class="nav-link" href="dashboard.php">应用仪表盘</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="upload_app.php">上传应用</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">退出登录</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
    <style>
        .form-group {
            margin-bottom: 1rem;
        }
        .btn-primary {
            background-color: #007BFF;
            border-color: #007BFF;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        .back-link {
            text-align: center;
            margin-top: 1rem;
        }
        #ageRatingDescriptionGroup {
            display: none;
        }
    </style>
    <script>
        // 年龄分级说明显示控制
        document.addEventListener('DOMContentLoaded', function() {
            const ageRating = document.getElementById('age_rating');
            const ageDescGroup = document.getElementById('ageRatingDescriptionGroup');
            const ageDescInput = document.getElementById('age_rating_description');

            function toggleAgeDescription() {
                if (['12+', '17+'].includes(ageRating.value)) {
                    ageDescGroup.style.display = 'block';
                    ageDescInput.required = true;
                } else {
                    ageDescGroup.style.display = 'none';
                    ageDescInput.required = false;
                }
            }

            ageRating.addEventListener('change', toggleAgeDescription);
            toggleAgeDescription(); // 初始状态检查

            // 文件类型验证
            const appFileInput = document.getElementById('app_file');
            const imageInput = document.getElementById('images');
            const allowedAppTypes = { 'android': ['apk'], 'ios': ['ipa'] };
            const allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif'];

            appFileInput.addEventListener('change', function(e) {
                if (this.files.length > 0) {
                    const file = this.files[0];
                    const ext = file.name.split('.').pop().toLowerCase();
                    if (file.size > 500 * 1024 * 1024) { // 100MB限制
                        Swal.fire({
                title: '提示',
                text: '文件大小不能超过500MB',
                icon: 'warning',
                confirmButtonText: '确定'
            });
                        this.value = '';
                    }
                }
            });

            imageInput.addEventListener('change', function(e) {
                if (this.files.length > 0) {
                    for (let i = 0; i < this.files.length; i++) {
                        const file = this.files[i];
                        if (file.size > 10 * 1024 * 1024) { // 10MB限制
                            Swal.fire({
                title: '提示',
                text: `图片 ${file.name} 大小不能超过10MB`,
                icon: 'warning',
                confirmButtonText: '确定'
            });
                            this.value = '';
                            return;
                        }
                    }
                }
            });

            // 平台子选项显示控制
            document.getElementById('windows').addEventListener('change', function() {
                const suboptions = document.getElementById('windows_suboptions');
                suboptions.style.display = this.checked ? 'block' : 'none';
                if (!this.checked) {
                    document.querySelectorAll('input[name="windows_version"]').forEach(radio => radio.checked = false);
                }
            });

            document.getElementById('linux').addEventListener('change', function() {
                const suboptions = document.getElementById('linux_suboptions');
                suboptions.style.display = this.checked ? 'block' : 'none';
                if (!this.checked) {
                    document.querySelectorAll('input[name="linux_distribution"]').forEach(radio => radio.checked = false);
                }
            });
        });
    </script>
</head>
<body>
    <div class="container mt-5 mb-5 col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h2 class="h4 mb-0">上传应用</h2>
            </div>
            <div class="card-body p-4">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <div class="alert alert-warning mb-3">
                <strong>警告：</strong>如果该应用非您本人开发，请务必添加"转载"标签。
            </div>
            <div class="form-group mb-3">
                <label for="name" class="form-label">应用名称</label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>
            <div class="form-group mb-3">
                <label for="tags" class="form-label">标签</label>
                <select id="tags" name="tags[]" multiple class="form-select" size="3">
                    <?php
                    $tagResult = $conn->query("SELECT id, name FROM tags");
                    while ($tag = $tagResult->fetch_assoc()):
                    ?>
                    <option value="<?php echo $tag['id']; ?>"><?php echo htmlspecialchars($tag['name']); ?></option>
                    <?php endwhile; ?>
                </select>
                <small>按住Ctrl键可选择多个标签</small>
            </div>
            <div class="form-group mb-3">
                <label for="description" class="form-label">应用描述</label>
                <textarea id="description" name="description" rows="5" class="form-control" required></textarea>
            </div>
            <div class="form-group mb-3">
                <label for="age_rating" class="form-label">年龄分级</label>
                <select class="form-select" id="age_rating" name="age_rating" required>
                    <option value="3+">3+</option>
                    <option value="7+">7+</option>
                    <option value="12+">12+</option>
                    <option value="17+">17+</option>
                </select>
            </div>
            <div class="form-group mb-3" id="ageRatingDescriptionGroup">
                <label for="age_rating_description" class="form-label">年龄分级说明</label>
                <textarea class="form-control" id="age_rating_description" name="age_rating_description" rows="3" placeholder="请说明为何需要此年龄分级"></textarea>
                <small>当年龄分级为12+或以上时，此项为必填</small>
            </div>
            <div class="form-group mb-3">
                <label class="form-label">适用平台</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="android" id="android" name="platforms[]">
                    <label class="form-check-label" for="android">Android</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="ios" id="ios" name="platforms[]">
                    <label class="form-check-label" for="ios">iOS</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="windows" id="windows" name="platforms[]">
                    <label class="form-check-label" for="windows">Windows</label>
                </div>
                <div id="windows_suboptions" class="ms-4 mt-2" style="display: none;">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="windows_version" id="windows_xp" value="windows_xp">
                        <label class="form-check-label" for="windows_xp">XP以前</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="windows_version" id="windows_win7" value="windows_win7">
                        <label class="form-check-label" for="windows_win7">Win7以后</label>
                    </div>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="macos" id="macos" name="platforms[]">
                    <label class="form-check-label" for="macos">macOS</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="linux" id="linux" name="platforms[]">
                    <label class="form-check-label" for="linux">Linux</label>
                </div>
                <div id="linux_suboptions" class="ms-4 mt-2" style="display: none;">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="linux_distribution" id="linux_ubuntu" value="linux_ubuntu">
                        <label class="form-check-label" for="linux_ubuntu">Ubuntu</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="linux_distribution" id="linux_arch" value="linux_arch">
                        <label class="form-check-label" for="linux_arch">Arch Linux</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="linux_distribution" id="linux_centos" value="linux_centos">
                        <label class="form-check-label" for="linux_centos">CentOS</label>
                    </div>
                </div>
            </div>
            <div class="form-group mb-3">
                <label for="app_file" class="form-label">应用文件</label>
                <input type="file" id="app_file" name="app_file" class="form-control" required>
            </div>
            <div class="form-group mb-3">
                <label for="version" class="form-label">版本号</label>
                <input type="text" id="version" name="version" class="form-control" required placeholder="例如: 1.0.0">
            </div>
            <div class="form-group mb-3">
                <label for="changelog" class="form-label">更新日志</label>
                <textarea id="changelog" name="changelog" rows="3" class="form-control" required></textarea>
            </div>
            <div class="form-group mb-4">
                <label for="images" class="form-label">预览图片</label>
                <input type="file" id="images" name="images[]" multiple accept="image/*" class="form-control">
                <small>可选择多张图片</small>
            </div>
            <input type="submit" value="上传" class="btn btn-primary w-100 py-2">
        </form>
        <div class="back-link mt-4">
            <a href="dashboard.php" class="btn btn-outline-secondary w-100">返回仪表盘</a>
        </div>
            </div>
        </div>
    </div>
</body>
</html>