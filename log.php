<?php
include 'db.php';

$stmt=$conn->prepare("INSERT INTO visitor_log(user_id,reason) VALUES(?,?)");
$stmt->bind_param("is",$_POST['user_id'],$_POST['reason']);
$stmt->execute();
?>