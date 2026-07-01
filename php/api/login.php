<?php
/**
 * 登录接口
 * 支持：邮箱 / 用户名 登录
 * 数据库操作：使用 DB_API 类（execQuery + 命名参数）
 */

// 开启 Session（必须放在最顶部）
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

// ==================== 接收参数 ====================
$account  = trim($_POST['username'] ?? '');  // 邮箱或用户名
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

// 参数校验
if (empty($account)) {
    echo json_encode(['status' => 'error', 'message' => '请输入账号']);
    exit;
}
if (empty($password)) {
    echo json_encode(['status' => 'error', 'message' => '请输入密码']);
    exit;
}

// ==================== 初始化数据库 ====================
$DB_API = new DB_API($config);
$table  = $config['db_prefix'] . 'user';  // 比如 gf_user

// ==================== 查询用户（OR 条件，命名参数） ====================
// ✅ 用命名参数 :email :username，避免位置参数从0开始的bug
$sql = "SELECT * FROM {$table} WHERE email = :email OR username = :username LIMIT 1";

// 第三个参数传 true → 返回查询结果
$result = $DB_API->execQuery($sql, [
    ':email'    => $account,
    ':username' => $account
], true);

// ==================== 验证密码 ====================
if ($result && is_array($result) && count($result) > 0) {
    $user = $result[0];
    
    if (password_verify($password, $user['password'])) {
        // 登录成功 → 存入 Session
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['nickname']  = !empty($user['nickname']) ? $user['nickname'] : $user['username'];
        $_SESSION['email']     = $user['email'];
        $_SESSION['score']     = $user['score'];
        $_SESSION['login_time']= time();

        // 记住我（延长 Session 有效期）
        if ($remember) {
            ini_set('session.gc_maxlifetime', 7 * 24 * 3600);  // 7天
        }

        echo json_encode([
            'status'  => 'success',
            'message' => '登录成功',
            'data' => [
                'redirect' => 'index.php',
                'nickname' => $_SESSION['nickname']
            ]
        ]);
        exit;
    }
}

// 登录失败（不透露是账号错还是密码错，安全）
echo json_encode([
    'status'  => 'error',
    'message' => '账号或密码错误'
]);
?>