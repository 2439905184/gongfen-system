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
$result = $DB->select($config["db_prefix"] . "user",["score"],["id"=>$_SESSION["user_id"]]);
$score = $result[0]["score"];

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    if (isset($_POST["logout"]) && $_POST["logout"] == "logout")
    {
        $_SESSION = [];
        session_destroy();
        echo json_encode(['code' => 200, 'msg' => '退出成功']);
        exit;
    }
    echo $_POST;
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
        /* 全局重置 */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Microsoft YaHei", system-ui, sans-serif;
}
body {
    background-color: #f4f6f9;
    display: flex;
    min-height: 100vh;
}

/* 左侧侧边栏 */
.left-menu {
    width: 220px;
    background: #1f2937;
    padding: 40px 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.left-menu a {
    display: block;
    color: #d1d5db;
    text-decoration: none;
    padding: 14px 30px;
    cursor: pointer;
    transition: 0.2s all ease;
    font-size: 15px;
}
.left-menu a:hover {
    background: #374151;
    color: #ffffff;
}
.left-menu a.active {
    background: #2563eb;
    color: #fff;
}

/* 右侧主内容区域 */
.right {
    flex: 1;
    padding: 40px;
}

/* 内容面板：默认隐藏，激活显示 */
.right > div {
    display: none;
    background: #fff;
    padding: 32px;
    border-radius: 14px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.06);
    max-width: 600px;
}
.right > div.show {
    display: block;
}

/* 欢迎标题 */
.user-title {
    font-size: 22px;
    color: #111827;
    margin-bottom: 16px;
}
.score-text {
    font-size: 18px;
    color: #ea580c;
    font-weight: bold;
}

/* 输入框样式 */
input[type="text"] {
    width: 100%;
    padding: 12px 16px;
    margin-bottom: 16px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    font-size: 15px;
    transition: 0.2s;
}
input[type="text"]:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

/* 退出按钮 */
#logout-btn {
    margin-top: 24px;
    padding: 13px 0;
    width: 100%;
    border: none;
    border-radius: 10px;
    background-color: #ef4444;
    color: white;
    font-size: 16px;
    cursor: pointer;
    transition: 0.2s;
}
#logout-btn:hover {
    background-color: #dc2626;
}
    .index-show, .profile-show, .profile-show
    {
        display: inline-flex;
        flex-direction: column;
    }
    </style>
</head>
<body>
    <div class="left-menu">
        <a href="#index">首页</a>
        <a href="my_work.php" class="nav-link">我的接单</a>
        <a href="#profile">编辑资料</a>
    </div>

    <div class="right">
        <div id="index">
            <div class="user-title">欢迎，<?php echo $_SESSION["username"]; echo "&nbsp"; echo $_SESSION["email"]?></div>
            我的工分：<span class="score-text"><?php echo $score; ?></span>
            <button id="logout-btn" onclick="logout()">退出登录</button>
        </div>

        <div id="profile">
            <input id="sign-input" type="text" placeholder="个性签名(128字以内)" maxlength="128" />
            <input id="contact-input" type="text" placeholder="联系方式(255字以内)" maxlength="256" value=""/>
        </div>
    </div>

    <script>
        const navLinks = document.querySelectorAll('.left-menu a');


        const container = docuement.getElementByClassName("card-box")[0];
        function changeView(value)
        {
            if (value == "index")
            {
                container.innerHTML = document.getElementById("index").innerHTML;
                //container.addClassName("index-show");
            }
            else if (value == "profile")
            {
                container.innerHTML = document.getElementById("profile").innerHTML;
            }
        }
        // var contactEntered = false
        // const contactInput = document.getElementById("contact-input");
        // // 失去焦点
        // contactInput.onblur = function()
        // {
        //     updateUserContact(contactInput.value);
        // }
        // contactInput.onfocus = function()
        // {
        //     contactEntered = true
        // }
        // contactInput.onmouseleave = function()
        // {
        //     updateUserContact(contactInput.value);
        // }
        // contactInput.onkeyup = function(event)
        // {
        //     if (event.keyCode == 13)
        //     {
        //         updateUserContact(contactInput.value);
        //     }
        // }
        function updateUserContact(value)
        {
            const request = new XMLHttpRequest();
            request.open("POST", "");
            request.send(value);
        }
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