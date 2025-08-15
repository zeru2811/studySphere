<?php
session_start();
require_once '../../requires/connect.php';
require_once '../../requires/common_function.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$lessonId = $data['lesson_id'] ?? null;

if (!$lessonId || !is_numeric($lessonId)) {
    echo json_encode(['success' => false, 'message' => 'Invalid lesson ID']);
    exit;
}

try {
    // Check if lesson exists
    $checkStmt = $mysqli->prepare("SELECT id FROM lessons WHERE id = ?");
    $checkStmt->bind_param("i", $lessonId);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if ($checkStmt->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Lesson not found']);
        exit;
    }
    
    // Mark as complete in lesson_completions table
    $userId = $_SESSION['id'];
    $insertStmt = $mysqli->prepare("
        INSERT INTO lesson_completions (user_id, lesson_id) 
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE completed_at = CURRENT_TIMESTAMP
    ");
    $insertStmt->bind_param("ii", $userId, $lessonId);
    
    if ($insertStmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}