<?php
    session_start();
    require_once '../config.php';

    // 删除文件
    function delete_file($file_path) {
        if (file_exists($file_path)) {
            return unlink($file_path);
        }
        return false;
    }

    // 处理删除请求
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $upload_dirs = [
            '../uploads/apps',
            '../uploads/images'
        ];

        // 全量删除
        if (isset($_POST['delete_all'])) {
            foreach ($upload_dirs as $dir) {
                if (is_dir($dir)) {
                    $files = scandir($dir);
                    foreach ($files as $file) {
                        if ($file !== '.' && $file !== '..') {
                            $file_path = $dir . '/' . $file;
                            if (is_file($file_path)) {
                                delete_file($file_path);
                            }
                        }
                    }
                }
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        // 单个删除
        if (isset($_POST['delete_files'])) {
            foreach ($_POST['delete_files'] as $file_info) {
                list($type, $filename) = explode('|', $file_info);
                $dir = $type === '图片' ? '../uploads/images' : '../uploads/apps';
                $file_path = $dir . '/' . $filename;
                delete_file($file_path);
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }

    // 验证管理员权限
    if (!isset($_SESSION['admin'])) {
        header('Location: login.php');
        exit;
    }

    // 获取上传文件和图片信息
    function get_uploaded_files_info() {
        $uploaded_files = [];

        // 上传目录配置
        $upload_dirs = [
            '../uploads/apps',
            '../uploads/images'
        ];

        foreach ($upload_dirs as $dir) {
            if (is_dir($dir)) {
                $files = scandir($dir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $file_path = $dir . '/' . $file;
                        if (is_file($file_path)) {
                            $file_size = filesize($file_path);
                            $uploaded_files[] = [
                                'name' => $file,
                                'size' => $file_size,
                                'type' => strpos($dir, 'images') !== false ? '图片' : '文件'
                            ];
                        }
                    }
                }
            }
        }

        return $uploaded_files;
    }

    $uploaded_files = get_uploaded_files_info();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统信息 - 上传文件列表</title>
    <!-- Bootstrap CSS -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <!-- 自定义CSS -->
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">管理员面板</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">首页</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="system_info.php">系统信息</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <form method="post">
            <h2>上传文件信息</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>文件名</th>
                    <th>大小</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($uploaded_files as $file): ?>
                    <?php if ($file['type'] === '文件'): ?>
                    <tr>
                        <td><input type="checkbox" name="delete_files[]" value="<?php echo $file['type'] . '|' . $file['name']; ?>"></td>
                        <td><?php echo htmlspecialchars($file['name']); ?></td>
                        <td><?php echo round($file['size'] / 1024, 2); ?> KB</td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>上传图片信息</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAllImages"></th>
                    <th>文件名</th>
                    <th>大小</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($uploaded_files as $file): ?>
                    <?php if ($file['type'] === '图片'): ?>
                    <tr>
                        <td><input type="checkbox" name="delete_files[]" value="<?php echo $file['type'] . '|' . $file['name']; ?>"></td>
                        <td><?php echo htmlspecialchars($file['name']); ?></td>
                        <td><?php echo round($file['size'] / 1024, 2); ?> KB</td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
            <button type="submit" name="delete_all" class="btn btn-danger" onclick="return confirm('确定要删除所有文件吗？')">全量删除</button>
            <button type="submit" class="btn btn-danger ms-2" onclick="return confirm('确定要删除选中的文件吗？')">删除选中</button>
        </form>
    </div>
        </form>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="/js/bootstrap.bundle.js"></script>
<script>
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="delete_files[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

    document.getElementById('selectAllImages').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="delete_files[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
</script>
</body>
</html>