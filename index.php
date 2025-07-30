<?php
session_start();

// Check if user is logged in by verifying session id
if (!isset($_SESSION['id'])) {
    // Not logged in, redirect to login page
    header("Location: login.php");
    exit();
}
