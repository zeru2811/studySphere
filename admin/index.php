<?php
session_start();

// Check if user is logged in by verifying session id
if (!isset($_SESSION['id']) || $_SESSION['id'] == 1 || $_SESSION['id'] == 2) {
    header("Location: ../login.php");
    exit();
}elseif($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2) {
    header("Location: ../frontend/index.php");
    exit();
}else {
    header("Location: ./user_management.php");
    exit();
}