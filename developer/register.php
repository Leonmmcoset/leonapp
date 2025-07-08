<?php
// Define SMTP constants if not already defined
if (!defined('SMTP_USERNAME')) define('SMTP_USERNAME', '');
if (!defined('SMTP_ENCRYPTION')) define('SMTP_ENCRYPTION', 'tls');
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', 'noreply@example.com');


// 引入PHPMailer命名空间
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// 引入配置文件
require_once '../config.php';

// 引入Composer自动加载器
require_once '../vendor/autoload.php';

// 顶栏样式
echo '<style>
.navbar.scrolled {
    background-color: rgba(255, 255, 255, 0.95) !important;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}
</style>';

// 导航栏
echo '<nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
    <div class="container">
        <a class="navbar-brand" href="../index.php">'. APP_STORE_NAME . '</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="../index.php">首页</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="login.php">开发者登录</a>
                </li>
            </ul>
        </div>
    </div>
</nav>';

// 为内容添加顶部内边距
echo '<div style="padding-top: 70px;">';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($username) || empty($email) || empty($password)) {
        $error = '用户名、邮箱和密码不能为空';
    } elseif (empty($_POST['agree'])) {
        $error = '必须同意 APP 审核标准才能注册';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '请输入有效的邮箱地址';
    } else {
        // 检查数据库连接是否为 PDO 对象
        if (!($conn instanceof mysqli)) {
              log_error('数据库连接错误: 连接不是MySQLi实例', __FILE__, __LINE__);
              $error = '数据库连接错误，请检查配置';
          } else {
            try {
                $stmt = $conn->prepare('SELECT id FROM developers WHERE username = ? OR email = ?');
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $error = '用户名或邮箱已被注册';
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    // 生成验证令牌
                    $verificationToken = bin2hex(random_bytes(32));
                    $insertStmt = $conn->prepare('INSERT INTO developers (username, email, password, verification_token) VALUES (?, ?, ?, ?)');
                    if (!$insertStmt) {
                        log_error('插入准备失败: ' . $conn->error, __FILE__, __LINE__);
                        $error = '系统错误，请稍后再试';
                } else {
                        // 生成验证链接
                        $verificationLink = 'https://' . $_SERVER['HTTP_HOST'] . '/developer/verify_email.php?token=' . urlencode($verificationToken);

                        // 加载邮件模板
                        $templatePath = __DIR__ . '/../mail/verification_template.php';
                        if (file_exists($templatePath)) {
                            $templateContent = file_get_contents($templatePath);
                            $templateContent = str_replace('{username}', htmlspecialchars($username), $templateContent);
                            $templateContent = str_replace('{verification_link}', $verificationLink, $templateContent);

                            // 配置SMTP邮件发送
                            require_once '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
                            require_once '../vendor/phpmailer/phpmailer/src/SMTP.php';


                            /** @var \PHPMailer\PHPMailer\PHPMailer $mail */
                            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                            try {
                                $mail->isSMTP();
                                $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.example.com';
                                $mail->SMTPAuth = true;
                                $mail->Username = defined('SMTP_USERNAME') ? SMTP_USERNAME : ''; // Ensure SMTP_USERNAME is defined in config.php
                                $mail->Password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
                                $mail->SMTPSecure = defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'tls'; // Ensure SMTP_ENCRYPTION is defined in config.php
                                $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;

                                $mail->setFrom(defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@example.com', defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'App Store'); // Ensure SMTP_FROM_EMAIL is defined in config.php
                                $mail->addAddress($email, $username);

                                $mail->isHTML(true);
                                $mail->Subject = '邮箱验证 - ' . (defined('APP_STORE_NAME') ? APP_STORE_NAME : 'App Store');
                                $mail->Body = $templateContent;

                                $mail->send();
                            } catch (\PHPMailer\PHPMailer\Exception $e) {
                                log_error('邮件发送失败: ' . $mail->ErrorInfo, __FILE__, __LINE__);
                            }
                        } else {
                            log_error('验证邮件模板不存在: ' . $templatePath, __FILE__, __LINE__);
                        }

                        header('Location: login.php?register_success=1&verify_email_sent=1');
                        exit;
                    }
                }
            } catch (PDOException $e) {
                $error = '注册时发生错误，请稍后再试';
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
    <title>开发者注册</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f4;
            padding: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container mt-5 col-md-4">
        <h2>开发者注册</h2>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username" required>
                <label for="username">用户名</label>
            </div>
            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="email" name="email" required>
                <label for="email">邮箱</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" required>
                <label for="password">密码</label>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="agree" name="agree" required>
                <label class="form-check-label" for="agree">我已阅读并同意 <a href="/docs/app_review_standards.php" target="_blank">APP 审核标准</a></label>
            </div>
            <button type="submit" class="btn btn-primary w-100">注册</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>