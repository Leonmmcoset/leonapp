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
    header('Location: index.php?error=无效的App ID');
    exit;
}
$appId = $_GET['id'];

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
$platforms = json_decode($app['platforms'], true);

$success = '';
$error = '';
// 处理编辑App请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_app'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $ageRating = $_POST['age_rating'];
    $newPlatforms = json_encode($_POST['platforms'] ?? []);

    // 处理表单提交
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 验证必填字段
        $required = ['name', 'description', 'age_rating', 'platforms'];
        $errors = [];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $errors[] = ucfirst($field) . ' 不能为空';
            }
        }
    
        // 年龄分级验证
        if (($_POST['age_rating'] === '12+' || $_POST['age_rating'] === '17+') && empty($_POST['age_rating_description'])) {
            $errors[] = '年龄分级为12+或以上时，年龄分级说明不能为空';
        }
    
        // 处理应用图标上传（如果有新上传）
        if (!empty($_FILES['images']['name'][0])) {
            $uploadDir = '../images/';
            foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                $fileName = basename($_FILES['images']['name'][$key]);
                $targetPath = $uploadDir . $fileName;
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $insertImageSql = "INSERT INTO app_images (app_id, image_path) VALUES (?, ?)";
                    $imgStmt = $conn->prepare($insertImageSql);
                    $imgStmt->bind_param("is", $appId, $targetPath);
                    $imgStmt->execute();
                }
            }
        }

        // 处理新上传的App文件
        if (!empty($_FILES['app_file']['name'])) {
            $uploadDir = '../files/';
            $fileName = basename($_FILES['app_file']['name']);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['app_file']['tmp_name'], $targetPath)) {
                $version = $_POST['version'];
                $changelog = $_POST['changelog'];
                $insertVersionSql = "INSERT INTO app_versions (app_id, version, changelog, file_path) VALUES (?, ?, ?, ?)";
                $verStmt = $conn->prepare($insertVersionSql);
                $verStmt->bind_param("isss", $appId, $version, $changelog, $targetPath);
                $verStmt->execute();
            }
        }

        // 更新标签关联
            $stmt = $conn->prepare("DELETE FROM app_tags WHERE app_id = ?");
            $stmt->bind_param("i", $appId);
            $stmt->execute();
            $stmt->close();

            if (!empty($_POST['tags'])) {
                $stmt = $conn->prepare("INSERT INTO app_tags (app_id, tag_id) VALUES (?, ?)");
                foreach ($_POST['tags'] as $tagId) {
                    $stmt->bind_param("ii", $appId, $tagId);
                    $stmt->execute();
                }
                $stmt->close();
            }

            header('Location: index.php?success=App 更新成功');
            exit;
        } else {
            $error = 'App 更新失败: '. $conn->error;
        }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>编辑App - <?php echo APP_STORE_NAME; ?></title>
    <!-- Bootstrap CSS -->
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
                        <a class="nav-link active" aria-current="page" href="editapp.php?id=<?php echo $appId; ?>">编辑App</a>
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

        <h2>编辑App: <?php echo htmlspecialchars($app['name']); ?></h2>
        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="name" class="form-label">App名称</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($app['name']); ?>" required>
            </div>
        <div class="mb-3">
            <label for="tags" class="form-label">标签</label>
            <select id="tags" name="tags[]" multiple class="form-control">
                <?php
                $selectedTags = [];
                $tagQuery = $conn->prepare("SELECT tag_id FROM app_tags WHERE app_id = ?");
                $tagQuery->bind_param("i", $appId);
                $tagQuery->execute();
                $tagResult = $tagQuery->get_result();
                while ($tag = $tagResult->fetch_assoc()) {
                    $selectedTags[] = $tag['tag_id'];
                }
                
                $allTags = $conn->query("SELECT id, name FROM tags");
                while ($tag = $allTags->fetch_assoc()):
                $selected = in_array($tag['id'], $selectedTags) ? 'selected' : '';
                ?>
                <option value="<?php echo $tag['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($tag['name']); ?></option>
                <?php endwhile; ?>
            </select>
            <div class="form-text">按住Ctrl键可选择多个标签</div>
        </div>
        <div class="mb-3">
                <label for="description" class="form-label">描述</label>
                <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($app['description']); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="age_rating" class="form-label">年龄分级</label>
                <select class="form-select" id="age_rating" name="age_rating" required>
                    <option value="3+" <?php echo $app['age_rating'] === '3+' ? 'selected' : ''; ?>>3+</option>
                    <option value="7+" <?php echo $app['age_rating'] === '7+' ? 'selected' : ''; ?>>7+</option>
                    <option value="12+" <?php echo $app['age_rating'] === '12+' ? 'selected' : ''; ?>>12+</option>
                    <option value="17+" <?php echo $app['age_rating'] === '17+' ? 'selected' : ''; ?>>17+</option>
                </select>
          </div>
          <div class="mb-3" id="ageRatingDescriptionGroup" style="display: none;">
              <label for="age_rating_description" class="form-label">年龄分级说明</label>
              <textarea class="form-control" id="age_rating_description" name="age_rating_description" rows="3" placeholder="请说明为何需要此年龄分级"><?php echo htmlspecialchars($app['age_rating_description'] ?? ''); ?></textarea>
              <div class="form-text">当年龄分级为12+或以上时，此项为必填</div>
          </div>
            <div class="mb-3">
                <label class="form-label">适用平台</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="android" id="android" name="platforms[]" <?php echo in_array('android', $platforms) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="android">Android</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="ios" id="ios" name="platforms[]" <?php echo in_array('ios', $platforms) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="ios">iOS</label>
                </div>
                <?php
                $windowsChecked = false;
                $windowsVersion = '';
                foreach ($platforms as $p) {
                    if (strpos($p, 'windows_') === 0) {
                        $windowsChecked = true;
                        $windowsVersion = $p;
                        break;
                    }
                }
                ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="windows" id="windows" name="platforms[]" <?php echo $windowsChecked ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="windows">Windows</label>
                </div>
                <div id="windows_suboptions" class="ms-4 mt-2" style="display: <?php echo $windowsChecked ? 'block' : 'none'; ?>">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="windows_version" id="windows_xp" value="windows_xp" <?php echo $windowsVersion === 'windows_xp' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="windows_xp">XP以前</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="windows_version" id="windows_win7" value="windows_win7" <?php echo $windowsVersion === 'windows_win7' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="windows_win7">Win7以后</label>
                    </div>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="macos" id="macos" name="platforms[]" <?php echo in_array('macos', $platforms) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="macos">macOS</label>
                </div>
                <?php
                $linuxChecked = false;
                $linuxVersion = '';
                foreach ($platforms as $p) {
                    if (strpos($p, 'linux_') === 0) {
                        $linuxChecked = true;
                        $linuxVersion = $p;
                        break;
                    }
                }
                ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="linux" id="linux" name="platforms[]" <?php echo $linuxChecked ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="linux">Linux</label>
                </div>
                <div id="linux_suboptions" class="ms-4 mt-2" style="display: <?php echo $linuxChecked ? 'block' : 'none'; ?>">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="linux_distribution" id="linux_ubuntu" value="linux_ubuntu" <?php echo $linuxVersion === 'linux_ubuntu' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="linux_ubuntu">Ubuntu</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="linux_distribution" id="linux_arch" value="linux_arch" <?php echo $linuxVersion === 'linux_arch' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="linux_arch">Arch Linux</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="linux_distribution" id="linux_centos" value="linux_centos" <?php echo $linuxVersion === 'linux_centos' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="linux_centos">CentOS</label>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label for="version" class="form-label">新版本号</label>
                <input type="text" class="form-control" id="version" name="version" placeholder="如: 1.0.1">
                <div class="form-text">仅在上传新安装包时填写</div>
            </div>
            <div class="mb-3">
                <label for="changelog" class="form-label">更新日志</label>
                <textarea class="form-control" id="changelog" name="changelog" rows="3" placeholder="描述本次更新内容"></textarea>
            </div>
            <div class="mb-3">
                <label for="app_file" class="form-label">新App文件 (可选)</label>
                <input class="form-control" type="file" id="app_file" name="app_file">
            </div>
            <div class="mb-3">
                <label for="images" class="form-label">新增预览图片 (可选, 可多选)</label>
                <input class="form-control" type="file" id="images" name="images[]" multiple>
            </div>
            <button type="submit" class="btn btn-primary" name="edit_app">更新App</button>
            <a href="index.php" class="btn btn-secondary ms-2">取消</a>
        </form>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
          // 年龄分级说明显示控制
          // 年龄分级说明显示控制
          const ageRatingSelect = document.getElementById('age_rating');
          const descriptionGroup = document.getElementById('ageRatingDescriptionGroup');
          const descriptionInput = document.getElementById('age_rating_description');
          
          function toggleAgeDescription() {
              const selectedRating = ageRatingSelect.value;
              if (selectedRating === '12+' || selectedRating === '17+') {
                  descriptionGroup.style.display = 'block';
                  descriptionInput.required = true;
              } else {
                  descriptionGroup.style.display = 'none';
                  descriptionInput.required = false;
              }
          }
          
          ageRatingSelect.addEventListener('change', toggleAgeDescription);
          // 初始加载时检查
          toggleAgeDescription();
    
        // 导航栏滚动效果
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 10) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
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

        // 表单提交验证
        document.querySelector('form').addEventListener('submit', function(e) {
            // 验证Windows子选项
            if (document.getElementById('windows').checked && !document.querySelector('input[name="windows_version"]:checked')) {
                e.preventDefault();
                Swal.fire({
                title: '提示',
                text: '请选择Windows版本（XP以前或Win7以后）',
                icon: 'warning',
                confirmButtonText: '确定'
            });
                return;
            }

            // 验证Linux子选项
            if (document.getElementById('linux').checked && !document.querySelector('input[name="linux_distribution"]:checked')) {
                e.preventDefault();
                Swal.fire({
                title: '提示',
                text: '请选择Linux发行版（Ubuntu、Arch Linux或CentOS）',
                icon: 'warning',
                confirmButtonText: '确定'
            });
                return;
            }

            // 更新平台值包含子选项信息
            if (document.getElementById('windows').checked) {
                document.getElementById('windows').value = document.querySelector('input[name="windows_version"]:checked').value;
            }
            if (document.getElementById('linux').checked) {
                document.getElementById('linux').value = document.querySelector('input[name="linux_distribution"]:checked').value;
            }
        });
    </script>
</body>
</html>

    <?php
// 更新应用数据
$stmt = $conn->prepare("UPDATE apps SET name=?, description=?, age_rating=?, age_rating_description=?, platforms=?, updated_at=NOW() WHERE id=?");
$stmt->bind_param("sssssi", $name, $description, $age_rating, $_POST['age_rating_description'], $platformsJson, $appId);

// ... existing code ...
?>