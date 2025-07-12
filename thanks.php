<?php
/**
 * 鸣谢页面
 */
require_once 'config.php';
?> 
<!DOCTYPE html>
<style>
        .page-transition {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>鸣谢页面</title>
    <!-- Bootstrap CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="/favicon.ico">
    <!-- 自定义CSS -->
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Fluent Design 模糊效果 -->
    <style>
        :root {
            --primary-color: #4a6fa5;
            --secondary-color: #e0e0e0;
            --text-color: #333;
            --bg-color: #f9f9f9;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .thank-you-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            animation: animate__fadeIn 1s;
        }

        .thank-you-title {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2.5rem;
            font-weight: 600;
        }

        .thank-you-list {
            list-style: none;
            padding: 0;
        }

        .thank-you-item {
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            background-color: #f5f7fa;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .thank-you-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .thank-you-link {
            color: var(--primary-color);
            font-weight: 500;
            text-decoration: none;
            transition: color 0.3s;
        }

        .thank-you-link:hover {
            color: #3a5a8a;
            text-decoration: underline;
        }

        .footer {
            text-align: center;
            margin-top: 2rem;
            padding: 1rem;
            color: #666;
        }
    </style>
</head>
<body class="page-transition">
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-light blur-bg">
        <div class="container">
            <a href="index.php"><img src="/favicon.ico" alt="Logo" style="height: 30px; margin-right: 10px; border-radius: var(--border-radius);"></a>
            <a class="navbar-brand" href="#"><?php echo APP_STORE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">首页</a>
                    </li>
                    <?php if (isset($_SESSION['admin'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/">管理</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="tags.php">标签</a>
                    </li>
                    <?php if (isset($_SESSION['developer_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="developer/dashboard.php">进入面板</a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="developer/register.php">开发者注册</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="developer/login.php">开发者登录</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="thanks.php">鸣谢</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="thank-you-container">
        <h1 class="thank-you-title animate__animated animate__fadeInDown">鸣谢</h1>
        <ul class="thank-you-list">
            <li class="thank-you-item">
                <a href="/developer_apps.php?id=2" class="thank-you-link">JGZ_YES</a>：制作LeonAPP客户端应用，帮助转载许多APP。
            </li>
        </ul>
        <div class="footer">
            &copy; <?php echo date('Y'); ?> <?php echo APP_STORE_NAME; ?>. 保留所有权利。
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/js/bootstrap.bundle.js"></script>
</body>
</html>