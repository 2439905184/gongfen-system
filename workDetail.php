<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $_POST["title"] ?></title>
</head>
<body>
    <h1><?php echo $_POST["title"] ?></h1>
    <p>发布者<?php 
        include "/php/lib/Db.php";
        $DB_API = new DB_API($config);
        $selectSQL = $DB_API->select("user",array("username"),array("id" => $_POST["publisher_uid"]));
        if ($selectSQL) {
            echo $selectSQL["username"];
        } else {
            echo '查询失败，原因：' . $DB_API->errorMsg();
        }
    ?></p>
    <textarea name="content" id="content" cols="100%" rows="100%">
        <?php echo $_POST["content"] ?>
    </textarea>
    <p>工期限制<?php echo $_POST["enableTimeLimit"]?></p>
    <?php if ($_POST["enableTimeLimit"] == "true") 
    {
        echo "<p>截止时间" . $_POST["deadline"] . "</p>";
    }
    ?>
    <?php if ($_POST["attachment"])
    {
        echo "<a href= $_POST[attachment]>" . "附件" . "</a>";
    }
    ?>
    <p>工分<?php echo $_POST["pay"]?></p>
    <textarea>描述你具体完成了哪些工作或者解答发布者的内容。</textarea>
    <input type="url" placeholder="请输入工作附件链接（如果有的话）">
    <button>提交工作</button>
</body>
</html>