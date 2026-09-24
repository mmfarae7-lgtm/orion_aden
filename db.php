<?php
$host= "localhost";
$usr="root";
$pass = "";
$db = "institute_system";

$conn = new mysqli($host,$user,$pass,$db);

if($conn->connect_error) {
    die("connection failed:".
    $conn->connect_error);
}
?>