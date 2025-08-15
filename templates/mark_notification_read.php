<?php
require_once '../requires/connect.php';
require_once '../requires/common_function.php';

session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

if (!isset($_POST['notification_id'])) {
    echo json_encode(['success' => false, 'message' => 'Notification ID required']);
    exit();
}

$notificationId = (int)$_POST['notification_id'];
$userId = $_SESSION['id'];

// Verify the notification belongs to the user
$verify = $mysqli->prepare("SELECT id FROM comment_notifications WHERE id = ? AND user_id = ?");
$verify->bind_param("ii", $notificationId, $userId);
$verify->execute();

if ($verify->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Notification not found']);
    exit();
}

// Mark as read
$stmt = $mysqli->prepare("UPDATE comment_notifications SET is_read = TRUE WHERE id = ?");
$stmt->bind_param("i", $notificationId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark as read']);
}
?>