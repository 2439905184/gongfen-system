<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

// 未登录跳转
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login_register.html");
    exit;
}
$uid = $_SESSION['user_id'];

// 引入数据库
include __DIR__ . '/lib/Database.php';
include __DIR__ . '/../config.php';
$DB_API = new DB_API($config);
$prefix = $config['db_prefix'];
$table_work = "{$prefix}work_list";
$table_user = "{$prefix}user";

// 获取当前标签 all / publish / receive
$tab = $_GET['tab'] ?? 'all';

// 根据标签生成SQL
if ($tab === 'publish') {
    // 我发布的任务
    $sql = "SELECT w.*, u.nickname as worker_name 
            FROM {$table_work} w
            LEFT JOIN {$table_user} u ON w.worker_uid = u.id
            WHERE w.publisher_uid = :uid
            ORDER BY w.create_time DESC";
    $params = [":uid" => $uid];
} elseif ($tab === 'receive') {
    // 我接单的任务
    $sql = "SELECT w.*, u.nickname as publisher_name
            FROM {$table_work} w
            LEFT JOIN {$table_user} u ON w.publisher_uid = u.id
            WHERE w.worker_uid = :uid
            ORDER BY w.create_time DESC";
    $params = [":uid" => $uid];
} else {
    // 全部（发布+接单合并）
    $sql = "SELECT w.*,
            IF(w.publisher_uid = :publisher_uid, pu.nickname, ru.nickname) as target_name,
            IF(w.publisher_uid = :publisher_uid2, 'publish', 'receive') as my_type
            FROM {$table_work} w
            LEFT JOIN {$table_user} pu ON w.publisher_uid = pu.id
            LEFT JOIN {$table_user} ru ON w.worker_uid = ru.id
            WHERE w.publisher_uid = :publisher_uid3 OR w.worker_uid = :publisher_uid4
            ORDER BY w.create_time DESC";
    $params = [
        ":publisher_uid" => $uid, 
        ":publisher_uid2" => $uid, 
        ":publisher_uid3" => $uid, 
        ":publisher_uid4" => $uid, 
    ];
}

