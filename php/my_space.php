<?php
include(__DIR__ . "/lib/Database.php");
include(__DIR__ . "/../config.php");
session_start();

if (!isset($_SESSION["user_id"]) || !isset($_SESSION["username"]))
{
    header("Location: ../login_register.html");
    exit;
}
$DB = new DB_API($config);
$s = $DB->select($config["db_prefix"] . "user",["score"],["id"=>$_SESSION["user_id"]]);
var_dump($s);
#$score = $_SESSION['score'] ?? 0;

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    if (isset($_POST["logout"]) && $_POST["logout"] == "logout")
    {
        $_SESSION = [];
        session_destroy();
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
    <title><?php echo $_SESSION["username"]; ?> - 个人中心</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Microsoft YaHei", sans-serif;
        }
        body {
            background-color: #f5f7fa;
            padding: 40px 20px;
        }
        /* 外层卡片容器 */
        .card-box {
            width: 100%;
            max-width: 520px;
            margin: 0 auto;
            background: #ffffff;
            padding: 36px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        /* 标题用户名 */
        .user-title {
            font-size: 22px;
            color: #2d3748;
            margin-bottom: 30px;
            padding-bottom: 16px;
            border-bottom: 1px solid #eee;
        }
        /* 统一输入框样式 */
        input[type="text"] {
            width: 100%;
            padding: 12px 16px;
            margin-bottom: 16px;
            border: 1px solid #dde2e9;
            border-radius: 10px;
            font-size: 15px;
            transition: 0.2s all;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: #4096ff;
            box-shadow: 0 0 0 3px rgba(64, 150, 255, 0.15);
        }
        /* 导航链接 */
        .nav-link {
            display: block;
            text-decoration: none;
            color: #4a5568;
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 10px;
            font-size: 15px;
            transition: 0.2s background;
        }
        .nav-link:hover {
            background-color: #f0f7ff;
            color: #2b7cd3;
        }
        /* 工分高亮 */
        .score-text {
            font-weight: bold;
            color: #e67e22;
        }
        /* 退出按钮 */
        #logout-btn {
            width: 100%;
            margin-top: 20px;
            padding: 13px;
            border: none;
            border-radius: 10px;
            background-color: #ef4444;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: 0.2s background;
        }
        #logout-btn:hover {
            background-color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="card-box">
        <div class="user-title">欢迎，<?php echo $_SESSION["username"]; ?></div>

        <input type="text" placeholder="个性签名(128字以内)" maxlength="128"/>
        <input type="text" placeholder="联系方式(256字以内)" maxlength="256"/>

        <a href="" class="nav-link">我的收藏</a>
        <a href="" class="nav-link">历史浏览</a>
        <a href="" class="nav-link">我的关注</a>
        <a href="workerJiedan.php" class="nav-link">我的接单</a>
        <a href="" class="nav-link">我的工分：<span class="score-text"><?php echo $score; ?></span></a>

        <button id="logout-btn" onclick="logout()">退出登录</button>
    </div>

    <script>
        function logout()
        {
            const request = new XMLHttpRequest();
            request.open("POST", "");
            const data = new FormData();
            data.append("logout", "logout");
            request.send(data);
            request.onload = function()
            {
                if (request.status == 200)
                {
                    alert("退出成功！");
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