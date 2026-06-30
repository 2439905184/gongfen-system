<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>工分系统</title>
</head>
<body>
    <form action="">
        <input type="text" placeholder="主机地址" value="127.0.0.1">
        <input type="text" placeholder="数据库名" value="">
        <input type="text" placeholder="数据库用户名" value="root">
        <input type="text" placeholder="数据库密码" value="">
        <input type="text" placeholder="数据表前缀" value="gongfen_">
        <input type="submit" value="安装">
    </form>
    <?php

    // 用户表
    $sql_user = "CREATE TABLE IF NOT EXISTS `user` (
        `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '用户ID',
        `email` VARCHAR(255) NOT NULL COMMENT '邮箱',
        `username` VARCHAR(50) NOT NULL COMMENT '用户名',
        `password` VARCHAR(255) NOT NULL COMMENT '密码（加密存储）',
        `score` INT(11) NOT NULL DEFAULT 0 COMMENT '工分余额',
        `create_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '注册时间',
        `update_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_email` (`email`),
        UNIQUE KEY `uk_username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';";

    // 工作列表（已发布的任务）
    $sql_work_list = "CREATE TABLE IF NOT EXISTS `work_list` (
        `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '任务ID',
        `status` ENUM('Active','WIP','Finish') NOT NULL DEFAULT 'Active' COMMENT '状态：Active-待接单 WIP-进行中 Finish-已完成',
        `title` VARCHAR(255) NOT NULL COMMENT '任务标题',
        `content` TEXT NOT NULL COMMENT '任务详情',
        `enable_time_limit` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否限时：0-否 1-是',
        `deadline` DATETIME DEFAULT NULL COMMENT '截止时间',
        `attachment` VARCHAR(500) DEFAULT '' COMMENT '附件路径',
        `pay` INT(11) NOT NULL DEFAULT 0 COMMENT '工分报酬',
        `work_type` ENUM('common','level1') NOT NULL DEFAULT 'common' COMMENT '类型：common-普通工分任务 level1-一级系统任务',
        `publisher_uid` INT(11) NOT NULL COMMENT '发布者用户ID',
        `worker_uid` INT(11) DEFAULT NULL COMMENT '接单者用户ID',
        `create_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '发布时间',
        `update_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
        PRIMARY KEY (`id`),
        KEY `idx_publisher` (`publisher_uid`),
        KEY `idx_worker` (`worker_uid`),
        KEY `idx_status` (`status`),
        KEY `idx_create_time` (`create_time`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='任务列表';";

    // 交易记录表（工分明细）
    $sql_transaction = "CREATE TABLE IF NOT EXISTS `score_transaction` (
        `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '记录ID',
        `work_id` INT(11) NOT NULL COMMENT '关联任务ID',
        `work_type` ENUM('common','level1') NOT NULL COMMENT '任务类型',
        `user_id` INT(11) NOT NULL COMMENT '用户ID',
        `type` ENUM('income','expense','system') NOT NULL COMMENT '类型：income-收入 expense-支出 system-系统发放',
        `amount` INT(11) NOT NULL COMMENT '工分数额',
        `balance` INT(11) NOT NULL COMMENT '变动后余额',
        `remark` VARCHAR(255) DEFAULT '' COMMENT '备注',
        `create_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '发生时间',
        PRIMARY KEY (`id`),
        KEY `idx_user_id` (`user_id`),
        KEY `idx_work_id` (`work_id`),
        KEY `idx_create_time` (`create_time`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='工分交易记录表';";

    // 任务聊天记录
    $sql_work_chat = "CREATE TABLE IF NOT EXISTS `work_chat` (
        `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '消息ID',
        `work_id` INT(11) NOT NULL COMMENT '关联任务ID',
        `sender_uid` INT(11) NOT NULL COMMENT '发送者用户ID',
        `receiver_uid` INT(11) NOT NULL COMMENT '接收者用户ID',
        `message` TEXT NOT NULL COMMENT '消息内容',
        `is_read` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否已读：0-未读 1-已读',
        `create_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '发送时间',
        PRIMARY KEY (`id`),
        KEY `idx_work_id` (`work_id`),
        KEY `idx_sender` (`sender_uid`),
        KEY `idx_receiver` (`receiver_uid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='任务聊天记录表';";

?>
    ?>
</body>
</html>