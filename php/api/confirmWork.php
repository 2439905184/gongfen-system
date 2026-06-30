<?php
include("/php/lib/Db.php");
$DB_API = new DB_API($config);
$json = array(
    "status" => "",
    "message" => ""
);
$data = array(
    "status" => "finish",
    "worker_uid" => $_POST["worker_uid"],
    "worker_answer" => $_POST["answer"],
    "worker_attachment" => $_POST["attachment"]
);
$where = array(
    "id" => $_POST["work_id"]
);
$result = $DB_API->update("work_list", $where, $data);
if ($result)
{
    if ($work_type == "level1")
    {
        $r = $DB_API->update("user", array("id" => $_POST["worker_uid"]), array("score" => $POST["pay"]));
        if ($r)
        {
            $json["status"] = "success";
            $json["message"] = "确认成功，发放工分";
        }
        else
        {
            $json["status"] = "error";
            $json["message"] = "确认失败，原因：" . $DB_API->errorMsg();
        }
    }
    elseif($work_type == "common")
    {
        // 从发布者的账户扣除工分
    }
}
?>