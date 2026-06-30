<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>接单者接单记录</title>
</head>
<body>
    <ol>
        <?php
            include("/php/lib/Db.php");
            $db = new DB_API($config);
            $work = $db->select("work_list", "*", ["worker_uid"=>$_GET["worker_uid"]]);
            foreach ($work as $key => $value) 
            { 
                echo "<li>";
                echo "任务ID：".$value["work_id"];
            
                echo "任务标题：".$value["work_title"];
        
                echo "任务描述：".$value["work_desc"];
                
                echo "任务价格：".$value["work_price"];
            
                echo "任务状态：".$value["work_status"];
                echo "<button onclick=commitAnswer()>";
                echo "提交答案";
                echo "</button>";
                echo "<button onclick=commitAttachment()>";
                echo "提交附件";
                echo "</button>";
                echo "<button onclick=commitWork()>";
                echo "提交工作";
                echo "</button>";
                echo "</li>";
            }
        ?>
        <script>
            var work_id = document.getElementsByTagName("li")[0].getElementsByTagName("input")[0].value;
            function commitAnswer()
            {
                var answer = prompt("请输入回答");
            }
            function commitAttachment()
            {
                var attachment = prompt("请输入附件链接");
            }
            function commitWork(){
                const request = new XMLHttpRequest();
                request.open("POST", "/php/api/commitWork.php");
                var data = new FormData();
                data.append("work_id", work_id);
                data.append("answer", answer);
                data.append("attachment", attachment);
                request.send(data);
                request.onload = function()
                {
                    if (request.status == 200)
                    {
                        alert("提交成功");
                    }
                }
            }
        </script>
    </ol>
</body>
</html>