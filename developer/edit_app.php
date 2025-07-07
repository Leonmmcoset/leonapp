<?php
// 引入配置文件
require_once '../config.php';

session_start();

// 检查开发者是否已登录
if (!isset($_SESSION['developer_id'])) {
    header('Location: login.php');
    exit;
}

$developerId = $_SESSION['developer_id'];
$error = '';
$success = '';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$appId = $_GET['id'];
$app = [];

// 检查数据库连接是否为 MySQLi 对象
if (!($conn instanceof mysqli)) {
    log_error('数据库连接错误: 连接不是MySQLi实例', __FILE__, __LINE__);
    $error = '数据库连接错误，请检查配置';
    header('Location: dashboard.php');
    exit;
}

// 获取所有标签
$tags = [];
$tagStmt = $conn->query('SELECT id, name FROM tags');
while ($tag = $tagStmt->fetch_assoc()) {
    $tags[] = $tag;
}
$tagStmt->close();

// 获取应用现有标签
$appTags = [];
$appTagStmt = $conn->prepare('SELECT tag_id FROM app_tags WHERE app_id = ?');
$appTagStmt->bind_param('i', $appId);
$appTagStmt->execute();
$appTagResult = $appTagStmt->get_result();
while ($tag = $appTagResult->fetch_assoc()) {
    $appTags[] = $tag['tag_id'];
}
$appTagStmt->close();

