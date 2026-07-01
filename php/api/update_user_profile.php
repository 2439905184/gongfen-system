<?php
session_start();
include(__DIR__ . "/../lib/Database.php");
include(__DIR__ . "/../../config.php");
$DB = new DB_API($config);
if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    if (isset($_POST["sign"]) && isset($_POST["contact"]))
    {
        $where = ["id"=>$_SESSION["user_id"]];
        $data = [
            "sign"=>$_POST["sign"], 
            "contact"=>$_POST["contact"]
        ];
        $result = $DB->update($config["db_prefix"] . "user", $where, $data);
        $json = [
            "status"  => "",
            "message" => ""
        ];
        if ($result)
        {
            $json["status"]  = "success";
            $json["message"] = "更新成功";
            echo json_encode($json);
        }
        else{
            $json["status"]  = "error";
            $json["message"] = $DB->errorMsg();
            echo json_encode($json);
        }
    }
}
?>