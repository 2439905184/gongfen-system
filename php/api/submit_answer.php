<?php
session_start();
header("Content-Type:application/json;charset=utf-8");
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status"=>"error","message"=>"请先登录"]);
    exit;
}
$loginUid = $_SESSION['user_id'];
$workId = isset($_POST['work_id']) ? (int)$_POST['work_id'] : 0;
$answerContent = trim($_POST['answer_content'] ?? "");
$answerFile = trim($_POST['answer_file'] ?? "");
if ($workId <= 0 || empty($answerContent)) {
    echo json_encode(["status"=>"error","message"=>"请填写完整交付说明"]);
    exit;
}
include __DIR__ . '/../lib/Database.php';
include __DIR__ . '/../../config.php';
$DB_API = new DB_API($config);
$prefix = $config['db_prefix'];
$tableWork = "{$prefix}work_list";
$tableChat = "{$prefix}work_chat";

// 二次校验权限：必须是该任务接单者且任务进行中
$sqlCheck = "SELECT publisher_uid FROM {$tableWork} WHERE id=:wid AND status='WIP' AND worker_uid=:uid LIMIT 1";
$checkRes = $DB_API->execQuery($sqlCheck,[":wid"=>$workId,":uid"=>$loginUid],true);
if (!$checkRes || count($checkRes) === 0) {
    echo json_encode(["status"=>"error","message"=>"无权提交交付内容"]);
    exit;
}
$pubUid = $checkRes[0]['publisher_uid'];

$msgText = "【任务交付答复】\n".$answerContent;
if (!empty($answerFile)) $msgText .= "\n附件：".$answerFile;

$insertData = [
    "work_id" => $workId,
    "sender_uid" => $loginUid,
    "receiver_uid" => $pubUid,
    "message" => $msgText,
    "is_read" => 0
];
$res = $DB_API->add($tableChat,$insertData);
if ($res !== false) {
    echo json_encode([
        "status"=>"success",
        "message"=>"交付答复提交成功，发布人可查看"
    ]);
}else{
    echo json_encode([
        "status"=>"error",
        "message"=>"提交失败：".$DB_API->errorMsg()
    ]);
}
exit;
?>