<?php
// 修正文件名拼写 Database.php
include(__DIR__ . "/lib/Dabase.php");
include(__DIR__ . "/../config.php");
session_start();

// 登录校验：缺少任意一个会话都拦截
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["username"]))
{
    header("Location: ../login_register.html");
    exit;
}
$DB = new DB_API($config);

// 读取用户工分（示例，根据你数据库自行修改查询语句）
$score = $_SESSION['score'] ?? 0;

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    if (isset($_POST["logout"]) && $_POST["logout"] == "logout")
    {
        // 清空当前会话变量 + 销毁会话
        $_SESSION = [];
        session_destroy();
        // AJAX 请求不能靠 header 跳转，返回成功标识给前端
        echo json_encode(['code' => 200, 'msg' => '退出成功']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $_SESSION["username"]; ?></title>
    <style>
        #c{
            display: flex;
            flex-direction: column;
            width: 50%;
            gap: 8px;
            margin: 20px;
        }
    </style>
</head>
<body>
    <div id="c">
        <input type="text" placeholder="个性签名">
        <input type="text" placeholder="联系方式">
        <a href="">我的收藏</a>
        <a href="">历史浏览</a>
        <a href="">我的关注</a>
        <a href="workerJiedan.php">我的接单</a>
        <!-- PHP 变量输出工分，不要写 ${score} -->
        <a href="">我的工分：<?php echo $score; ?></a>
        <button onclick="logout()">退出登录</button>
    </div>
    <script>
        function logout()
        {
            const request = new XMLHttpRequest();
            // 请求当前页面（空字符串代表本页）没问题
            request.open("POST", "");
            var data = new FormData();
            data.append("logout", "logout");
            request.send(data);
            request.onload = function()
            {
                if (request.status == 200)
                {
                    alert("退出成功！");
                    // 关键：AJAX 请求成功后跳转到登录页
                    location.href = "../login_register.html";
                }
                else{
                    alert("网络错误！请稍后再试！")
                }
            }
        }
    </script>
</body>
</html>