<?php
declare(strict_types=1); // 开启严格类型检查
session_start();
header("Content-Type:text/html;charset=utf-8");

// 全局常量/配置（减少重复定义）
define('ERROR_MSG_DEFAULT', '系统异常，请稍后重试');
define('LOGIN_REQUIRED_MSG', '请先登录后操作');
define('PERMISSION_DENIED_MSG', '权限不足，无法操作');

// 工具函数：统一XSS过滤
function escapeHtml(mixed $str): string {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// 初始化变量（严格类型）
$loginUid = (int)($_SESSION['user_id'] ?? 0);
$workId = (int)($_GET['id'] ?? 0);

// 基础校验（合并GET/POST通用校验）
if ($loginUid <= 0 || $workId <= 0) {
    // 生产环境禁用var_dump，避免泄露敏感信息
    http_response_code(400);
    die(($_SERVER['REQUEST_METHOD'] === 'POST') 
        ? json_encode(['status'=>'error','message'=>LOGIN_REQUIRED_MSG], JSON_UNESCAPED_UNICODE)
        : LOGIN_REQUIRED_MSG
    );
}

// 引入数据库（增加文件存在性检查）
$dbFiles = [__DIR__ . '/lib/Dabase.php', __DIR__ . '/../config.php'];
foreach ($dbFiles as $file) {
    if (!file_exists($file)) {
        http_response_code(500);
        die(ERROR_MSG_DEFAULT);
    }
    include $file;
}

// 初始化数据库连接
try {
    $DB_API = new DB_API($config);
    if (empty($DB_API->pdo) || !$DB_API->pdo instanceof PDO) {
        throw new PDOException('数据库连接失败');
    }
} catch (Exception $e) {
    http_response_code(500);
    die(ERROR_MSG_DEFAULT);
}

$dbPrefix = $config['db_prefix'] ?? '';
// 统一表名定义（避免重复拼接）
$tables = [
    'work' => "{$dbPrefix}work_list",
    'user' => "{$dbPrefix}user",
    'chat' => "{$dbPrefix}work_chat",
    'scoreLog' => "{$dbPrefix}score_transaction"
];

// POST请求处理（接口逻辑）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header("Content-Type:application/json;charset=utf-8");
    
    // 重新获取POST参数（严格类型）
    $postWorkId = (int)($_POST['work_id'] ?? 0);
    if ($postWorkId <= 0 || $postWorkId !== $workId) { // 防参数篡改
        echo json_encode([
            'status'=>'error',
            'message'=>'非法请求参数'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 校验任务归属与状态（优化SQL，只查必要字段）
    $sqlCheck = "SELECT pay, worker_uid, publisher_uid, work_type 
                 FROM {$tables['work']} 
                 WHERE id=:wid AND publisher_uid=:uid AND status='WIP' 
                 LIMIT 1";
    $checkRes = $DB_API->execQuery($sqlCheck, [":wid"=>$workId, ":uid"=>$loginUid], true);
    if (empty($checkRes) || count($checkRes) === 0) {
        echo json_encode([
            'status'=>'error',
            'message'=>'无审核权限或任务状态异常'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $work = $checkRes[0];
    $reward = max(0, (int)$work['pay']); // 确保工分非负
    $workerId = (int)$work['worker_uid'];
    $pubId = (int)$work['publisher_uid'];
    $wType = trim($work['work_type']);

    // 工分合法性校验
    if ($reward <= 0) {
        echo json_encode([
            'status'=>'error',
            'message'=>'任务工分设置异常，无法审核'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($workerId <= 0) {
        echo json_encode([
            'status'=>'error',
            'message'=>'接单者信息异常，无法发放工分'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 开启数据库事务
    $DB_API->pdo->beginTransaction();
    $errorMsg = ERROR_MSG_DEFAULT;

    try {
        // 1. 更新任务状态为Finish（校验执行结果）
        $updateWork = $DB_API->update($tables['work'], ["id"=>$workId], ["status"=>"Finish"]);
        if ($updateWork === false || $DB_API->pdo->rowCount() === 0) {
            throw new Exception("任务状态更新失败");
        }

        // 2. 区分任务类型处理工分
        if ($wType === "common") {
            // 普通任务：发布者扣款，接单者收款
            // 先校验发布者余额是否足够
            $pubScore = $DB_API->execQuery(
                "SELECT score FROM {$tables['user']} WHERE id=:pid LIMIT 1",
                [":pid"=>$pubId],
                true
            );
            if (empty($pubScore) || (int)$pubScore[0]['score'] < $reward) {
                throw new Exception("发布者工分余额不足，无法支付");
            }

            // 扣发布者工分（校验执行结果）
            $subPub = $DB_API->execQuery(
                "UPDATE {$tables['user']} SET score = score - :num WHERE id=:pid",
                [":num"=>$reward, ":pid"=>$pubId],
                true
            );
            if ($subPub === false || $DB_API->pdo->rowCount() === 0) {
                throw new Exception("发布者工分扣除失败");
            }

            // 发布者扣款后余额
            $pubAfter = $DB_API->execQuery(
                "SELECT score FROM {$tables['user']} WHERE id=:pid LIMIT 1",
                [":pid"=>$pubId],
                true
            );
            $pubAfterScore = (int)$pubAfter[0]['score'];

            // 发布者支出流水（校验插入结果）
            $logPub = [
                "work_id" => $workId,
                "work_type" => "common",
                "user_id" => $pubId,
                "type" => "expense",
                "amount" => $reward,
                "balance" => $pubAfterScore,
                "remark" => "发布普通任务支付工分，任务ID：{$workId}"
            ];
            $logPubId = $DB_API->add($tables['scoreLog'], $logPub);
            if ($logPubId === false) {
                throw new Exception("发布者流水记录失败");
            }

            // 接单者加钱
            $addWorker = $DB_API->execQuery(
                "UPDATE {$tables['user']} SET score = score + :num WHERE id=:wid",
                [":num"=>$reward, ":wid"=>$workerId],
                true
            );
            if ($addWorker === false || $DB_API->pdo->rowCount() === 0) {
                throw new Exception("接单者工分发放失败");
            }

            // 接单者收入流水
            $workerAfter = $DB_API->execQuery(
                "SELECT score FROM {$tables['user']} WHERE id=:wid LIMIT 1",
                [":wid"=>$workerId],
                true
            );
            $workerAfterScore = (int)$workerAfter[0]['score'];
            
            $logWorker = [
                "work_id" => $workId,
                "work_type" => "common",
                "user_id" => $workerId,
                "type" => "income",
                "amount" => $reward,
                "balance" => $workerAfterScore,
                "remark" => "完成普通任务获得奖励，任务ID：{$workId}"
            ];
            $logWorkerId = $DB_API->add($tables['scoreLog'], $logWorker);
            if ($logWorkerId === false) {
                throw new Exception("接单者流水记录失败");
            }

        } elseif ($wType === "level1") {
            // 一级系统任务：仅接单者加钱
            $addWorker = $DB_API->execQuery(
                "UPDATE {$tables['user']} SET score = score + :num WHERE id=:wid",
                [":num"=>$reward, ":wid"=>$workerId],
                true
            );
            if ($addWorker === false || $DB_API->pdo->rowCount() === 0) {
                throw new Exception("系统工分发放失败");
            }

            // 接单者流水
            $workerAfter = $DB_API->execQuery(
                "SELECT score FROM {$tables['user']} WHERE id=:wid LIMIT 1",
                [":wid"=>$workerId],
                true
            );
            $workerAfterScore = (int)$workerAfter[0]['score'];
            
            $logWorker = [
                "work_id" => $workId,
                "work_type" => "level1",
                "user_id" => $workerId,
                "type" => "system",
                "amount" => $reward,
                "balance" => $workerAfterScore,
                "remark" => "完成一级系统任务，系统发放工分，任务ID：{$workId}"
            ];
            $logWorkerId = $DB_API->add($tables['scoreLog'], $logWorker);
            if ($logWorkerId === false) {
                throw new Exception("系统任务流水记录失败");
            }
        } else {
            throw new Exception("不支持的任务类型：{$wType}");
        }

        // 提交事务
        $DB_API->pdo->commit();
        echo json_encode([
            "status"=>"success",
            "message"=>"审核完成，工分已处理完毕",
            "redirect"=>"my_work.php?tab=publish"
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (PDOException $e) {
        // 数据库异常单独捕获
        $DB_API->pdo->rollBack();
        $errorMsg = "数据库操作失败：" . $e->getMessage();
        // 生产环境可记录日志，屏蔽具体错误
        error_log("审核任务异常：" . $e->getMessage() . " | 任务ID：{$workId}");
    } catch (Exception $e) {
        // 业务异常捕获
        $DB_API->pdo->rollBack();
        $errorMsg = $e->getMessage();
    }

    // 异常返回
    echo json_encode([
        "status"=>"error",
        "message"=>$errorMsg
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// GET请求处理（页面展示）
// 查询任务+接单者信息（优化SQL，只查必要字段）
$sqlWork = "
SELECT w.id, w.title, w.pay, w.status, w.work_type, 
       pu.nickname pub_name, wk.nickname worker_name
FROM {$tables['work']} w
LEFT JOIN {$tables['user']} pu ON w.publisher_uid = pu.id
LEFT JOIN {$tables['user']} wk ON w.worker_uid = wk.id
WHERE w.id = :wid LIMIT 1
";
$workRes = $DB_API->execQuery($sqlWork, [":wid"=>$workId], true);
if (empty($workRes)) {
    http_response_code(404);
    die("任务不存在");
}
$work = $workRes[0];

// 权限校验：仅发布人可审核，且任务为WIP状态
if ($work['publisher_uid'] != $loginUid) {
    http_response_code(403);
    die(PERMISSION_DENIED_MSG);
}
if ($work['status'] != 'WIP') {
    die("仅进行中的任务可审核，当前任务状态：{$work['status']}");
}

// 查询聊天记录（交付答复）
$sqlChat = "
SELECT c.message, c.create_time, s.nickname send_name
FROM {$tables['chat']} c
LEFT JOIN {$tables['user']} s ON c.sender_uid = s.id
WHERE c.work_id = :wid
ORDER BY c.create_time ASC
";
$chatList = $DB_API->execQuery($sqlChat, [":wid"=>$workId], true) ?: [];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>任务审核 - 工分系统</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Microsoft YaHei"}
body{
    background:linear-gradient(135deg,#667eea,#764ba2);
    min-height:100vh;padding:20px;position:relative;overflow-x:hidden;
}
body::before,body::after{
    content:"";position:fixed;border-radius:50%;background:rgba(255,255,255,0.1);z-index:0;
}
body::before{width:500px;height:500px;top:-200px;right:-100px;}
body::after{width:400px;height:400px;bottom:-100px;left:-100px;}
.container{max-width:800px;margin:0 auto;position:relative;z-index:1;}
.back{
    display:inline-flex;align-items:center;gap:6px;color:#fff;text-decoration:none;margin-bottom:16px;font-size:14px;
}
.card{
    background:#fff;border-radius:16px;padding:32px;box-shadow:0 10px 40px rgba(0,0,0,0.2);margin-bottom:20px;
}
.title{font-size:24px;color:#333;margin-bottom:14px;}
.tags{display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap;}
.tag{padding:4px 12px;border-radius:6px;font-size:13px;}
.tag-wip{background:#fff7e6;color:#fa8c16;}
.tag-level1{background:#f0f9eb;color:#67c23a;}
.info-row{display:flex;flex-wrap:wrap;gap:20px;font-size:14px;color:#666;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid #eee;}
.info-row strong{color:#333;}
.chat-title{font-size:17px;color:#333;margin-bottom:16px;font-weight:500;}
.chat-item{border:1px solid #eee;border-radius:10px;padding:18px;margin-bottom:14px;}
.chat-header{display:flex;justify-content:space-between;font-size:13px;color:#999;margin-bottom:10px;flex-wrap:wrap;gap:8px;}
.chat-content{white-space:pre-wrap;line-height:1.7;color:#444;font-size:14px;}
.audit-box{margin-top:26px;padding-top:24px;border-top:1px solid #eee;text-align:center;}
.audit-tip{color:#f56c6c;font-size:14px;margin-bottom:16px;line-height:1.5;}
.btn-audit{
    padding:13px 36px;border:none;border-radius:8px;background:linear-gradient(135deg,#67c23a,#42a850);
    color:#fff;font-size:16px;cursor:pointer;transition:0.3s;
}
.btn-audit:hover{opacity:0.92;transform:translateY(-2px);}
.btn-audit:disabled{opacity:0.6;cursor:not-allowed;transform:none;}
@media(max-width:600px){
    .card{padding:22px;}
    .info-row{flex-direction:column;gap:6px;}
}
</style>
</head>
<body>
<div class="container">
    <a href="work_detail.php?id=<?=escapeHtml($workId)?>" class="back">← 返回任务详情</a>
    <div class="card">
        <h1 class="title"><?=escapeHtml($work['title'])?></h1>
        <div class="tags">
            <span class="tag tag-wip">进行中（待审核）</span>
            <?php if($work['work_type'] == 'level1'):?>
                <span class="tag tag-level1">一级任务（系统发放工分）</span>
            <?php endif;?>
        </div>
        <div class="info-row">
            <div>发布人：<strong><?=escapeHtml($work['pub_name'])?></strong></div>
            <div>接单者：<strong><?=escapeHtml($work['worker_name'])?></strong></div>
            <div>奖励工分：<strong><?=escapeHtml($work['pay'])?> 工分</strong></div>
        </div>

        <div class="chat-title">接单者提交的交付记录</div>
        <?php if(empty($chatList)):?>
            <p style="color:#999;text-align:center;padding:30px 0;">接单者暂未提交任何交付答复</p>
        <?php else:?>
            <?php foreach($chatList as $chat):?>
                <div class="chat-item">
                    <div class="chat-header">
                        <span>发送人：<?=escapeHtml($chat['send_name'])?></span>
                        <span><?=escapeHtml($chat['create_time'])?></span>
                    </div>
                    <div class="chat-content"><?=escapeHtml($chat['message'])?></div>
                </div>
            <?php endforeach;?>
        <?php endif;?>

        <div class="audit-box">
            <p class="audit-tip">审核通过后，任务将标记为已完成，系统自动发放工分给接单者，不可撤销</p>
            <button class="btn-audit" id="auditBtn" onclick="auditPass(<?=escapeHtml($workId)?>)">审核通过，发放工分</button>
        </div>
    </div>
</div>
<script>
// 防重复提交：增加锁机制
let isSubmitting = false;
function auditPass(wid){
    if(isSubmitting) return; // 已在提交中，直接返回
    if(!confirm("确认审核通过？工分将自动发放给接单者，任务永久标记完成")) return;
    
    const btn = document.getElementById('auditBtn');
    isSubmitting = true;
    btn.disabled = true;
    btn.textContent = "处理中...";
    
    const fd = new FormData();
    fd.append("work_id", wid);
    
    fetch(window.location.href,{ // 显式指定URL，避免空值问题
        method:"POST",
        body:fd,
        credentials: 'include' // 携带Cookie，保证Session有效
    })
    .then(res=>{
        if(!res.ok) throw new Error('网络请求失败');
        return res.json();
    })
    .then(json=>{
        alert(json.message);
        if(json.status === "success") {
            setTimeout(()=>{ // 延迟跳转，提升体验
                window.location.href = json.redirect || "my_work.php?tab=publish";
            }, 1000);
        } else {
            isSubmitting = false;
            btn.disabled = false;
            btn.textContent = "审核通过，发放工分";
        }
    })
    .catch(err=>{
        console.error('审核失败：', err);
        alert("网络异常或系统错误，请稍后重试");
        isSubmitting = false;
        btn.disabled = false;
        btn.textContent = "审核通过，发放工分";
    });
}
</script>
</body>
</html>