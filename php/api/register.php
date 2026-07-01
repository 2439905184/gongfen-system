<?php
/**
 * 注册接口
 * 功能：用户注册，支持用户名 + 邮箱
 * 数据库操作：使用 DB_API 类
 */

// 开启 Session
session_start();

// 引入配置和数据库类
include __DIR__ . '/../../config.php';
include __DIR__ . '/../lib/Database.php';

// 返回 JSON
header('Content-Type: application/json; charset=utf-8');

// 只接受 POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => '请求方式错误']);
    exit;
}

// ==================== 1. 接收参数 ====================
$username        = trim($_POST['username'] ?? '');
$email           = trim($_POST['email'] ?? '');
$password        = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$nickname        = trim($_POST['nickname'] ?? '');  // 可选，没填就用 username

// ==================== 2. 参数校验 ====================

// 用户名
if (empty($username)) {
    echo json_encode(['status' => 'error', 'message' => '请输入用户名']);
    exit;
}
if (strlen($username) < 3 || strlen($username) > 20) {
    echo json_encode(['status' => 'error', 'message' => '用户名长度需在 3-20 位之间']);
    exit;
}
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    echo json_encode(['status' => 'error', 'message' => '用户名只能包含字母、数字和下划线']);
    exit;
}

// 邮箱
if (empty($email)) {
    echo json_encode(['status' => 'error', 'message' => '请输入邮箱']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => '邮箱格式不正确']);
    exit;
}

// 密码
if (empty($password)) {
    echo json_encode(['status' => 'error', 'message' => '请输入密码']);
    exit;
}
if (strlen($password) < 6) {
    echo json_encode(['status' => 'error', 'message' => '密码至少 6 位']);
    exit;
}
if ($password !== $confirmPassword) {
    echo json_encode(['status' => 'error', 'message' => '两次输入的密码不一致']);
    exit;
}

// ==================== 3. 初始化数据库 ====================
$DB_API = new DB_API($config);
$table  = $config['db_prefix'] . 'user';

// ==================== 4. 检查用户名是否已存在 ====================
$checkUser = $DB_API->select($table, 'id', ['username' => $username], null, null, 1);
if ($checkUser && count($checkUser) > 0) {
    echo json_encode(['status' => 'error', 'message' => '该用户名已被注册']);
    exit;
}

// ==================== 5. 检查邮箱是否已存在 ====================
$checkEmail = $DB_API->select($table, 'id', ['email' => $email], null, null, 1);
if ($checkEmail && count($checkEmail) > 0) {
    echo json_encode(['status' => 'error', 'message' => '该邮箱已被注册']);
    exit;
}

// ==================== 6. 密码加密 ====================
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// ==================== 7. 组装数据并插入 ====================
$userData = [
    'username' => $username,
    'email'    => $email,
    'password' => $hashedPassword,
    'nickname' => !empty($nickname) ? $nickname : $username,  // 没填昵称就用用户名
    'score'    => 0,  // 初始工分 0
];

$userId = $DB_API->add($table, $userData);

// ==================== 8. 判断结果 ====================
if ($userId) {
    // 注册成功 → 自动登录（可选，也可以跳转到登录页）
    $_SESSION['user_id']   = $userId;
    $_SESSION['username']  = $username;
    $_SESSION['nickname']  = $userData['nickname'];
    $_SESSION['email']     = $email;
    $_SESSION['score']     = 0;
    $_SESSION['login_time']= time();

    echo json_encode([
        'status'  => 'success',
        'message' => '注册成功',
        'data' => [
            'redirect' => 'index.php',
            'user_id'  => $userId,
            'nickname' => $userData['nickname']
        ]
    ]);

} else {
    // 注册失败
    echo json_encode([
        'status'  => 'error',
        'message' => '注册失败：' . $DB_API->errorMsg()
    ]);
}
?>