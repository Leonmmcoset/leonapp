<?php require_once '../config.php'; ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LeonAPP年龄分级</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="/styles.css">
    <script src="/js/marked.js"></script>
    <style>
        .markdown-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .markdown-container h1 {
            margin-bottom: 2rem;
            color: #333;
        }
    </style>
    <style>
        body {
            padding-top: 56px;
        }
        .blur-bg {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-light blur-bg fixed-top">
        <div class="container">
            <a href="/index.php"><img src="/favicon.jpeg" alt="Logo" style="height: 30px; margin-right: 10px; border-radius: var(--border-radius);"></a>
            <a class="navbar-brand" href="/index.php"><?php echo APP_STORE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/index.php">首页</a>
                    </li>
                    <?php if (isset($_SESSION['admin'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/">管理</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container markdown-container">
        <div id="markdown-content"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 读取Markdown文件内容并渲染
            const markdownContent = <?php echo json_encode(file_get_contents('markdown/la-1.md')); ?>;
            const htmlContent = marked.parse(markdownContent);
            document.getElementById('markdown-content').innerHTML = htmlContent;
        });
    </script>
</body>
</html>