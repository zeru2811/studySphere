<?php
session_start();
require '../requires/connect.php';

if (!isset($_SESSION['id'])) {
    $_SESSION['feedback_error'] = "You need to be logged in to submit feedback.";
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback'])) {
    $feedback = trim($_POST['feedback']);
    $userId = $_SESSION['id'];
    
    if (empty($feedback)) {
        $_SESSION['feedback_error'] = "Feedback cannot be empty.";
        header("Location: ../pages/about_us.php");
        exit();
    }
    
    // Prepare and execute the SQL statement
    $stmt = $mysqli->prepare("INSERT INTO feedback (text, userId) VALUES (?, ?)");
    $stmt->bind_param("si", $feedback, $userId);
    
    if ($stmt->execute()) {
        $_SESSION['feedback_success'] = "Thank you for your feedback!";
    } else {
        $_SESSION['feedback_error'] = "There was an error submitting your feedback. Please try again.";
    }
    
    $stmt->close();
    header("Location: about_us.php");
    exit();
} else {
    header("Location: about_us.php");
    exit();
}
?>