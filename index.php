<?php session_start(); // 页面需要判断登录状态，必须加session_start
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>工分系统</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Microsoft YaHei", PingFang SC, sans-serif;
        }
        body {
            background-color: #f5f7fa;
            padding-top: 70px;
            padding-bottom: 40px;
        }
        /* 顶部导航栏 固定悬浮 */
        nav {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 60px;
            background: #233454;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 999;
        }
        nav a {
            color: #e8edf7;
            text-decoration: none;
            font-size: 15px;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.24s ease;
        }
        nav a:hover {
            background: rgba(255,255,255,0.15);
            color: #fff;
        }
        nav a:last-child {
            margin-left: auto;
        }
        /* 主容器居中卡片 */
        .container {
            width: 92%;
            max-width: 800px;
            margin: 0 auto;
        }
        .tip-card {
            background: #fff;
            padding: 20px 26px;
            border-radius: 12px;
            box-shadow: 0 2px 14px rgba(0,0,0,0.06);
            margin-bottom: 20px;
        }
        .tip-card p {
            font-size: 15px;
            line-height: 1.7;
            color: #444;
        }
        .tip-card p + p {
            margin-top: 10px;
        }
        .title-box {
            background: #2b4270;
            color: white;
            padding: 18px 26px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 24px;
            font-weight: 600;
        }
        .warn-text {
            color: #e53e3e;
            font-weight: 500;
        }
        /* 移动端适配 */
        @media (max-width: 640px) {
            nav {
                padding: 0 16px;
                gap: 6px;
            }
            nav a {
                padding: 6px 10px;
                font-size: 13px;
            }
            .title-box, .tip-card {
                padding: 16px 18px;
            }
        }
    </style>
</head>
<body>
    <nav>
        <a href="php/work_list.php">工作大厅</a>
        <a href="publish_one.html">发布一级工作</a>
        <?php if (!isset($_SESSION["user_id"])): ?>
            <a href="login_register.html">登录/注册</a>
        <?php else:?>
            <a href="php/my_space.php">我的账号</a>
        <?php endif;?>
    </nav>

    <div class="container">
        <!-- 提示卡片 -->
        <div class="tip-card">
            <p>说明：发布工分交易工作需要支付工分作为劳动价值交换的媒介</p>
            <p>发布一级工作不需要支付工分，工分直接从系统发送给接单者</p>
        </div>

        <!-- 标题区域 -->
        <div class="title-box">
            <h1>平台说明</h1>
        </div>

        <!-- 平台规则卡片 -->
        <div class="tip-card">
            <p>工分为补充货币的一种，不存在任何的金融价值，不可充值，不可转账，不可私下交易，仅作为社区内部的一种劳动价值交换（互助性）社区的代币媒介</p>
            <p class="warn-text">一切私下交易，平台不承担任何责任</p>
        </div>
    </div>
</body>
</html>