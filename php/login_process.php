<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (!$email || !$password) {
        header('Location: login.php?error=Please fill in all fields');
        exit();
    }

    $conn = getDBConnection();
    if (!$conn) {
        header('Location: login.php?error=Database connection error');
        exit();
    }

    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
    if (!$stmt) {
        header('Location: login.php?error=Database error');
        exit();
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'instructor') {
                header('Location: instructor_dashboard.php');
            } else {
                header('Location: student_dashboard.php');
            }
            exit();
        } else {
            header('Location: login.php?error=Invalid email or password');
            exit();
        }
    } else {
        header('Location: login.php?error=Invalid email or password');
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    // If not POST request, redirect to login page
    header('Location: login.php');
    exit();
}
?> 