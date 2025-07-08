<?php
// 引入配置文件
// 检查配置文件是否存在并加载
$configFile = 'd:\app2\config.php';
if (!file_exists($configFile)) {
    die('配置文件缺失: ' . $configFile . '，无法继续执行');
}
require_once $configFile;

// 引入日志工具
require_once 'd:\app2\includes\logger.php';

// 配置文件加载后日志记录和常量检查
log_error('配置文件已成功加载: ' . $configFile);
// 验证关键常量是否定义
log_error('配置加载后常量检查 - SMTP_HOST: ' . (defined('SMTP_HOST') ? SMTP_HOST : '未定义'));
log_error('配置加载后常量检查 - SMTP_USERNAME: ' . (defined('SMTP_USERNAME') ? SMTP_USERNAME : '未定义'));
log_error('配置加载后常量检查 - SMTP_PASSWORD: ' . (defined('SMTP_PASSWORD') ? '已设置' : '未定义'));
log_error('配置加载后常量检查 - SMTP_PORT: ' . (defined('SMTP_PORT') ? SMTP_PORT : '未定义'));
log_error('配置文件加载后 - SMTP_USERNAME: ' . (defined('SMTP_USERNAME') ? SMTP_USERNAME : '未定义') . ', SMTP_PORT: ' . (defined('SMTP_PORT') ? SMTP_PORT : '未定义'));




// 引入PHPMailer命名空间
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

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
                        $insertStmt->bind_param('ssss', $username, $email, $hashedPassword, $verificationToken);
                        if (!$insertStmt->execute()) {
                            log_error('插入执行失败: ' . $insertStmt->error, __FILE__, __LINE__);
                            $error = '系统错误，请稍后再试';
                        } else {
                        // 生成验证链接
                        $verificationLink = 'http://' . $_SERVER['HTTP_HOST'] . '/developer/verify_email.php?token=' . urlencode($verificationToken);

                        // 加载邮件模板
                        $templatePath = __DIR__ . '/../mail/verification_template.php';
                        if (file_exists($templatePath)) {
                            $templateContent = file_get_contents($templatePath);
                            $templateContent = str_replace('{username}', htmlspecialchars($username), $templateContent);
                            $templateContent = str_replace('{verification_link}', $verificationLink, $templateContent);

                            // 调试日志测试
                            $testLogDir = 'd:\\app2\\logs';
                            $testLogFile = $testLogDir . '\\test.log';
                            if (!is_dir($testLogDir)) {
                                mkdir($testLogDir, 0755, true);
                            }
                            file_put_contents($testLogFile, date('[Y-m-d H:i:s] ') . '邮件发送代码开始执行' . PHP_EOL, FILE_APPEND);

                            // 添加SMTP配置调试日志
                            log_error('SMTP配置参数 - HOST: ' . (defined('SMTP_HOST') ? SMTP_HOST : '未定义') . ', PORT: ' . (defined('SMTP_PORT') ? SMTP_PORT : '未定义') . ', USERNAME: ' . (defined('SMTP_USERNAME') ? SMTP_USERNAME : '未定义') . ', ENCRYPTION: ' . (defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : '未定义'), __FILE__, __LINE__);
                            log_error('开始执行邮件发送流程', __FILE__, __LINE__);

                            // 配置SMTP邮件发送
                            require_once '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
                            require_once '../vendor/phpmailer/phpmailer/src/SMTP.php';


                            /** @var \PHPMailer\PHPMailer\PHPMailer $mail */
                            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                            try {
                                $mail->isSMTP();
                                $mail->SMTPDebug = 4;
// 输出当前SMTP配置参数用于调试
log_error('SMTP配置参数: HOST=' . SMTP_HOST . ', PORT=' . SMTP_PORT . ', USERNAME=' . SMTP_USERNAME . ', ENCRYPTION=' . SMTP_ENCRYPTION);
// 检查openssl扩展是否启用
log_error('OpenSSL扩展状态: ' . (extension_loaded('openssl') ? '已启用' : '未启用')); // 启用详细调试
                                $mail->Debugoutput = function($str, $level) {
                                     $logDir = 'd:\\app2\\logs';
                                     if (!is_dir($logDir)) {
                                         mkdir($logDir, 0755, true);
                                     }
                                     file_put_contents($logDir . '\\smtp_debug.log', date('[Y-m-d H:i:s] ') . $str . PHP_EOL, FILE_APPEND);
                                 };
                                $mail->Host = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.example.com';
                                $mail->SMTPAuth = true;
                                $mail->Username = defined('SMTP_USERNAME') ? SMTP_USERNAME : ''; // Ensure SMTP_USERNAME is defined in config.php
                                $mail->Password = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '';
                                $mail->SMTPSecure = defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'tls'; // Ensure SMTP_ENCRYPTION is defined in config.php
$mail->AuthType = 'PLAIN'; // 尝试使用PLAIN认证方式
                                $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
                                $mail->CharSet = 'UTF-8';

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