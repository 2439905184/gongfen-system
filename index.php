<?php
session_start();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>工分系统</title>
</head>
<body>
    <nav>
        <a href="virtual_shop.html">虚拟商品商城</a>
        <a href="php/work_list.php">工作大厅</a>
        <a href="publish.html">发布工分交易工作</a>
        <a href="publish_one.html">发布一级工作</a>
        <?php if (!isset($_SESSION["user_id"])): ?>
            <a href="login_register.html">登录/注册</a>
        <?php else:?>
            <a href="php/my_space.php">我的账号</a>
        <?php endif;?>
    </nav>
    <p>说明：发布工分交易工作需要支付工分作为劳动价值交换的媒介</p>
    <p>发布一级工作不需要支付工分，工分直接从系统发送给接单者</p>
    <h1>平台说明</h1>
    <p>工分为补充货币的一种，不存在任何的金融价值，不可充值，不可转账，不可私下交易，仅作为社区内部的一种劳动价值交换（互助性）社区的代币媒介</p>
    <p>一切私下交易，平台不承担任何责任</p>
</body>
</html>