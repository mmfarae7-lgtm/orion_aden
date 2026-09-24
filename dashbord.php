<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

include "db.php";

$students = $conn->query("SELECT COUNT(*) as total FROM students")->fetch_assoc();
?>

<h1>لوحة التحكم</h1>

<p>مرحباً: <?php echo $_SESSION['user']; ?></p>

<div>
    <h3>عدد الطلاب: <?php echo $students['total']; ?></h3>
</div>

<a href="logout.php">تسجيل خروج</a>