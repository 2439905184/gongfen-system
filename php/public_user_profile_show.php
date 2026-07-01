<!-- 用于向其他用户显示用户信息 -->
<?php
session_start();
$hasId = false;
include(__DIR__ . "/lib/Database.php");
include(__DIR__ . "/../config.php");
if ($_SERVER["REQUEST_METHOD"] == "GET")
{
    if (isset($_GET["user_id"]))
    {
        $hasId = true;
        $DB = new DB_API($config);
        $result = $DB->select($config["db_prefix"] . "user",["username","email","sign","contact"],["id"=>$_GET["user_id"]]);
        $username = $result[0]["username"];
        $email = $result[0]["email"];
        $sign = $result[0]["sign"];
        $contact = $result[0]["contact"];
    }
    else{
        $hasId = false;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $_SESSION["username"]; ?> - 用户资料</title>
    <style>
        .container
        {
            display: flex;
            flex-direction: column;
        }
    </style>
</head>
<body>
    <!-- 居中 -->
    <div class="cotainer">
        <?php if ($hasId):?>
            <p>用户名：<?php echo $username; ?></p>
            <p>邮箱：<?php echo $email; ?></p>
            <p>个性签名：<?php echo $sign; ?></p>
            <p>联系方式：<?php echo $contact; ?></p>
        <?php else:?>
            <p>用户不存在</p>
        <?php endif;?>
    </div>
</body>
</html>
