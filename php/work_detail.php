<?php
/**
 * 任务详情页
 * 根据 id 显示单个任务的完整信息
 */

session_start();

// 引入配置和数据库
include __DIR__ . '/lib/Dabase.php';
include __DIR__ . '/../config.php';

// 检查任务 ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("任务ID错误");
}

$work_id = (int)$_GET['id'];
$DB_API = new DB_API($config);

$table_work = $config['db_prefix'] . 'work_list';
$table_user = $config['db_prefix'] . 'user';

// 查询任务详情 + 发布者信息
$sql = "SELECT w.*, 
               u.nickname as publisher_name, 
               u.username as publisher_username
        FROM {$table_work} w
        LEFT JOIN {$table_user} u ON w.publisher_uid = u.id
        WHERE w.id = :work_id LIMIT 1";

$work = $DB_API->execQuery($sql, [
    ":work_id" => $work_id,
], true);

if (!$work || count($work) === 0) {
    die("任务不存在或已删除");
}

$work = $work[0];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>任务详情 - 工分系统</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Microsoft YaHei", "PingFang SC", sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        /* 返回按钮 */
        .back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #fff;
            font-size: 14px;
            text-decoration: none;
            margin-bottom: 15px;
        }

        .back:hover {
            color: #fff;
        }

        /* 卡片 */
        .card {
            background: #fff;
            border-radius: 16px;
            padding: 35px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        /* 标题 */
        .title {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
        }

        /* 标签 */
        .tags {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
        }

        .tag {
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
        }

        .tag-open { background: #e6f7ff; color: #1890ff; }
        .tag-wip { background: #fff7e6; color: #fa8c16; }
        .tag-finish { background: #f6ffed; color: #52c41a; }
        .tag-level1 { background: #f0f9eb; color: #67c23a; }
        .tag-common { background: #ecf5ff; color: #409eff; }

        /* 信息行 */
        .info {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .info-item {
            font-size: 14px;
            color: #666;
        }

        .info-item strong {
            color: #333;
        }

        /* 内容 */
        .content {
            font-size: 15px;
            color: #444;
            line-height: 1.8;
            margin-bottom: 25px;
            white-space: pre-wrap;
        }

        /* 附件 */
        .attachment {
            margin-bottom: 25px;
        }

        .attachment a {
            color: #409eff;
            text-decoration: none;
        }

        .attachment a:hover {
            text-decoration: underline;
        }

        /* 底部操作 */
        .action {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .pay {
            font-size: 22px;
            font-weight: bold;
            color: #f56c6c;
        }

        .btn {
            padding: 12px 28px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            cursor: pointer;
        }

        .btn:hover {
            opacity: 0.95;
        }

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        /* 响应式 */
        @media (max-width: 600px) {
            .card { padding: 25px; }
            .action { flex-direction: column; gap: 15px; align-items: flex-start; }
            .btn { width: 100%; }
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="work_list.php" class="back">← 返回任务大厅</a>

        <div class="card">
            <h1 class="title"><?php echo htmlspecialchars($work['title']); ?></h1>

            <div class="tags">
                <!-- 状态标签 -->
                <?php if ($work['status'] === 'Open'): ?>
                    <span class="tag tag-open">待接单</span>
                <?php elseif ($work['status'] === 'WIP'): ?>
                    <span class="tag tag-wip">进行中</span>
                <?php else: ?>
                    <span class="tag tag-finish">已完成</span>
                <?php endif; ?>

                <!-- 类型标签 -->
                <?php if ($work['work_type'] === 'level1'): ?>
                    <span class="tag tag-level1">一级任务</span>
                <?php else: ?>
                    <span class="tag tag-common">普通任务</span>
                <?php endif; ?>
            </div>

            <div class="info">
                <div class="info-item">
                    发布者：<strong><?php echo htmlspecialchars($work['publisher_name'] ?: $work['publisher_username']); ?></strong>
                </div>
                <div class="info-item">
                    发布时间：<strong><?php echo $work['create_time']; ?></strong>
                </div>
                <?php if ($work['enable_time_limit']): ?>
                    <div class="info-item">
                        截止时间：<strong><?php echo $work['deadline']; ?></strong>
                    </div>
                <?php endif; ?>
            </div>

            <div class="content">
                <?php echo htmlspecialchars($work['content']); ?>
            </div>

            <?php if (!empty($work['attachment'])): ?>
                <div class="attachment">
                    附件链接：<a href="<?php echo htmlspecialchars($work['attachment']); ?>" target="_blank">点击查看</a>
                </div>
            <?php endif; ?>

            <div class="action">
                <div class="pay">报酬：<?php echo $work['pay']; ?> 工分</div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($work['status'] === 'Open'): ?>
                        <button class="btn" onclick="acceptWork(<?php echo $work['id']; ?>)">我要接单</button>
                    <?php else: ?>
                        <button class="btn" disabled>任务已<?php echo $work['status'] === 'WIP' ? '被接走' : '完成'; ?></button>
                    <?php endif; ?>
                <?php else: ?>
                    <button class="btn" onclick="alert('请先登录')">登录后接单</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
   <script src="../js/acceptWork.js"></script>
</body>
</html>