$workList = $DB_API->execQuery($sql, $params, true);
if (!$workList) $workList = [];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>我的任务 - 工分系统</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{
    font-family:"Microsoft YaHei",sans-serif;
    background:linear-gradient(135deg,#667eea,#764ba2);
    min-height:100vh;padding:20px;position:relative;overflow-x:hidden;
}
body::before,body::after{
    content:"";position:fixed;border-radius:50%;background:rgba(255,255,255,0.1);z-index:0;
}
body::before{width:500px;height:500px;top:-200px;right:-100px;}
body::after{width:400px;height:400px;bottom:-100px;left:-100px;}
.container{max-width:900px;margin:0 auto;position:relative;z-index:1;}
.top-bar{
    display:flex;justify-content:space-between;align-items:center;
    color:#fff;margin-bottom:24px;
}
.logo{display:flex;align-items:center;gap:10;font-size:20px;font-weight:600;color:#fff;text-decoration:none;}
.logo-icon{
    width:40px;height:40px;border-radius:10px;background:rgba(255,255,255,0.2);
    display:flex;align-items:center;justify-content:center;
}
.logo-icon svg{width:22px;height:22px;fill:#fff;}
.user-info{display:flex;gap:16;align-items:center;font-size:14px;}
.score-tag{background:rgba(255,255,0.2);padding:6px 12px;border-radius:20px;}
.user-info a{color:rgba(255,255,0.9);text-decoration:none;}
.page-card{background:#fff;border-radius:16px;padding:26px;box-shadow:0 10px 40px rgba(0,0,0,0.2);}
.tabs{display:flex;background:#f5f7fa;border-radius:10px;padding:6px;margin-bottom:20px;gap:4px;}
.tab-item{
    flex:1;text-align:center;padding:10px;border-radius:8px;cursor-pointer;font-size:14px;color:#666;
    text-decoration:none;transition:0.3s;
}
.tab-item.active{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;box-shadow:0 2px 8px rgba(102,126,234,0.2);}
.work-list{display:flex;flex-direction:column;gap:16px;}
.work-card{
    border:1px solid #eee;border-radius:12px;padding:22px;transition:0.3s;cursor:pointer;
}
.work-card:hover{box-shadow:0 6px 24px rgba(0,0,0,0.08);transform:translateY(-2px);}
.card-head{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;}
.title{font-size:18px;font-weight:600;color:#333;}
.pay{font-size:20px;color:#f56c6c;font-weight:bold;}
.tag-group{display:flex;gap:8px;margin:12px 0;}
.tag{padding:3px 10;border-radius:4;font-size:12px;}
.tag-open{background:#e6f7ff;color:#1890ff;}
.tag-wip{background:#fff7e6;color:#fa8c16;}
.tag-finish{background:#f6ffed;color:#52c41a;}
.tag-level1{background:#f0f9eb;color:#67c23a;}
.tag-publish{background:#fff0e6;color:#e67700;}
.tag-receive{background:#e8f4ff;color:#0066cc;}
.content{color:#666;font-size:14px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.6;margin-bottom:14px;}
.card-foot{display:flex;justify-content:space-between;color:#999;font-size:13px;padding-top:12px;border-top:1px solid #eee;}
.empty{text-align:center;padding:60px 20;color:#999;font-size:16px;}
.empty a{color:#667eea;text-decoration:none;}
@media(max-width:600px){
    .top-bar{flex-direction:column;gap:12;text-align:center;}
    .card-head{flex-direction:column;gap:8;}
    .page-card{padding:18px;}
}
</style>
</head>
<body>
<div class="container">
    <div class="top-bar">
        <a href="work_list.php" class="logo">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
            </div>
            工分系统
        </a>
        <div class="user-info">
            <span class="score-tag">💰 <?=$_SESSION['score']?> 工分</span>
            <span><?=htmlspecialchars($_SESSION['nickname']??$_SESSION['username'])?></span>
            <a href="login.html">退出</a>
        </div>
    </div>
    <div class="page-card">
        <div class="tabs">
            <a href="my_work.php?tab=all" class="tab-item <?=$tab==='all'?'active':''?>">全部任务</a>
            <a href="my_work.php?tab=publish" class="tab-item <?=$tab==='publish'?'active':''?>">我发布的</a>
            <a href="my_work.php?tab=receive" class="tab-item <?=$tab==='receive'?'active':''?>">我接单的</a>
        </div>

        <?php if(count($workList)===0): ?>
            <div class="empty">暂无任务记录<br><a href="work_list.php">去任务大厅接单/发布任务</a></div>
        <?php else: ?>
        <div class="work-list">
            <?php foreach($workList as $w): ?>
            <div class="work-card" onclick="location.href='work_detail.php?id=<?=$w['id']?>'">
                <div class="card-head">
                    <div class="title"><?=htmlspecialchars($w['title'])?></div>
                    <div class="pay"><?=$w['pay']?> <span style="font-size:12px;font-weight:normal;color:#999">工分</span></div>
                </div>
                <div class="tag-group">
                    <?php if($w['status']==='Open'): ?>
                        <span class="tag tag-open">待接单</span>
                    <?php elseif($w['status']==='WIP'): ?>
                        <span class="tag tag-wip">进行中</span>
                    <?php else: ?>
                        <span class="tag tag-finish">已完成</span>
                    <?php endif; ?>
                    <?php if($w['work_type']==='level1'): ?>
                        <span class="tag tag-level1">一级任务</span>
                    <?php endif; ?>
                    <?php if($tab==='publish' || ($tab==='all' && $w['publisher_uid']===$uid)): ?>
                        <span class="tag tag-publish">我发布</span>
                    <?php endif; ?>
                    <?php if($tab==='receive' || ($tab==='all' && $w['worker_uid']===$uid)): ?>
                        <span class="tag tag-receive">我接单</span>
                    <?php endif; ?>
                </div>
                <div class="content"><?=htmlspecialchars(mb_substr($w['content'],0,100))?>...</div>
                <div class="card-foot">
                    <span>发布时间：<?=$w['create_time']?></span>
                    <?php if($tab==='publish' && $w['worker_uid']): ?>
                        <span>接单人：<?=htmlspecialchars($w['worker_name'])?></span>
                    <?php endif; ?>
                    <?php if($tab==='receive'): ?>
                        <span>发布人：<?=htmlspecialchars($w['publisher_name'])?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>