<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我发布的工作</title>
    <script></script>
</head>
<body>
    <ol>
        <?php
        include "/php/lib/Db.php";
        $DB_API = new DB_API($config);
        $where = array(
            "publisher_uid" => $_SESSION["uid"]
        );
        $selectSQL = $DB_API->select("jiaoyi",array("*"),$where);
        for ($selectSQL as $key => $value) {
            echo '<li><a href="workDetail.php?id=' . $value["id"] . 
            $value["title"] .
             $value["content"] .
             $value["enableTimeLimit"] . 
             $value["deadline"] .
              $value["attachment"] . 
              $value["pay"] .
               $value["work_type"] . 
               $value["pay"] . 
               $value["publisher_uid"] .
                '">' .$value["title"] .  '</a></li>';
        }
        ?>
    </ol>
    <script>
        function showAnser()
        {
            var d = dialog({
                "title": "查看回答",
                "content": "<textarea>${answer}</textarea>",
            })
            d.show()
        }
        function showAttachment()
        {
            var d = dialog({
                "title": "查看附件",
                "content": "<a href='${attachment}'>点击下载</a>"
            })
            d.show()
        }
        // 确认通过 发放工分
        function confirm_ok()
        {
            const request = new XMLHttpRequest();
            request.open("POST", "/php/api/confirm_ok.php", true);
            var data = new FormData();
            data.append("work_id", "<?php echo $_GET["id"]?>")
            data.append("worker_uid", "<?php echo $_SESSION["uid"]?>")
            data.append("status", "finish");
            request.send(data)
            request.onload = function()
            {
                if (request.status == 200)
                {
                    alert("确认成功")
                }
                else
                {
                    alert("确认失败，原因：" + request.responseText)
                }
            }
            
        }
    </script>
</body>
</html>