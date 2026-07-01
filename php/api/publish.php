<?php
// 1. 开启 Session（必须放在最前面）
session_start();

include __DIR__ . '/../lib/Database.php';
include __DIR__ . '/../../config.php';
$DB = new DB_API($config);
$result = $DB->select($config['db_prefix'] . 'user', ["score"], ['id' => $_SESSION['user_id']]);
$balance = $result[0]['score'];
// 3. 检查是否登录
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status'  => 'error',
        'message' => '请先登录'
    ]);
    exit;
}

// 4. 接收参数（用 isset 判断，避免没传时报错）
$title          = trim($_POST['title'] ?? '');
$content        = trim($_POST['content'] ?? '');
$enableTimeLimit = isset($_POST['enableTimeLimit']) ? 1 : 0;  // 复选框：勾选=1，没勾选=0
$deadline       = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
$attachment     = trim($_POST['attachment'] ?? '');
$pay            = (int)($_POST['pay'] ?? 0);
$work_type      = $_POST["work_type"];

// 5. 参数校验
if (empty($title)) {
    echo json_encode(['status' => 'error', 'message' => '请输入任务标题']);
    exit;
}
if (empty($content)) {
    echo json_encode(['status' => 'error', 'message' => '请输入任务详情']);
    exit;
}
if ($pay <= 0) {
    echo json_encode(['status' => 'error', 'message' => '工分报酬必须大于0']);
    exit;
}
if ($work_type == "common")
{
    if ($balance < $pay) {
        echo json_encode(['status' => 'error', 'message' => '余额不足']);
        exit;
    }
}

if ($enableTimeLimit && empty($deadline)) {
    echo json_encode(['status' => 'error', 'message' => '请选择截止日期']);
    exit;
}

// 6. 初始化数据库
$DB_API = new DB_API($config);
$table  = $config['db_prefix'] . 'work_list';  // 表前缀 + 表名

// 7. 组装数据
$data = [
    'title'             => $title,
    'content'           => $content,
    'enable_time_limit' => $enableTimeLimit,  // 注意：数据库字段名是下划线命名
    'deadline'          => $deadline,
    'attachment'        => $attachment,
    'pay'               => $pay,
    'work_type'         => $work_type,
    'publisher_uid'     => $_SESSION['user_id'],
    'status'            => 'Open',  // 默认状态：待接单
];

// 8. 插入数据
$result = $DB_API->add($table, $data);

// 9. 返回结果（add 成功返回新记录的ID，失败返回 false）
if ($result !== false) {
    echo json_encode([
        'status'  => 'success',
        'message' => '发布成功',
        'data' => [
            'work_id' => $result,
            'redirect' => 'work_list.php'
        ]
    ]);
} else {
    echo json_encode([
        'status'  => 'error',
        'message' => '发布失败：' . $DB_API->errorMsg()
    ]);
}
?>