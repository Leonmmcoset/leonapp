<?php
/**
 * 邮箱验证模板
 * 变量说明:
 * - {username}: 用户名
 * - {verification_link}: 验证链接
 */
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>邮箱验证 - <?= APP_STORE_NAME ?></title>
    <style>
        body { font-family: 'Microsoft YaHei', sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .container { background-color: #f9f9f9; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .logo { font-size: 24px; font-weight: bold; color: #2c3e50; margin-bottom: 20px; }
        .greeting { font-size: 18px; margin-bottom: 15px; }
        .content { margin-bottom: 25px; }
        .verification-btn { display: inline-block; padding: 12px 24px; background-color: #3498db; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .verification-btn:hover { background-color: #2980b9; }
        .footer { margin-top: 30px; color: #7f8c8d; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo"><?= APP_STORE_NAME ?></div>
        <div class="greeting">您好，{username}！</div>
        <div class="content">
            <p>感谢您注册成为开发者！请点击下方链接验证您的邮箱：</p>
            <p style="margin: 30px 0;"><a href="{verification_link}" class="verification-btn">验证邮箱</a></p>
            <p>如果您没有注册过我们的服务，请忽略此邮件。</p>
        </div>
        <div class="footer">
            <p>此邮件由系统自动发送，请勿回复。</p>
        </div>
    </div>
</body>
</html>