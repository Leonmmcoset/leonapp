<?php
session_start();
require_once '../config.php';

if (!isset($conn) || !$conn instanceof mysqli) {
    die('数据库连接失败，请检查配置文件。');
}?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>隐私政策</title>
    <style>        .page-transition {            animation: fadeIn 0.5s ease-in-out;        }                @keyframes fadeIn {            from {                opacity: 0;                transform: translateY(20px);            }            to {                opacity: 1;                transform: translateY(0);            }        }    </style>    <!-- Bootstrap CSS -->    <link href="../css/bootstrap.min.css" rel="stylesheet">    <!-- 自定义CSS -->
    <link rel="stylesheet" href="../styles.css">
    <!-- Fluent Design 模糊效果 -->
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="../js/bootstrap.bundle.js"></script>    <style>        .blur-bg {            backdrop-filter: blur(10px);            background-color: rgba(255, 255, 255, 0.5);        }    </style></head><body class="page-transition">    <!-- 导航栏 -->    <nav class="navbar navbar-expand-lg navbar-light blur-bg">        <div class="container">            <a class="navbar-brand" href="#"><?php echo APP_STORE_NAME; ?></a>            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">                <span class="navbar-toggler-icon"></span>            </button>            <div class="collapse navbar-collapse" id="navbarNav">                <ul class="navbar-nav">                    <li class="nav-item">                        <a class="nav-link" href="../index.php">首页</a>                    </li>                    <?php if (isset($_SESSION['admin'])): ?>                        <li class="nav-item">                            <a class="nav-link" href="../admin/">管理</a>                        </li>                    <?php endif; ?>                    <li class="nav-item">                        <a class="nav-link" href="../tags.php">标签</a>                    </li>                    <?php if (isset($_SESSION['developer_id'])): ?>                    <li class="nav-item">                        <a class="nav-link" href="../developer/dashboard.php">进入面板</a>                    </li>                    <?php else: ?>                    <li class="nav-item">                        <a class="nav-link" href="../developer/register.php">开发者注册</a>                    </li>                    <li class="nav-item">                        <a class="nav-link" href="../developer/login.php">开发者登录</a>                    </li>                    <?php endif; ?>                </ul>            </div>        </div>    </nav>    <div class="container mt-4">
        <h1>隐私政策</h1>
        
        <h2>引言</h2>
        <p>本隐私政策旨在说明我们如何收集、使用、披露和保护您的个人信息。在使用我们的服务前，请仔细阅读本隐私政策。</p>

        <h2>信息收集</h2>
        <p>我们会收集您在使用服务时主动提供的信息，例如注册信息等。同时，我们也会自动收集一些信息，如设备信息、日志信息等。</p>

        <h2>信息使用</h2>
        <p>我们会将收集到的信息用于提供、维护和改进服务，个性化用户体验，处理交易，以及遵守法律要求等。</p>

        <h2>信息披露</h2>
        <p>除非获得您的同意，或者根据法律要求，否则我们不会向第三方披露您的个人信息。在某些情况下，我们可能会与合作伙伴共享信息，但会确保他们遵守严格的数据保护要求。</p>

        <h2>信息保护</h2>
        <p>我们采用合理的安全措施来保护您的个人信息，防止信息被未经授权的访问、使用或披露。但请您理解，没有任何一种互联网传输方式或电子存储方式是 100% 安全的。</p>

        <h2>您的权利</h2>
        <p>您有权访问、更正、删除您的个人信息，以及限制我们对您信息的处理。如果您有任何相关请求，请联系我们。</p>

        <h2>政策变更</h2>
        <p>我们可能会定期更新本隐私政策。更新后，我们会在网站上发布新的隐私政策，并说明变更的生效日期。请您定期查看本政策，以了解我们的信息处理方式是否有变化。</p>

        <h2>联系我们</h2>
        <p>如果您对本隐私政策有任何疑问或建议，请通过 [您的联系方式] 与我们联系。</p>
    </div>
</body>
</html>