<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
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
        $sql_user = "CREATE　TABLE user(
            id int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            email varchar(255) NOT NULL,
            username varchar(255) NOT NULL,
            password varchar(255) NOT NULL,
            score Number(11) NOT NULL, -- 工分
            time varchar(255) NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY email (email),
            UNIQUE KEY username (username)
        );";
        // 已发布的工作列表
        $sql_work1 = "CREATE　TABLE work_list(
            id int(11) NOT NULL AUTO_INCREMENT,
            status enum(Active,WIP,finish) NOT NULL, -- 活动、进行中、完成
            title varchar(255) NOT NULL,
            content varchar(255) NOT NULL,
            enable_time_limit boolean NOT NULL,
            deadline INT(11) NOT NULL,
            attachment varchar(255) NOT NULL,
            pay Number(11) NOT NULL,
            work_type enum('common','level1') NOT NULL, -- 普通、一级
            publisher_uid INT(11) NOT NULL,
            time varchar(255) NOT NULL DEFAULT CURRENT_TIMESTAMP,
        );";
        // 交易列表
        $sql_jiaoyi = "CREATE　TABLE jiaoyi(
            id int(11) NOT NULL AUTO_INCREMENT,
            work_id int(11) NOT NULL,
            work_type enum('common','level1') NOT NULL, -- 普通、一级
            -- 下面这两个列应该关联工作列表，问ai怎么写
            status enum(Active,WIP,finish) NOT NULL, -- 活动、进行中、完成
            pay Number(11) NOT NULL,
            worker_uid INT(11) NOT NULL,
            publisher_uid INT(11) NOT NULL,
            worker_attachment varchar(255) NOT NULL,
            worker_answer varchar(255) NOT NULL,
            updatetime varchar(255) NOT NULL DEFAULT CURRENT_TIMESTAMP);";
        $sql_work_chat = "CREATE TABLE workChat(
            id int(11)
            work_id int(11)
            sender_uid(11)
            receiver_uid(11)
            message 
        );";
    ?>
</body>
</html>