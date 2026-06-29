<?php
include "/php/lib/Db.php";

$title = $_POST["title"];
$content = $_POST["content"];
$enableTimeLimit = $_POST["enableTimeLimit"];
$deadline = $_POST["deadline"];
$pay = $_POST["pay"];

$DB_API = new DB_API($config);
$data = array(
    "title" => $title,
    "content" => $content,
    "enableTimeLimit" => $enableTimeLimit,
    "deadline" => $deadline,
    "pay" => $pay
);
$result = $DB_API->add("work1",$data);
$echo_array = array(
    "status" => "",
    "message" => ""
);
if (is_string($result))
{
    $echo_array["status"] = "success";
    $echo_array["message"] = "发布成功";
}
elseif ($result == false)
{
    $echo_array["status"] = "error";
    $echo_array["message"] = $DB_API->error;
}
echo json_encode($echo_array);
?>