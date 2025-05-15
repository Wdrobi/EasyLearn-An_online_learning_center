<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['course_id'])) {
    header('Location: student_dashboard.php?msg=Invalid+course');
    exit();
}

$student_id = $_SESSION['user_id'];
$course_id = intval($_GET['course_id']);
$conn = getDBConnection();
if (!$conn) {
    header('Location: student_dashboard.php?msg=Database+connection+failed');
    exit();
}
// Check if already enrolled
$stmt = $conn->prepare('SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?');
$stmt->bind_param('ii', $student_id, $course_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    $conn->close();
    header('Location: course.php?id=' . $course_id . '&msg=Already+enrolled');
    exit();
}
$stmt->close();
// Enroll student
$stmt = $conn->prepare('INSERT INTO enrollments (student_id, course_id, enrolled_at, progress, completed) VALUES (?, ?, NOW(), 0, 0)');
$stmt->bind_param('ii', $student_id, $course_id);
if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header('Location: course.php?id=' . $course_id . '&msg=Enrolled+successfully');
    exit();
} else {
    $stmt->close();
    $conn->close();
    header('Location: student_dashboard.php?msg=Enrollment+failed');
    exit();
} 