// 获取应用信息并验证开发者权限
$stmt = $conn->prepare('SELECT id, name, description, version, changelog, age_rating, age_rating_description, platforms, file_path FROM apps WHERE id = ? AND developer_id = ?');
if (!$stmt) {
    log_error('获取应用信息查询准备失败: ' . $conn->error, __FILE__, __LINE__);
    $error = '获取应用信息时发生错误，请稍后再试';
    header('Location: dashboard.php');
    exit;
}
$stmt->bind_param('ii', $appId, $developerId);
if (!$stmt->execute()) {
    log_error('获取应用信息查询执行失败: ' . $stmt->error, __FILE__, __LINE__);
    $error = '获取应用信息时发生错误，请稍后再试';
    header('Location: dashboard.php');
    exit;
}
$result = $stmt->get_result();
$app = $result->fetch_assoc();
if (!$app) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appName = trim($_POST['name']);
    $appDescription = trim($_POST['description']);
    $version = trim($_POST['version']);
    $changelog = trim($_POST['changelog']);
    $ageRating = $_POST['age_rating'];
    $ageRatingDescription = trim($_POST['age_rating_description']);
    $platforms = $_POST['platforms'] ?? [];
    $platforms_json = json_encode($platforms);
    $appFilePath = $app['file_path']; // 默认使用现有文件路径

    // 处理应用文件上传
    if (!empty($_FILES['app_file']['tmp_name'])) {
        $uploadDir = '../uploads/apps/';
        $fileExtension = pathinfo($_FILES['app_file']['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid() . '.' . $fileExtension;
        $targetPath = $uploadDir . $newFileName;

        // 验证文件类型和大小
        $allowedTypes = ['apk', 'exe', 'jar', 'crx', 'ini'];
        if (!in_array($fileExtension, $allowedTypes)) {
            $error = '不支持的文件类型，请上传 ' . implode(', ', $allowedTypes) . ' 格式的文件';
        } elseif ($_FILES['app_file']['size'] > 50 * 1024 * 1024) { // 50MB
            $error = '文件大小不能超过50MB';
        } elseif (!move_uploaded_file($_FILES['app_file']['tmp_name'], $targetPath)) {
            $error = '文件上传失败，请检查服务器权限';
        } else {
            // 删除旧文件
            if (file_exists($appFilePath)) {
                unlink($appFilePath);
            }
            $appFilePath = $targetPath;
        }
    }

    // 处理图片删除
    if (!empty($_POST['removed_images'])) {
        $removedImageIds = explode(',', $_POST['removed_images']);
        foreach ($removedImageIds as $imgId) {
            if (is_numeric($imgId)) {
                // 获取图片路径
                $stmt = $conn->prepare("SELECT image_path FROM app_images WHERE id = ?");
                $stmt->bind_param("i", $imgId);
                $stmt->execute();
                $imgResult = $stmt->get_result();
                if ($img = $imgResult->fetch_assoc()) {
                    // 删除文件
                    if (file_exists($img['image_path'])) {
                        unlink($img['image_path']);
                    }
                    // 删除数据库记录
                    $deleteStmt = $conn->prepare("DELETE FROM app_images WHERE id = ?");
                    $deleteStmt->bind_param("i", $imgId);
                    $deleteStmt->execute();
                    $deleteStmt->close();
                }
                $stmt->close();
            }
        }
    }

    // 更新应用标签
    // 删除现有标签关联
    $deleteTagStmt = $conn->prepare('DELETE FROM app_tags WHERE app_id = ?');
    $deleteTagStmt->bind_param('i', $appId);
    $deleteTagStmt->execute();
    $deleteTagStmt->close();

    // 添加新标签关联
    foreach ($selectedTags as $tagId) {
        if (is_numeric($tagId)) {
            $tagStmt = $conn->prepare('INSERT INTO app_tags (app_id, tag_id) VALUES (?, ?)');
            $tagStmt->bind_param('ii', $appId, $tagId);
            $tagStmt->execute();
            $tagStmt->close();
        }
    }

    // 处理新图片上传
    $imageUploadDir = '../uploads/images/';
    $allowedImageTypes = ['jpg', 'jpeg', 'png'];
    $maxImages = 5;
    $currentImageCount = count($existingImages) - count($removedImageIds ?? []);

    if (!empty($_FILES['images']['name'][0]) && empty($error)) {
        $newImages = $_FILES['images'];
        for ($i = 0; $i < count($newImages['name']); $i++) {
            if ($newImages['error'][$i] !== UPLOAD_ERR_OK) continue;
            
            $fileName = $newImages['name'][$i];
            $fileTmp = $newImages['tmp_name'][$i];
            $fileSize = $newImages['size'][$i];
            
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($fileExt, $allowedImageTypes)) {
                $error = "图片 {$fileName} 格式不支持，仅允许jpg、png";
                break;
            }
            if ($fileSize > 2 * 1024 * 1024) { // 2MB
                $error = "图片 {$fileName} 大小超过2MB";
                break;
            }
            if ($currentImageCount >= $maxImages) {
                $error = "最多只能上传5张图片";
                break;
            }
            
            $newFileName = uniqid() . '.' . $fileExt;
            $targetPath = $imageUploadDir . $newFileName;
            
            if (move_uploaded_file($fileTmp, $targetPath)) {
                // 插入数据库
                $stmt = $conn->prepare("INSERT INTO app_images (app_id, image_path) VALUES (?, ?)");
                $stmt->bind_param("is", $appId, $targetPath);
                $stmt->execute();
                $stmt->close();
                $currentImageCount++;
            } else {
                $error = "图片 {$fileName} 上传失败";
                break;
            }
        }
    }

    // 验证标签选择
    $selectedTags = $_POST['tags'] ?? [];
    if (empty($selectedTags)) {
        $error = '至少需要选择一个应用标签';
    }

    if (empty($appName) || empty($appDescription) || empty($version) || empty($changelog) || empty($ageRating) || empty($ageRatingDescription)) {
        $error = '应用名称和描述不能为空';
    } else {
        // 检查数据库连接是否为 MySQLi 对象
if (!($conn instanceof mysqli)) {
    log_error('数据库连接错误: 连接不是MySQLi实例', __FILE__, __LINE__);
    $error = '数据库连接错误，请检查配置';
} else {
    $platforms = $_POST['platforms'] ?? [];
    $platforms_json = json_encode($platforms);
    $stmt = $conn->prepare('UPDATE apps SET name = ?, description = ?, version = ?, changelog = ?, age_rating = ?, age_rating_description = ?, platforms = ?, file_path = ?, status = \'pending\' WHERE id = ? AND developer_id = ?');
    if (!$stmt) {
        log_error('更新应用信息查询准备失败: ' . $conn->error, __FILE__, __LINE__);
        $error = '更新应用信息时发生错误，请稍后再试';
    } else {
        $stmt->bind_param('ssssssssii', $appName, $appDescription, $version, $changelog, $ageRating, $ageRatingDescription, $platforms_json, $appFilePath, $appId, $developerId);
        if (!$stmt->execute()) {
            log_error('更新应用信息查询执行失败: ' . $stmt->error, __FILE__, __LINE__);
            $error = '更新应用信息时发生错误，请稍后再试';
        } else {
            $success = '应用信息更新成功，请等待管理员重新审核';
            header('Location: dashboard.php?success=' . urlencode($success));
            exit;
            $app['name'] = $appName;
            $app['description'] = $appDescription;
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
    <title>编辑应用</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 500px;
        }
        h2 {
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
            resize: vertical;
        }
        input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
        }
        input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #007BFF;
            color: #fff;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .success {
            color: green;
            margin-bottom: 10px;
        }
        .back-link {
            text-align: center;
            margin-top: 10px;
        }
        .back-link a {
            color: #007BFF;
            text-decoration: none;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>编辑应用</h2>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">应用名称</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($app['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">应用描述</label>
                <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($app['description']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="version">版本号</label>
                <input type="text" id="version" name="version" value="<?php echo htmlspecialchars($app['version']); ?>" required>
            </div>
            <div class="form-group">
                <label for="changelog">更新日志</label>
                <textarea id="changelog" name="changelog" rows="3" required><?php echo htmlspecialchars($app['changelog']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="age_rating">年龄分级</label>
                <select id="age_rating" name="age_rating" required>
                    <option value="3+" <?php echo $app['age_rating'] === '3+' ? 'selected' : ''; ?>>3+</option>
                    <option value="7+" <?php echo $app['age_rating'] === '7+' ? 'selected' : ''; ?>>7+</option>
                    <option value="12+" <?php echo $app['age_rating'] === '12+' ? 'selected' : ''; ?>>12+</option>
                    <option value="17+" <?php echo $app['age_rating'] === '17+' ? 'selected' : ''; ?>>17+</option>
                </select>
            </div>
            <div class="form-group">
                <label for="age_rating_description">年龄分级说明</label>
                <input type="text" id="age_rating_description" name="age_rating_description" value="<?php echo htmlspecialchars($app['age_rating_description']); ?>" required>
            </div>
            <div class="form-group">
                <label>适用平台</label>
                <?php $platforms = json_decode($app['platforms'], true) ?? []; ?>
                <div>
                    <input type="checkbox" id="platform_android" name="platforms[]" value="Android" <?php echo in_array('Android', $platforms) ? 'checked' : ''; ?>>
                    <label for="platform_android">Android</label>
                </div>
                <div>
                    <input type="checkbox" id="platform_ios" name="platforms[]" value="iOS" <?php echo in_array('iOS', $platforms) ? 'checked' : ''; ?>>
                    <label for="platform_ios">iOS</label>
                </div>
            </div>
            <div class="form-group">
                <label>应用标签 (至少选择1个)</label>
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($tags as $tag): ?>
                    <div>
                        <input type="checkbox" id="tag_<?php echo $tag['id']; ?>" name="tags[]" value="<?php echo $tag['id']; ?>" <?php echo in_array($tag['id'], $appTags) ? 'checked' : ''; ?>>
                        <label for="tag_<?php echo $tag['id']; ?>"><?php echo htmlspecialchars($tag['name']); ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label for="app_file">更新应用文件</label>
                <input type="file" id="app_file" name="app_file">
                <small>当前文件: <?php echo basename($app['file_path']); ?></small>
            </div>
            <div class="form-group">
                <label>应用图片 (最多5张)</label>
                <?php
                // 获取现有图片
                $existingImages = [];
                $stmt = $conn->prepare("SELECT id, image_path FROM app_images WHERE app_id = ?");
                $stmt->bind_param("i", $appId);
                $stmt->execute();
                $imgResult = $stmt->get_result();
                while ($img = $imgResult->fetch_assoc()) {
                    $existingImages[] = $img;
                }
                $stmt->close();
                ?>
                <!-- 现有图片 -->
                <?php if (!empty($existingImages)): ?>
                <div class="mb-3">
                    <label>现有图片:</label>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($existingImages as $img): ?>
                        <div class="position-relative">
                            <img src="<?php echo htmlspecialchars($img['image_path']); ?>" alt="应用图片" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px;">
                            <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0" onclick="removeImage(<?php echo $img['id']; ?>)">×</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <!-- 新图片上传 -->
                <input type="file" name="images[]" multiple accept="image/*" class="form-control">
                <small>支持jpg、png格式，最多上传5张图片</small>
            </div>
            <input type="hidden" name="removed_images" id="removed_images" value="">
            <input type="submit" value="保存更改">
        </form>
        <div class="back-link">
            <a href="dashboard.php">返回仪表盘</a>
        </div>
    </div>
<script>
function removeImage(imageId) {
    const removedInput = document.getElementById('removed_images');
    const currentValues = removedInput.value ? removedInput.value.split(',') : [];
    if (!currentValues.includes(imageId.toString())) {
        currentValues.push(imageId);
        removedInput.value = currentValues.join(',');
    }
    // 从DOM中移除图片元素
    event.target.closest('.position-relative').remove();
}
</script>
</body>
</html>