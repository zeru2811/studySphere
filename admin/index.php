<?php
session_start();

// Check if user is logged in by verifying session id
if (!isset($_SESSION['id']) || $_SESSION['id'] == 1 || $_SESSION['id'] == 2) {
    header("Location: ../login.php");
    exit();
}

echo "Hello, authorized user!";