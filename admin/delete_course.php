<?php
require "../requires/connect.php";

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $courseId = (int)$_GET['id'];

    $stmt = $mysqli->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->bind_param("i", $courseId);
    $stmt->execute();

    session_start();
    $_SESSION['message'] = "Course deleted successfully.";
}

header("Location: courses.php");
exit;