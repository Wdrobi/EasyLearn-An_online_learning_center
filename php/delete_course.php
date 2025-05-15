<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $course_id = intval($_POST['course_id']);
    $instructor_id = $_SESSION['user_id'];
    $conn = getDBConnection();
    if (!$conn) {
        header('Location: instructor_dashboard.php?msg=Database+connection+failed');
        exit();
    }
    // Check ownership
    $stmt = $conn->prepare('SELECT id FROM courses WHERE id = ? AND instructor_id = ?');
    $stmt->bind_param('ii', $course_id, $instructor_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $stmt->close();
        $conn->close();
        header('Location: instructor_dashboard.php?msg=Unauthorized');
        exit();
    }
    $stmt->close();
    // Delete related data
    $conn->query('DELETE FROM submissions WHERE assignment_id IN (SELECT id FROM assignments WHERE course_id = ' . $course_id . ')');
    $conn->query('DELETE FROM assignments WHERE course_id = ' . $course_id);
    $conn->query('DELETE FROM lesson_progress WHERE lesson_id IN (SELECT id FROM lessons WHERE course_id = ' . $course_id . ')');
    $conn->query('DELETE FROM lessons WHERE course_id = ' . $course_id);
    $conn->query('DELETE FROM quiz_results WHERE quiz_id IN (SELECT id FROM quizzes WHERE course_id = ' . $course_id . ')');
    $conn->query('DELETE FROM quiz_questions WHERE quiz_id IN (SELECT id FROM quizzes WHERE course_id = ' . $course_id . ')');
    $conn->query('DELETE FROM quizzes WHERE course_id = ' . $course_id);
    $conn->query('DELETE FROM enrollments WHERE course_id = ' . $course_id);
    $conn->query('DELETE FROM certificates WHERE course_id = ' . $course_id);
    $conn->query('DELETE FROM reviews WHERE course_id = ' . $course_id);
    // Finally, delete the course
    $conn->query('DELETE FROM courses WHERE id = ' . $course_id);
    $conn->close();
    header('Location: instructor_dashboard.php?msg=Course+deleted+successfully');
    exit();
} else {
    header('Location: instructor_dashboard.php?msg=Invalid+request');
    exit();
} 