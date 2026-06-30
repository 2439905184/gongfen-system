<?php
session_start();
header("Content-Type:text/html;charset=utf-8");
$loginUid = $_SESSION['user_id'] ?? 0;
$workId = $_GET['id'];
if($loginUid <= 0 || $workId <= 0){
    var_dump($loginUid, $workId);
    die("参数错误或未登录");
}
// 引入数据库
include __DIR__ . '/lib/Dabase.php';
include __DIR__ . '/../config.php';
$DB_API = new DB_API($config);
$pre = $config['db_prefix'];
$tableWork = "{$pre}work_list";
$tableUser = "{$pre}user";
$tableChat = "{$pre}work_chat";

// 查询任务 + 接单者信息
$sqlWork = "
SELECT w.*, pu.nickname pub_name, wk.nickname worker_name
FROM {$tableWork} w
LEFT JOIN {$tableUser} pu ON w.publisher_uid = pu.id
LEFT JOIN {$tableUser} wk ON w.worker_uid = wk.id
WHERE w.id = :wid LIMIT 1
";
$workRes = $DB_API->execQuery($sqlWork, [":wid"=>$workId], true);
if(!$workRes || count($workRes) == 0) die("任务不存在");
$work = $workRes[0];

// 权限：必须是发布人，任务必须是WIP进行中
if($work['publisher_uid'] != $loginUid){
    die("权限不足，只有任务发布人可以审核");
}
if($work['status'] != 'WIP'){
    die("仅进行中的任务可审核，任务已完成/未接单");
}

// 查询该任务所有聊天记录（交付答复）
$sqlChat = "
SELECT c.*, s.nickname send_name, r.nickname rec_name
FROM {$tableChat} c
LEFT JOIN {$tableUser} s ON c.sender_uid = s.id
LEFT JOIN {$tableUser} r ON c.receiver_uid = r.id
WHERE c.work_id = :wid
ORDER BY c.create_time ASC
";
$chatList = $DB_API->execQuery($sqlChat, [":wid"=>$workId], true);
if(!$chatList) $chatList = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
    session_start();
    header("Content-Type:application/json;charset=utf-8");
    $loginUid = $_SESSION['user_id'] ?? 0;
    $workId = $_POST['work_id'];
    if($loginUid <= 0 || $workId <= 0){
        echo json_encode(["status"=>"error","message"=>"非法请求"]);
        exit;
    }
    $pre = $config['db_prefix'];
    $tableWork = "{$pre}work_list";
    $tableUser = "{$pre}user";
    $tableScoreLog = "{$pre}score_transaction";

    // 校验任务归属与状态
    $sqlCheck = "SELECT pay, worker_uid, publisher_uid, work_type FROM {$tableWork} WHERE id=:wid AND publisher_uid=:uid AND status='WIP' LIMIT 1";
    $checkRes = $DB_API->execQuery($sqlCheck, [":wid"=>$workId, ":uid"=>$loginUid], true);
    if(!$checkRes || count($checkRes) === 0){
        echo json_encode(["status"=>"error","message"=>"无审核权限或任务状态异常"]);
        exit;
    }
    $work = $checkRes[0];
    $reward = (int)$work['pay'];
    $workerId = $work['worker_uid'];
    $pubId = $work['publisher_uid'];
    $wType = $work['work_type'];

