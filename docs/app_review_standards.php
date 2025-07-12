<?php
/**
 * APP 审核标准文档 - 完整版
 */
?>
<?php require_once '../config.php'; ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APP 审核标准 - 完整版</title>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="/favicon.ico">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/css/all.min.css">
    <style>
        body {
            padding-top: 56px;
        }
        .blur-bg {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.5);
        }
    </style>
    <style>
        .audit-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border-radius: 0.5rem;
            background-color: #f8f9fa;
        }
        .audit-subsection {
            margin-bottom: 1.5rem;
            padding-left: 1rem;
            border-left: 3px solid #0d6efd;
        }
        .audit-point {
            margin-bottom: 0.75rem;
        }
        .audit-note {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 0.25rem;
            margin-left: 1.5rem;
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-light blur-bg fixed-top">
        <div class="container">
            <a href="/index.php"><img src="/favicon.ico" alt="Logo" style="height: 30px; margin-right: 10px; border-radius: var(--border-radius);"></a>
            <a class="navbar-brand" href="/index.php"><?php echo APP_STORE_NAME; ?></a>
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
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-5 mb-5">
        <div class="text-center mb-5">
            <h1 class="display-4">APP 审核标准文档</h1>
            <p class="lead">适用于所有平台的应用审核规范</p>
            <p class="text-muted">版本：2025.07 修订</p>
        </div>

        <div class="audit-section">
            <h2 class="fw-bold mb-4">一、内容审核标准</h2>
            
            <div class="audit-subsection">
                <h3 class="fw-semibold">1.1 法律法规合规性</h3>
                <ul class="list-unstyled">
                    <li class="audit-point">
                        <span class="fw-medium">1.1.1</span> 不得包含任何违反中国法律法规的内容，如赌博、色情、暴力、恐怖主义等相关信息
                        <p class="audit-note">包括但不限于文字、图片、音频、视频等形式的违法内容</p>
                    </li>
                    <li class="audit-point">
                        <span class="fw-medium">1.1.2</span> 不得含有侵犯他人隐私、名誉权、肖像权等个人权利的内容
                        <p class="audit-note">需确保用户数据收集、使用和共享符合《个人信息保护法》</p>
                    </li>
                    <li class="audit-point">
                        <span class="fw-medium">1.1.3</span> 不得传播谣言、虚假信息或误导性内容
                        <p class="audit-note">特别是涉及金融、医疗、时政等敏感领域的信息</p>
                    </li>
                </ul>
            </div>
            
            <div class="audit-subsection">
                <h3 class="fw-semibold">1.2 知识产权保护</h3>
                <ul class="list-unstyled">
                    <li class="audit-point">
                        <span class="fw-medium">1.2.1</span> 不得包含侵犯他人商标、专利、著作权等知识产权的内容
                        <p class="audit-note">应用内所有内容需确保有合法授权或属于原创</p>
                    </li>
                    <li class="audit-point">
                        <span class="fw-medium">1.2.2</span> 不得抄袭或模仿已有应用的界面设计、功能逻辑或商业模式
                        <p class="audit-note">需具备显著的原创性和独特价值</p>
                    </li>
                </ul>
            </div>
            
            <div class="audit-subsection">
                <h3 class="fw-semibold">1.3 广告与推广内容</h3>
                <ul class="list-unstyled">
                    <li class="audit-point">
                        <span class="fw-medium">1.3.1</span> 所有广告内容必须真实、合法，不得含有虚假或引人误解的宣传
                        <p class="audit-note">广告需明确标识，与应用内容有清晰区分</p>
                    </li>
                    <li class="audit-point">
                        <span class="fw-medium">1.3.2</span> 不得诱导用户点击广告或进行不必要的操作
                        <p class="audit-note">禁止使用欺骗性标题、虚假下载按钮等手段</p>
                    </li>
                </ul>
            </div>
        </div>

        <div class="audit-section">
            <h2 class="fw-bold mb-4">二、功能审核标准</h2>
            
            <div class="audit-subsection">
                <h3 class="fw-semibold">2.1 功能完整性</h3>
                <ul class="list-unstyled">
                    <li class="audit-point">
                        <span class="fw-medium">2.1.1</span> 应用必须实现所有宣称的核心功能，无缺失或未完成的功能模块
                        <p class="audit-note">需与应用商店描述、宣传材料一致</p>
                    </li>
                    <li class="audit-point">
                        <span class="fw-medium">2.1.2</span> 所有功能必须可正常使用，无崩溃、闪退或无法访问的情况
                        <p class="audit-note">包括但不限于注册登录、数据提交、支付流程等</p>
                    </li>
                </ul>
            </div>
            
            <div class="audit-subsection">
                <h3 class="fw-semibold">2.2 用户界面与交互</h3>
                <ul class="list-unstyled">
                    <li class="audit-point">
                        <span class="fw-medium">2.2.1</span> 界面设计需符合目标平台的设计规范（如iOS Human Interface Guidelines或Android Material Design）
                        <p class="audit-note">包括布局、色彩、图标、字体等视觉元素的一致性</p>
                    </li>
                    <li class="audit-point">
                        <span class="fw-medium">2.2.2</span> 交互逻辑清晰，操作流程合理，无歧义或容易引起用户误解的设计
                        <p class="audit-note">例如按钮响应区域足够大，导航逻辑明确</p>
                    </li>
                    <li class="audit-point">
                        <span class="fw-medium">2.2.3</span> 支持多种屏幕尺寸和分辨率，在不同设备上显示正常
                        <p class="audit-note">需进行全面的兼容性测试</p>
                    </li>
                </ul>
            </div>
            
            <div class="audit-subsection">
                <h3 class="fw-semibold">2.3 数据安全与隐私</h3>
                <ul class="list-unstyled">
                    <li class="audit-point">
                        <span class="fw-medium">2.3.1</span> 应用必须有明确的隐私政策，说明数据收集、使用和共享方式
                        <p class="audit-note">隐私政策需符合相关法律法规要求</p>
                    </li>
                    <li class="audit-point">
                        <span class="fw-medium">2.3.2</span> 敏感数据（如密码、支付信息等）必须进行加密处理
                        <p class="audit-note">推荐使用HTTPS协议进行数据传输</p>
                    </li>
                    <li class="audit-point">
                        <span class="fw-medium">2.3.3</span> 不得在用户未明确授权的情况下收集、使用或共享个人数据
                        <p class="audit-note">特别是位置、联系人、摄像头等敏感权限</p>
                    </li>
                </ul>
            </div>
        </div>

        <div class="audit-section">
            <h2 class="fw-bold mb-4">三、性能审核标准</h2>
            
            <div class="audit-subsection">
                <h3 class="fw-semibold">3.1 响应速度与稳定性</h3>
                <ul class="list-unstyled">
                    <li class="audit-point">
                        <span class="fw-medium">3.1.1</span> 应用启动时间不得超过3秒（冷启动）和1秒（热启动）
                        <p class="audit-note">对于复杂应用，启动时间可适当放宽，但需提供合理说明</p>
                    </li>
                    <li class="audit-point">
                        <span class="fw-medium">3.1.2</span> 界面切换和操作响应时间不得超过500毫秒
                        <p class="audit-note">需避免长时间无响应或卡顿现象</p>
                    </li>
                    <li class="audit-point">
                        <span class="fw-medium">3.1.3</span> 在正常使用场景下，应用不得出现崩溃、闪退或无响应的情况
                        <p class="audit-note">需进行至少5000次操作的稳定性测试</p>
                    </li>
                </ul>
            </div>
            
            <div class="audit-subsection">
                <h3 class="fw-semibold">3.2 资源占用</h3>
                <ul class="list-unstyled">
                    <li class="audit-point">
                        <span class="fw-medium">3.2.1</span> 应用在 idle 状态下内存占用不得超过100MB
                        <p class="audit-note">复杂应用（如图形处理、游戏等）可适当放宽</p>
                    </li>
                    <li class="audit-point">
                        <span class="fw-medium">3.2.2</span> 应用不得过度消耗设备CPU、电池等资源
                        <p class="audit-note">禁止在后台执行不必要的操作或频繁唤醒设备</p>
                    </li>
                </ul>
            </div>
            
            <div class="audit-subsection">
                <h3 class="fw-semibold">3.3 兼容性</h3>
                <ul class="list-unstyled">
                    <li class="audit-point">
                        <span class="fw-medium">3.3.1</span> 应用必须支持目标平台的主流版本（如iOS 14+，Android 8.0+）
                        <p class="audit-note">需在审核时提供支持版本列表</p>
                    </li>
                    <li class="audit-point">
                        <span class="fw-medium">3.3.2</span> 应用必须在主流设备型号上正常运行，无显示异常或功能缺失
                        <p class="audit-note">至少覆盖市场占有率前80%的设备</p>
                    </li>
                </ul>
            </div>
        </div>

        <div class="audit-section">
            <h2 class="fw-bold mb-4">四、审核流程与结果</h2>
            
            <div class="audit-subsection">
                <h3 class="fw-semibold">4.1 审核周期</h3>
                <p>标准审核周期为5个工作日，紧急审核可在24小时内完成（需额外支付加急费用支付到<a href="https://afdian.com/a/leonmmcoset">站长爱发电</a>（使用自定义金额并备注）5元）</p>
            </div>
            
            <div class="audit-subsection">
                <h3 class="fw-semibold">4.2 审核结果通知</h3>
                <p>审核结果将通过邮件和平台内消息通知开发者，包含审核通过或拒绝上线两种结果</p>
            </div>
            
            <div class="audit-subsection">
                <h3 class="fw-semibold">4.3 申诉机制</h3>
                <p>如对审核结果有异议，开发者可在收到通知后7个工作日内向<a href="mailto:leonmmcoset@outlook.com">站长邮件</a>提交申诉，我们将在3个工作日内进行复核</p>
            </div>
        </div>

        <div class="text-center mt-6">
            <p class="text-muted">© 2025 LeonAPP. 保留所有权利.</p>
        </div>
    </div>

    <script src="/js/bootstrap.bundle.js"></script>
</body>
</html>