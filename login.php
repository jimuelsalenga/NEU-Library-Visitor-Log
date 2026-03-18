<?php
include 'db.php';

$value=$_POST['value'];

$stmt=$conn->prepare("SELECT * FROM users WHERE student_id=? OR email=?");
$stmt->bind_param("ss",$value,$value);
$stmt->execute();
$res=$stmt->get_result();

if($res->num_rows>0){
 $u=$res->fetch_assoc();

 if($u['is_blocked']){
  echo json_encode(["status"=>"error","message"=>"Access Denied"]);
  exit;
 }

 echo json_encode([
   "status"=>"ok",
   "id"=>$u['id'],
   "name"=>$u['name'],
   "program"=>$u['program']
 ]);
}else{
 echo json_encode(["status"=>"error","message"=>"User not found"]);
}
?>