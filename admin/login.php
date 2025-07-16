<?php
require_once '../config.php';

// 检查管理员登录状态
session_start();

if (!isset($_SESSION['admin'])) {
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $adminFound = false;
        foreach ($admin_accounts as $account) {
            if ($username === $account['username'] && $password === $account['password']) {
                $_SESSION['admin'] = [
                    'id' => $account['id'],
                    'username' => $account['username'],
                    'permission' => $account['permission']
                ];
                $adminFound = true;
                
                // 处理自动登录
                if (isset($_POST['remember_me']) && $_POST['remember_me'] === 'on') {
                    $cookie_lifetime = 30 * 24 * 60 * 60; // 30天
                    $cookie_params = session_get_cookie_params();
                    setcookie(
                        session_name(),
                        session_id(),
                        time() + $cookie_lifetime,
                        $cookie_params['path'],
                        $cookie_params['domain'],
                        $cookie_params['secure'],
                        $cookie_params['httponly']
                    );
                    ini_set('session.gc_maxlifetime', $cookie_lifetime);
                }
                
                // 根据权限设置重定向页面
                $redirectPage = 'index.php';
                if ($_SESSION['admin']['permission'] == 'say') {
                    $redirectPage = 'announcements.php';
                } elseif ($_SESSION['admin']['permission'] == 'review') {
                    $redirectPage = 'review_apps.php';
                }
                header("Location: $redirectPage");
                exit();
            }
        }
        
        if (!$adminFound) {
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
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        .page-transition {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
    </style>
    <script src="/js/sweetalert.js"></script>
</head>
<body class="page-transition">
    <!-- 导航栏 -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.body.classList.add('page-transition');
    });
    </script>
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
        <div class="container">
            <a href="../index.php"><img src="/favicon.jpeg" alt="Logo" style="height: 30px; margin-right: 10px; border-radius: var(--border-radius);"></a>
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
                        <div class="card-header"><i class="fas fa-sign-in-alt me-2"></i>管理员登录</div>
                        <div class="card-body">
                            <!-- <?php if (isset($error)): ?>
                                <script>
                                    Swal.fire({
                                        icon: "error",
                                        title: "错误",
                                        text: "<?php echo addslashes($error); ?>",
                                    });
                                </script>
                            <?php endif; ?> -->
                            <form method="post">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="username" name="username" required>
                                    <label for="username"><i class="fas fa-user me-2"></i>用户名</label>
                                </div>
                                <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="password" name="password" required>
                            <label for="password"><i class="fas fa-lock me-2"></i>密码</label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="remember_me" id="remember_me">
                            <label class="form-check-label" for="remember_me">
                                <i class="fas fa-clock me-2"></i>自动登录
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-sign-in-alt me-2"></i>登录</button>
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