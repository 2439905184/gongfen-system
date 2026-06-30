<?php
include "/php/lib/Db.php";
if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    $work_id = $_POST["work_id"];
    $worker_id =  $_POST["worker_id"];
    $DB_API = new DB_API($config);
    $data = array(
        "work_id" => $work_id,
        "worker_id" => $worker_id,
        "status" => "WIP",
    );
    $where = array(
        "id" => $work_id,
    );
    $json = array(
        "status" => "",
        "message" => "",
    );
    $result = $DB_API->update("work_list", $where, $data);
    if ($result == true)
    {
        $json["status"] = "success";
        $json["message"] = "接单成功";
    }
    else
    {
        $json["status"] = "error";
        $json["message"] = $DB_API->errorMsg();
    }
}
?>