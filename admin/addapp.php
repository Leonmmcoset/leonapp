<?php
require_once '../config.php';

session_start();
// 检查管理员登录状态
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';
// 处理添加App请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_app'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $ageRating = $_POST['age_rating'];
    $platforms = isset($_POST['platforms']) ? json_encode($_POST['platforms']) : json_encode([]);

    // 处理表单提交
        // 验证必填字段
        $required = ['name', 'description', 'age_rating', 'platforms'];
        $errors = [];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $errors[] = ucfirst($field) . ' 不能为空';
            }
        }
    
        // 年龄分级说明验证
        if (($_POST['age_rating'] === '12+' || $_POST['age_rating'] === '17+') && empty($_POST['age_rating_description'])) {
            $errors[] = '年龄分级为12+或以上时，年龄分级说明不能为空';
        }

    
        // 处理应用图标上传
    
    // 处理平台数据
    $platforms = json_encode($_POST['platforms']);
    // 插入应用数据
    $stmt = $conn->prepare("INSERT INTO apps (name, description, age_rating, age_rating_description, platforms) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        $error = "Database error: " . $conn->error;
    }
    if ($stmt) {
        $stmt->bind_param("sssss", $name, $description, $ageRating, $_POST['age_rating_description'], $platforms);
        if ($stmt->execute() === TRUE) {
        $appId = $stmt->insert_id;

        // 保存标签关联
        if (!empty($_POST['tags'])) {
            $stmt = $conn->prepare("INSERT INTO app_tags (app_id, tag_id) VALUES (?, ?)");
            foreach ($_POST['tags'] as $tagId) {
                $stmt->bind_param("ii", $appId, $tagId);
                $stmt->execute();
            }
            $stmt->close();
        }

        // 处理上传的预览图片
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

        // 处理上传的App文件
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

        header('Location: index.php?success=App 添加成功');
        exit;
    } else {
        $error = 'App 添加失败: '. $conn->error;
    }
}
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>添加App - <?php echo APP_STORE_NAME; ?></title>
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
    </style>
</head>
<body>
<?php if (isset($error)): ?>
    <div style='color: red; padding: 10px; background-color: #ffeeee; border-radius: 5px; margin-bottom: 20px;'>
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>
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
                        <a class="nav-link active" aria-current="page" href="addapp.php">添加App</a>
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

        <h2>添加App</h2>
        <form method="post" enctype="multipart/form-data">
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="name" name="name" required>
                <label for="name">App名称</label>
            </div>
            <div class="mb-3">
                <label for="tags" class="form-label">标签</label>
                <select id="tags" name="tags[]" multiple class="form-control">
                    <?php
                    $tagResult = $conn->query("SELECT id, name FROM tags");
                    while ($tag = $tagResult->fetch_assoc()):
                    ?>
                    <option value="<?php echo $tag['id']; ?>"><?php echo htmlspecialchars($tag['name']); ?></option>
                    <?php endwhile; ?>
                </select>
                <small class="form-text text-muted">按住Ctrl键可选择多个标签</small>
            </div>
            <div class="form-floating mb-3">
                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                <label for="description">描述</label>
            </div>
            <div class="mb-3">
                <label for="age_rating" class="form-label">年龄分级</label>
                <select class="form-select" id="age_rating" name="age_rating" required>
                    <option value="3+">3+</option>
                    <option value="7+">7+</option>
                    <option value="12+">12+</option>
                    <option value="17+">17+</option>
                </select>
          </div>
          <div class="form-floating mb-3" id="ageRatingDescriptionGroup" style="display: none;">
              <textarea class="form-control" id="age_rating_description" name="age_rating_description" rows="3" placeholder="请说明为何需要此年龄分级"></textarea>
              <label for="age_rating_description">年龄分级说明</label>
              <div class="form-text">当年龄分级为12+或以上时，此项为必填</div>
          </div>

            <div class="mb-3">
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
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="version" name="version" required>
                <label for="version">版本号</label>
            </div>
            <div class="form-floating mb-3">
                <textarea class="form-control" id="changelog" name="changelog" rows="3" required></textarea>
                <label for="changelog">更新日志</label>
            </div>
            <div class="form-floating mb-3">
                <input class="form-control" type="file" id="app_file" name="app_file" required>
                <label for="app_file">App文件</label>
            </div>
            <div class="form-floating mb-3">
                <input class="form-control" type="file" id="images" name="images[]" multiple>
                <label for="images">预览图片 (可多选)</label>
            </div>
            <button type="submit" class="btn btn-primary" name="add_app">添加App</button>
            <a href="index.php" class="btn btn-secondary ms-2">取消</a>
        </form>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
