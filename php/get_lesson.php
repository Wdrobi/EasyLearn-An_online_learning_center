<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in and is an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

$lesson_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$lesson_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid lesson ID']);
    exit();
}

// Get lesson data and verify ownership
$stmt = $conn->prepare("
    SELECT l.* 
    FROM lessons l 
    JOIN courses c ON l.course_id = c.id 
    WHERE l.id = ? AND c.instructor_id = ?
");
$stmt->bind_param("ii", $lesson_id, $_SESSION['user_id']);
$stmt->execute();
$lesson = $stmt->get_result()->fetch_assoc();

if (!$lesson) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Lesson not found or unauthorized access']);
    exit();
}

// Return lesson data
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'id' => $lesson['id'],
    'title' => $lesson['title'],
    'content_type' => $lesson['content_type'],
    'content' => $lesson['content'],
    'lesson_order' => $lesson['lesson_order']
]);

$conn->close();
?> 