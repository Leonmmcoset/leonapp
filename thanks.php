<?php
/**
 * 鸣谢页面
 */
require_once 'config.php';
?>
<style>
.navbar.scrolled {
    background-color: rgba(255, 255, 255, 0.95) !important;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}
</style>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>鸣谢页面</title>
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="/css/bootstrap.min.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php"><?php echo APP_STORE_NAME; ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">首页</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
    <h1>鸣谢</h1>
    <ul>
        <li><a href="/developer_apps.php?id=2">JGZ_YES</a>：制作LeonAPP客户端应用，帮助转载许多APP。</li>
    </ul>
    <script src="/js/bootstrap.bundle.js"></script>
</body>
</html>