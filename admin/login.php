<?php
require_once '../config.php';

// 检查管理员登录状态
session_start();

if (!isset($_SESSION['admin'])) {
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
            $_SESSION['admin'] = [
                'id' => 1, // 配置文件中未定义管理员ID，使用默认值1
                'username' => $username
            ];
            header('Location: index.php');
            exit();
        } else {
            $error = '用户名或密码错误';
        }
    }
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - <?php echo APP_STORE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <!-- 自定义CSS -->
    <link rel="stylesheet" href="../styles.css">
    <!-- 顶栏样式 -->
    <style>
        .navbar.scrolled {
            background-color: rgba(255, 255, 255, 0.95) !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .blur-bg {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.5);
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../index.php"><?php echo APP_STORE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">首页</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">管理后台</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- 为内容添加顶部内边距 -->
    <div style="padding-top: 70px;">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card blur-bg">
                        <div class="card-header">管理员登录</div>
                        <div class="card-body">
                            <?php if (isset($error)): ?>
                                <script>
                                    Swal.fire({
                                        icon: "error",
                                        title: "错误",
                                        text: "<?php echo addslashes($error); ?>",
                                    });
                                </script>
                            <?php endif; ?>
                            <form method="post">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="username" name="username" required>
                                    <label for="username">用户名</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <label for="password">密码</label>
                                </div>
                                <button type="submit" class="btn btn-primary">登录</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="/js/bootstrap.bundle.js"></script>
</body>
</html>
<?php
    exit;
}