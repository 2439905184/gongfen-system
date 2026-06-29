<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>工作大厅</title>
</head>
<body>
    <h1>一级工作</h1>
    <div class="container">
        <ol>
        <?php
        include "/php/lib/Db.php";
        $DB_API = new DB_API($config);
        $where = array(
            "status" => "Active",
            "work_type" => "level1"
        );
        $selectSQL = $DB_API->select("work_list",array("*"),$where);
        if ($selectSQL) {
            // echo json_encode($selectSQL, JSON_UNESCAPED_UNICODE);
            foreach ($selectSQL as $key => $value) {
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
        } else {
            // echo '查询失败，原因：' . $db->errorMsg();
            echo '查询失败，原因：' . $DB_API->errorMsg();
        }
    ?>
        </ol>
    </div>
    <h1>工分交易工作</h1>
    <div class="container">
        <ol>
        <?php
        include "/php/lib/Db.php";
        $DB_API = new DB_API($config);
        $where = array(
            "status" => "Active",
            "work_type" => "common"
        );
        $selectSQL = $DB_API->select("work_list",array("*"),$where);
        if ($selectSQL) {
            // echo json_encode($selectSQL, JSON_UNESCAPED_UNICODE);
            foreach ($selectSQL as $key => $value) {
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
        } else {
            // echo '查询失败，原因：' . $db->errorMsg();
            echo '查询失败，原因：' . $DB_API->errorMsg();
        }
    ?>
        </ol>
    </div>
</body>
</html>