// 开启数据库事务，保证两条流水原子性
$DB_API->pdo->beginTransaction();
try {
    // 1. 更新任务状态为 Finish
    $updateWork = $DB_API->update($tableWork, ["id"=>$workId], ["status"=>"Finish"]);
    if(!$updateWork) throw new Exception("任务状态更新失败");

    // 2. 区分任务类型处理账户与流水
    if($wType === "common"){
        // ========== 普通任务：发布者扣款，接单者收款 ==========
        // 扣发布者工分
        $subPub = $DB_API->execQuery(
            "UPDATE {$tableUser} SET score = score - :num WHERE id=:pid",
            [":num"=>$reward, ":pid"=>$pubId],
            true
        );
        if(!$subPub) throw new Exception("发布者工分扣除失败");

        // 获取发布者扣款后余额
        $pubAfter = $DB_API->execQuery("SELECT score FROM {$tableUser} WHERE id=:pid", [":pid"=>$pubId], true)[0]['score'];
        // 发布者支出流水
        $logPub = [
            "work_id" => $workId,
            "work_type" => "common",
            "user_id" => $pubId,
            "type" => "expense",
            "amount" => $reward,
            "balance" => $pubAfter,
            "remark" => "发布普通任务，支付工分，任务ID：{$workId}"
        ];
        $DB_API->add($tableScoreLog, $logPub);

        // 接单者加钱
        $addWorker = $DB_API->execQuery(
            "UPDATE {$tableUser} SET score = score + :num WHERE id=:wid",
            [":num"=>$reward, ":wid"=>$workerId],
            true
        );
        if(!$addWorker) throw new Exception("接单者工分发放失败");

        // 接单者收入流水
        $workerAfter = $DB_API->execQuery("SELECT score FROM {$tableUser} WHERE id=:wid", [":wid"=>$workerId], true)[0]['score'];
        $logWorker = [
            "work_id" => $workId,
            "work_type" => "common",
            "user_id" => $workerId,
            "type" => "income",
            "amount" => $reward,
            "balance" => $workerAfter,
            "remark" => "完成普通任务获得奖励，任务ID：{$workId}"
        ];
        $DB_API->add($tableScoreLog, $logWorker);
    }elseif($wType === "level1"){
        // ========== 一级系统任务：仅接单者加钱，发布者无变动 ==========
        $addWorker = $DB_API->execQuery(
            "UPDATE {$tableUser} SET score = score + :num WHERE id=:wid",
            [":num"=>$reward, ":wid"=>$workerId],
            true
        );
        if(!$addWorker) throw new Exception("系统发放工分失败");

        $workerAfter = $DB_API->execQuery("SELECT score FROM {$tableUser} WHERE id=:wid", [":wid"=>$workerId], true)[0]['score'];
        $logWorker = [
            "work_id" => $workId,
            "work_type" => "level1",
            "user_id" => $workerId,
            "type" => "system",
            "amount" => $reward,
            "balance" => $workerAfter,
            "remark" => "完成一级系统任务，系统发放工分，任务ID：{$workId}"
        ];
        $DB_API->add($tableScoreLog, $logWorker);
    }

    // 全部执行成功，提交事务
    $DB_API->pdo->commit();
    echo json_encode([
        "status"=>"success",
        "message"=>"审核完成，工分已处理完毕",
        "redirect"=>"my_work.php?tab=publish"
    ]);
}catch(Exception $e){
    // 任意步骤出错，全部回滚，账目不会错乱
    $DB_API->pdo->rollBack();
    echo json_encode([
        "status"=>"error",
        "message"=>"操作失败：".$e->getMessage()
    ]);
}
exit;
}
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
    content:"";position:fixed;border-radius:50%;background:rgba(255,255,0.1);z-index:0;
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
.tags{display:flex;gap:8px;margin-bottom:18px;}
.tag{padding:4px 12px;border-radius:6px;font-size:13px;}
.tag-wip{background:#fff7e6;color:#fa8c16;}
.tag-level1{background:#f0f9eb;color:#67c23a;}
.info-row{display:flex;flex-wrap:wrap;gap:20px;font-size:14px;color:#666;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid #eee;}
.info-row strong{color:#333;}
.chat-title{font-size:17px;color:#333;margin-bottom:16px;font-weight:500;}
.chat-item{border:1px solid #eee;border-radius:10px;padding:18px;margin-bottom:14px;}
.chat-header{display:flex;justify-content:space-between;font-size:13px;color:#999;margin-bottom:10px;}
.chat-content{white-space:pre-wrap;line-height:1.7;color:#444;font-size:14px;}
.audit-box{margin-top:26px;padding-top:24px;border-top:1px solid #eee;text-align:center;}
.audit-tip{color:#f56c6c;font-size:14px;margin-bottom:16px;}
.btn-audit{
    padding:13px 36px;border:none;border-radius:8px;background:linear-gradient(135deg,#67c23a,#42a850);
    color:#fff;font-size:16px;cursor:pointer;transition:0.3s;
}
.btn-audit:hover{opacity:0.92;transform:translateY(-2px);}
.btn-audit:disabled{opacity:0.6;cursor:not-allowed;transform:none;}
@media(max-width:600px){
    .card{padding:22px;}
    .info-row{flex-direction:column;gap:6;}
}
</style>
</head>
<body>
<div class="container">
    <a href="work_detail.php?id=<?=$workId?>" class="back">← 返回任务详情</a>
    <div class="card">
        <h1 class="title"><?=htmlspecialchars($work['title'])?></h1>
        <div class="tags">
            <span class="tag tag-wip">进行中（待审核）</span>
            <?php if($work['work_type'] == 'level1'):?>
                <span class="tag tag-level1">一级任务（系统发放工分）</span>
            <?php endif;?>
        </div>
        <div class="info-row">
            <div>发布人：<strong><?=htmlspecialchars($work['pub_name'])?></strong></div>
            <div>接单者：<strong><?=htmlspecialchars($work['worker_name'])?></strong></div>
            <div>奖励工分：<strong><?=$work['pay']?> 工分</strong></div>
        </div>

        <div class="chat-title">接单者提交的交付记录</div>
        <?php if(count($chatList) == 0):?>
            <p style="color:#999;text-align:center;padding:30px 0;">接单者暂未提交任何交付答复</p>
        <?php else:?>
            <?php foreach($chatList as $chat):?>
                <div class="chat-item">
                    <div class="chat-header">
                        <span>发送人：<?=htmlspecialchars($chat['send_name'])?></span>
                        <span><?=$chat['create_time']?></span>
                    </div>
                    <div class="chat-content"><?=htmlspecialchars($chat['message'])?></div>
                </div>
            <?php endforeach;?>
        <?php endif;?>

        <div class="audit-box">
            <p class="audit-tip">审核通过后，任务将标记为已完成，系统自动发放工分给接单者，不可撤销</p>
            <button class="btn-audit" onclick="auditPass(<?=$workId?>)">审核通过，发放工分</button>
        </div>
    </div>
</div>
<script>
function auditPass(wid){
    if(!confirm("确认审核通过？工分将自动发放给接单者，任务永久标记完成")) return;
    const btn = document.querySelector(".btn-audit");
    btn.disabled = true;
    btn.textContent = "处理中...";
    const fd = new FormData();
    fd.append("work_id", wid);
    fetch("",{
        method:"POST",
        body:fd
    })
    .then(res=>res.json())
    .then(json=>{
        alert(json.message);
        if(json.status == "success") window.location.href = "my_work.php?tab=publish";
        else {
            btn.disabled = false;
            btn.textContent = "审核通过，发放工分";
        }
    })
    .catch(()=>{
        alert("网络异常");
        btn.disabled = false;
        btn.textContent = "审核通过，发放工分";
    })
}
</script>
</body>
</html>