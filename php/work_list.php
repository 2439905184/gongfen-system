<?php
/**
 * 任务列表页
 * 显示所有 Open（待接单）状态的任务
 */

session_start();

include __DIR__ . '/lib/Dabase.php';
include __DIR__ . '/../config.php';

$DB_API = new DB_API($config);
$table_work = $config['db_prefix'] . 'work_list';
$table_user = $config['db_prefix'] . 'user';

// 当前筛选类型（all / common / level1）
$type = $_GET['type'] ?? 'all';

// 构建查询条件
$whereSql = "WHERE w.status = 'Open'";
$params = [];

if ($type === 'common') {
    $whereSql .= " AND w.work_type = 'common'";
} elseif ($type === 'level1') {
    $whereSql .= " AND w.work_type = 'level1'";
}

// 联表查询任务 + 发布者昵称
$sql = "SELECT w.*, 
               u.nickname as publisher_name, 
               u.username as publisher_username
        FROM {$table_work} w
        LEFT JOIN {$table_user} u ON w.publisher_uid = u.id
        {$whereSql}
        ORDER BY w.create_time DESC";

$workList = $DB_API->execQuery($sql, $params, true);

if (!$workList) {
    $workList = [];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>任务大厅 - 工分系统</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Microsoft YaHei", "PingFang SC", -apple-system, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* 背景装饰 */
        body::before,
        body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            z-index: 0;
        }

        body::before {
            width: 500px;
            height: 500px;
            top: -200px;
            right: -100px;
        }

        body::after {
            width: 400px;
            height: 400px;
            bottom: -100px;
            left: -100px;
        }

        /* 容器 */
        .container {
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        /* 顶部导航 */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding: 0 5px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fff;
            font-size: 20px;
            font-weight: 600;
            text-decoration: none;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }

        .logo-icon svg {
            width: 22px;
            height: 22px;
            fill: #fff;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }

        .user-info .score {
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }

        .user-info a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
        }

        .user-info a:hover {
            color: #fff;
        }

        /* 页面标题区 */
        .page-title-bar {
            background: #fff;
            border-radius: 16px;
            padding: 25px 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 5px;
        }

        .page-title p {
            font-size: 14px;
            color: #999;
        }

        .btn-publish {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-publish:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(102, 126, 234, 0.5);
        }

        /* 筛选标签 */
        .filter-tabs {
            background: #fff;
            border-radius: 12px;
            padding: 8px;
            margin-bottom: 20px;
            display: inline-flex;
            gap: 5px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .filter-tab {
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 14px;
            color: #666;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }

        .filter-tab:hover {
            background: #f5f7fa;
        }

        .filter-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        /* 任务列表 */
        .work-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .work-card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            cursor: pointer;
        }

        .work-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .work-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .work-title {
            font-size: 18px;
            color: #333;
            font-weight: 600;
            flex: 1;
            margin-right: 15px;
        }

        .work-pay {
            font-size: 20px;
            color: #f56c6c;
            font-weight: 700;
            white-space: nowrap;
        }

        .work-pay .unit {
            font-size: 12px;
            font-weight: normal;
            color: #999;
            margin-left: 2px;
        }

        /* 标签 */
        .work-tags {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }

        .tag {
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .tag-level1 {
            background: #f0f9eb;
            color: #67c23a;
        }

        .tag-common {
            background: #ecf5ff;
            color: #409eff;
        }

        .tag-time {
            background: #fdf6ec;
            color: #e6a23c;
        }

        /* 内容摘要 */
        .work-content {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* 底部信息 */
        .work-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
            font-size: 13px;
            color: #999;
        }

        .work-meta {
            display: flex;
            gap: 20px;
        }

        .work-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .work-meta svg {
            width: 14px;
            height: 14px;
            fill: #ccc;
        }

        .btn-accept {
            padding: 8px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-accept:hover {
            opacity: 0.9;
            transform: scale(1.05);
        }

        /* 空状态 */
        .empty-state {
            background: #fff;
            border-radius: 12px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-text {
            font-size: 16px;
            color: #999;
            margin-bottom: 20px;
        }

        /* 响应式 */
        @media (max-width: 600px) {
            body {
                padding: 15px;
            }

            .page-title-bar {
                flex-direction: column;
                gap: 15px;
                text-align: center;
                padding: 20px;
            }

            .work-card {
                padding: 20px;
            }

            .work-card-header {
                flex-direction: column;
                gap: 10px;
            }

            .work-pay {
                font-size: 18px;
            }

            .work-footer {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }

            .work-meta {
                flex-wrap: wrap;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 顶部导航 -->
        <div class="header">
            <a href="index.php" class="logo">
                <div class="logo-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                </div>
                工分系统
            </a>
            <div class="user-info">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="score">💰 <?php echo $_SESSION['score'] ?? 0; ?> 工分</span>
                    <span><?php echo htmlspecialchars($_SESSION['nickname'] ?? $_SESSION['username']); ?></span>
                    <a href="user/logout.php">退出</a>
                <?php else: ?>
                    <a href="login.html">登录 / 注册</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- 标题栏 -->
        <div class="page-title-bar">
            <div class="page-title">
                <h1>任务大厅</h1>
                <p>找到适合你的任务，赚取工分</p>
            </div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="publish_level1.php" class="btn-publish">+ 发布任务</a>
            <?php endif; ?>
        </div>

        <!-- 筛选标签 -->
        <div class="filter-tabs">
            <a href="?type=all" class="filter-tab <?php echo $type === 'all' ? 'active' : ''; ?>">全部任务</a>
            <a href="?type=common" class="filter-tab <?php echo $type === 'common' ? 'active' : ''; ?>">普通任务</a>
            <a href="?type=level1" class="filter-tab <?php echo $type === 'level1' ? 'active' : ''; ?>">一级任务</a>
        </div>

        <!-- 任务列表 -->
        <?php if (count($workList) > 0): ?>
            <div class="work-list">
                <?php foreach ($workList as $work): ?>
                    <div class="work-card" onclick="location.href='work_detail.php?id=<?php echo $work['id']; ?>'">
                        <div class="work-card-header">
                            <h3 class="work-title"><?php echo htmlspecialchars($work['title']); ?></h3>
                            <div class="work-pay">
                                <?php echo $work['pay']; ?><span class="unit">工分</span>
                            </div>
                        </div>

                        <div class="work-tags">
                            <?php if ($work['work_type'] === 'level1'): ?>
                                <span class="tag tag-level1">一级任务</span>
                            <?php else: ?>
                                <span class="tag tag-common">普通任务</span>
                            <?php endif; ?>
                            
                            <?php if ($work['enable_time_limit'] == 1 && !empty($work['deadline'])): ?>
                                <span class="tag tag-time">
                                    ⏰ 截止：<?php echo date('m月d日', strtotime($work['deadline'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <p class="work-content"><?php echo htmlspecialchars(mb_substr($work['content'], 0, 100)); ?>...</p>

                        <div class="work-footer">
                            <div class="work-meta">
                                <span>
                                    <svg viewBox="0 0 24 24">
                                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                    </svg>
                                    <?php 
                                    $publisherName = !empty($work['publisher_name']) ? $work['publisher_name'] : $work['publisher_username'];
                                    echo htmlspecialchars($publisherName);
                                    ?>
                                </span>
                                <span>
                                    <svg viewBox="0 0 24 24">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                    </svg>
                                    <?php echo date('Y-m-d', strtotime($work['create_time'])); ?>
                                </span>
                            </div>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <button class="btn-accept" onclick="event.stopPropagation(); acceptWork(<?php echo $work['id']; ?>)">我要接单</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- 空状态 -->
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <div class="empty-text">暂无待接单的任务</div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="publish_level1.php" class="btn-publish">发布第一个任务</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="../js/acceptWork.js"></script>
</body>
</html>