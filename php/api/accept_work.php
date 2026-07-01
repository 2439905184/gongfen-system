<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// 引入数据库类、配置
include __DIR__ . '/../lib/Database.php';
include __DIR__ . '/../../config.php';

// 未登录拦截
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => '请先登录后再接单'
    ]);
    exit;
}

$loginUid = $_SESSION['user_id'];
$workId = isset($_POST['work_id']) ? (int)$_POST['work_id'] : 0;

if ($workId <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => '任务ID非法'
    ]);
    exit;
}

$DB_API = new DB_API($config);
$table = $config['db_prefix'] . 'work_list';

// 1. 查询任务信息：是否存在、状态、发布人
$sql = "SELECT id, status, publisher_uid FROM {$table} WHERE id = :wid LIMIT 1";
$res = $DB_API->execQuery($sql, [':wid' => $workId], true);

if (!$res || count($res) === 0) {
    echo json_encode([
        'status' => 'error',
        'message' => '该任务不存在'
    ]);
    exit;
}
$work = $res[0];

// 校验任务状态必须是待接单 Open
if ($work['status'] !== 'Open') {
    echo json_encode([
        'status' => 'error',
        'message' => '任务已被接走或已完成，无法接单'
    ]);
    exit;
}

// 不能接自己发布的任务
if ($work['publisher_uid'] == $loginUid) {
    echo json_encode([
        'status' => 'error',
        'message' => '不能接自己发布的任务'
    ]);
    exit;
}

// 2. 更新任务：状态改为进行中WIP，接单者为当前用户
$updateData = [
    'status' => 'WIP',
    'worker_uid' => $loginUid
];
$where = ['id' => $workId];
$updateResult = $DB_API->update($table, $where, $updateData);

if ($updateResult) {
    echo json_encode([
        'status' => 'success',
        'message' => '接单成功！你可以在我的任务中查看',
        'redirect' => 'my_work.php'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => '接单失败：' . $DB_API->errorMsg()
    ]);
}
exit;
?>