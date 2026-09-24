<?php
include 'db.php';

//جلب اخر رقم 
$result=$conn->query ("select id from student order by id desc limit 1");
$row = $result->fetch_assoc();

$next_id = $row ? $row['id'] + 1 : 1;
$student_code = "06." . str_pad($next_id,4,"0",str_pad_left);
$name = $_post [ 'full_name'];
$phone = $_post['phone'];
$course = $_post ['course'];

$sql = "insert into students (student_code,full_name,phone,course)
values('$student_code','$name','phone','$course',)";
if ($conn->query($sql)){
    echo "student added successfully:".
    $student_code;
}slse{
   echo "error";
}
?>
