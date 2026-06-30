<?php
/**
 * 登录接口（优化版）
 * 支持：邮箱 / 用户名 登录
 * 数据库操作：使用 DB_API 类（execQuery + 原生SQL）
 */

session_start();
include __DIR__ . '../../config.php';
include __DIR__ . '../lib/Dabase.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => '请求方式错误']);
    exit;
}

// ==================== 接收参数 ====================
$account  = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

if (empty($account) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => '账号和密码不能为空']);
    exit;
}

// ==================== 查询用户（OR 条件，一次查完） ====================
$DB_API = new DB_API($config);
$table  = $config['db_prefix'] . 'user';

// 原生 SQL：邮箱或用户名都能匹配
$sql = "SELECT * FROM {$table} WHERE email = ? OR username = ? LIMIT 1";

// 第三个参数传 true → 返回查询结果
$result = $DB_API->execQuery($sql, [$account, $account], true);

// ==================== 验证密码 ====================
if ($result && is_array($result) && count($result) > 0) {
    $user = $result[0];
    
    if (password_verify($password, $user['password'])) {
        // 登录成功
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['nickname']  = !empty($user['nickname']) ? $user['nickname'] : $user['username'];
        $_SESSION['email']     = $user['email'];
        $_SESSION['score']     = $user['score'];
        $_SESSION['login_time']= time();

        if ($remember) {
            ini_set('session.gc_maxlifetime', 7 * 24 * 3600);
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

// 登录失败
echo json_encode([
    'status'  => 'error',
    'message' => '账号或密码错误'
]);
?>