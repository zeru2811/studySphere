<?php
session_start();
require "../../requires/connect.php";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = intval($_POST['id']);
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $note  = trim($_POST['note']);
    // var_dump("Hello");
    // exit();
    $stmt = $mysqli->prepare("UPDATE users SET name = ?, email = ?, phone = ?, note = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssssi", $name, $email, $phone, $note, $id);

    if ($stmt->execute()) {
        header("Location: ../user_details.php?id=$id&updated=1");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}

