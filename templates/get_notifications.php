<?php
require_once '../requires/connect.php';
require_once '../requires/common_function.php';

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$userId = $_SESSION['id'];

// Get unread notifications count
$countQuery = $mysqli->prepare("SELECT COUNT(*) as count FROM comment_notifications WHERE user_id = ? AND is_read = FALSE");
$countQuery->bind_param("i", $userId);
$countQuery->execute();
$unreadCount = $countQuery->get_result()->fetch_assoc()['count'];

// Get notifications (limit to 10 most recent)
$query = $mysqli->prepare("
    SELECT cn.id, cn.comment_id, cn.is_read, cn.created_at,
           c.comment, u.name as commenter_name, l.title as lesson_title
    FROM comment_notifications cn
    JOIN comment c ON cn.comment_id = c.id
    JOIN users u ON c.userId = u.id
    JOIN lessons l ON c.lessonId = l.id
    WHERE cn.user_id = ?
    ORDER BY cn.created_at DESC
    LIMIT 10
");
$query->bind_param("i", $userId);
$query->execute();
$notifications = $query->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'unreadCount' => $unreadCount
]);
?>