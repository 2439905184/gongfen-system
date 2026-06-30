<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>工分系统 - 安装向导</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: "Microsoft YaHei", Arial, sans-serif;
            background: #f5f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .install-box {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 500px;
        }
        .install-title {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }
        .install-tip {
            text-align: center;
            color: #999;
            font-size: 13px;
            margin-bottom: 30px;
        }
        .notice {
            background: #fff7e6;
            border: 1px solid #ffd591;
            color: #d46b08;
            padding: 12px 15px;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #666;
            font-size: 14px;
        }
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #409eff;
        }
        .btn-install {
            width: 100%;
            padding: 12px;
            background: #409eff;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-install:hover {
            background: #66b1ff;
        }
        .btn-install:disabled {
            background: #a0cfff;
            cursor: not-allowed;
        }
        .result-box {
            margin-top: 20px;
            padding: 15px;
            border-radius: 4px;
            font-size: 14px;
            line-height: 1.6;
        }
        .result-success {
            background: #f0f9eb;
            border: 1px solid #e1f3d8;
            color: #67c23a;
        }
        .result-error {
            background: #fef0f0;
            border: 1px solid #fde2e2;
            color: #f56c6c;
        }
        .result-info {
            background: #ecf5ff;
            border: 1px solid #d9ecff;
            color: #409eff;
        }
        .step-list {
            list-style: none;
            padding: 0;
        }
        .step-list li {
            padding: 5px 0;
            padding-left: 20px;
            position: relative;
        }
        .step-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #67c23a;
            font-weight: bold;
        }
        .step-list li.error:before {
            content: "✗";
            color: #f56c6c;
        }
        .copyright {
            text-align: center;
            color: #999;
            font-size: 12px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="install-box">
        <h1 class="install-title">工分系统安装向导</h1>
        <p class="install-tip">请先手动创建好数据库，再填写以下信息</p>
        
        <?php
        // 配置文件路径
        $config_file = dirname(__FILE__) . '/config.php';
        $is_installed = file_exists($config_file);
        
        // 如果已经安装，提示
        if ($is_installed && !isset($_POST['force_install'])) {
            echo '<div class="result-box result-info">';
            echo '<p><strong>检测到系统已安装！</strong></p>';
            echo '<p>如需重新安装，请删除 config.php 文件后刷新页面。</p>';
            echo '</div>';
            echo '<div class="copyright">工分系统 v1.0</div>';
            exit;
        }
        
        // 处理表单提交
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $db_host = trim($_POST['db_host']);
            $db_name = trim($_POST['db_name']);
            $db_user = trim($_POST['db_user']);
            $db_pass = trim($_POST['db_pass']);
            $db_prefix = trim($_POST['db_prefix']);
            
            $install_log = [];
            $has_error = false;
            
            echo '<div class="result-box result-info">';
            echo '<p><strong>正在安装，请稍候...</strong></p>';
            echo '<ul class="step-list">';
            
            // 步骤1：测试数据库连接
            try {
                $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $install_log[] = '数据库连接成功';
                echo '<li>数据库连接成功</li>';
            } catch (PDOException $e) {
                $has_error = true;
                $install_log[] = '数据库连接失败：' . $e->getMessage();
                echo '<li class="error">数据库连接失败：' . $e->getMessage() . '</li>';
            }
            
            // 步骤2：选择数据库（不创建，直接用）
            if (!$has_error) {
                try {
                    $pdo->exec("USE `$db_name`");
                    $install_log[] = '数据库选择成功';
                    echo '<li>数据库选择成功</li>';
                } catch (PDOException $e) {
                    $has_error = true;
                    $install_log[] = '数据库不存在或无权限访问：请先在数据库管理工具中手动创建数据库';
                    echo '<li class="error">数据库不存在或无权限访问</li>';
                    echo '<li class="error" style="padding-left: 0; font-size: 12px; color: #999;">提示：请先在 phpMyAdmin / Navicat 中手动创建名为「' . htmlspecialchars($db_name) . '」的数据库，字符集选 utf8mb4</li>';
                }
            }
            
            // 步骤3：创建数据表
            if (!$has_error) {
                // 用户表
                $sql_user = "CREATE TABLE IF NOT EXISTS `{$db_prefix}user` (
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
                
                try {
                    $pdo->exec($sql_user);
                    $install_log[] = '用户表创建成功';
                    echo '<li>用户表创建成功</li>';
                } catch (PDOException $e) {
                    $has_error = true;
                    $install_log[] = '用户表创建失败：' . $e->getMessage();
                    echo '<li class="error">用户表创建失败：' . $e->getMessage() . '</li>';
                }
                
                // 任务列表
                $sql_work_list = "CREATE TABLE IF NOT EXISTS `{$db_prefix}work_list` (
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
                
                try {
                    $pdo->exec($sql_work_list);
                    $install_log[] = '任务列表创建成功';
                    echo '<li>任务列表创建成功</li>';
                } catch (PDOException $e) {
                    $has_error = true;
                    $install_log[] = '任务列表创建失败：' . $e->getMessage();
                    echo '<li class="error">任务列表创建失败：' . $e->getMessage() . '</li>';
                }
                
                // 工分交易记录表
                $sql_transaction = "CREATE TABLE IF NOT EXISTS `{$db_prefix}score_transaction` (
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
                
                try {
                    $pdo->exec($sql_transaction);
                    $install_log[] = '工分交易记录表创建成功';
                    echo '<li>工分交易记录表创建成功</li>';
                } catch (PDOException $e) {
                    $has_error = true;
                    $install_log[] = '工分交易记录表创建失败：' . $e->getMessage();
                    echo '<li class="error">工分交易记录表创建失败：' . $e->getMessage() . '</li>';
                }
                
                // 任务聊天记录表
                $sql_work_chat = "CREATE TABLE IF NOT EXISTS `{$db_prefix}work_chat` (
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
                
                try {
                    $pdo->exec($sql_work_chat);
                    $install_log[] = '任务聊天记录表创建成功';
                    echo '<li>任务聊天记录表创建成功</li>';
                } catch (PDOException $e) {
                    $has_error = true;
                    $install_log[] = '任务聊天记录表创建失败：' . $e->getMessage();
                    echo '<li class="error">任务聊天记录表创建失败：' . $e->getMessage() . '</li>';
                }
            }
            
            // 步骤4：写入配置文件
            if (!$has_error) {
                $config_content = "<?php\n";
                $config_content .= "// 工分系统配置文件\n";
                $config_content .= "// 自动生成于 " . date('Y-m-d H:i:s') . "\n\n";
                $config_content .= "\$config = [\n";
                $config_content .= "    'db_host' => '$db_host',\n";
                $config_content .= "    'db_port' => 3306,\n";
                $config_content .= "    'db_name' => '$db_name',\n";
                $config_content .= "    'db_user' => '$db_user',\n";
                $config_content .= "    'db_pass' => '$db_pass',\n";
                $config_content .= "    'db_prefix' => '$db_prefix',\n";
                $config_content .= "];\n";
                $config_content .= "?>\n";
                
                if (file_put_contents($config_file, $config_content)) {
                    $install_log[] = '配置文件写入成功';
                    echo '<li>配置文件写入成功</li>';
                } else {
                    $has_error = true;
                    $install_log[] = '配置文件写入失败，请检查目录权限';
                    echo '<li class="error">配置文件写入失败，请检查目录权限</li>';
                }
            }
            
            echo '</ul>';
            
            // 最终结果
            if (!$has_error) {
                echo '<p style="margin-top: 15px; color: #67c23a; font-weight: bold; font-size: 16px;">🎉 安装成功！</p>';
                echo '<p style="margin-top: 8px;">共创建 4 张数据表，配置文件已生成。</p>';
                echo '<p style="margin-top: 8px; color: #f56c6c;"><strong>⚠️  重要：请立即删除 install.php 文件，防止被他人重新安装！</strong></p>';
                echo '<p style="margin-top: 15px;"><a href="index.html" style="color: #409eff; text-decoration: none;">前往首页 →</a></p>';
            } else {
                echo '<p style="margin-top: 15px; color: #f56c6c; font-weight: bold;">❌ 安装失败</p>';
                echo '<p>请检查上述错误信息，修正后重新安装。</p>';
            }
            
            echo '</div>';
            
        } else {
            // 显示安装表单
        ?>
        
        <div class="notice">
            <strong>📌 安装前准备：</strong><br>
            请先在 phpMyAdmin / Navicat 等工具中<strong>手动创建数据库</strong>，字符集选择 <code>utf8mb4</code>，然后再填写以下信息。
        </div>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="db_host">数据库主机地址</label>
                <input type="text" id="db_host" name="db_host" value="127.0.0.1" placeholder="localhost 或 127.0.0.1" required>
            </div>
            
            <div class="form-group">
                <label for="db_name">数据库名称 <span style="color: #f56c6c;">*</span></label>
                <input type="text" id="db_name" name="db_name" value="gongfen" placeholder="请输入已创建好的数据库名" required>
            </div>
            
            <div class="form-group">
                <label for="db_user">数据库用户名</label>
                <input type="text" id="db_user" name="db_user" value="gf" placeholder="请输入数据库用户名" required>
            </div>
            
            <div class="form-group">
                <label for="db_pass">数据库密码</label>
                <input type="password" id="db_pass" name="db_pass" value="" placeholder="请输入数据库密码">
            </div>
            
            <div class="form-group">
                <label for="db_prefix">数据表前缀</label>
                <input type="text" id="db_prefix" name="db_prefix" value="gf_" placeholder="建议使用前缀，如 gf_">
            </div>
            
            <button type="submit" class="btn-install" onclick="return confirm('确认开始安装吗？\n\n请确保数据库已手动创建！')">开始安装</button>
        </form>
        
        <?php
        } // 结束表单显示
        ?>
        
        <div class="copyright">工分系统 v1.0 · 互助社区劳动价值交换平台</div>
    </div>
</body>
</html>