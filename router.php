<?php
    // 路由脚本，用于PHP内置服务器
    // 将所有/api开头的请求转发到api.php

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
            <a class="navbar-brand" href="index.php">'. APP_STORE_NAME . '</a>
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
    </nav>';

    // 为内容添加顶部内边距
    echo '<div style="padding-top: 70px;">';
    if (preg_match('/^\/api/', $_SERVER['REQUEST_URI'])) {
        include 'api.php';
        exit;
    }

    // 其他请求按正常方式处理
    return false;