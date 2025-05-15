<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);

    // Validate input
    $errors = [];

    if (!$name || !$email || !$password || !$confirm_password || !$role) {
        $errors[] = "All fields are required";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Password validation
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }

    if (!in_array($role, ['student', 'instructor'])) {
        $errors[] = "Invalid role selected";
    }

    if (!empty($errors)) {
        $error_string = implode(", ", $errors);
        header("Location: login.php?error=" . urlencode($error_string));
        exit();
    }

    $conn = getDBConnection();
    if (!$conn) {
        header('Location: login.php?error=Database connection error');
        exit();
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$stmt) {
        header('Location: login.php?error=Database error');
        exit();
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        header('Location: login.php?error=Email already registered');
        exit();
    }
    $stmt->close();

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        header('Location: login.php?error=Database error');
        exit();
    }

    $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
    
    if ($stmt->execute()) {
        // Get the new user's ID
        $user_id = $stmt->insert_id;
        
        // Set session variables
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;

        // Redirect based on role
        if ($role === 'instructor') {
            header('Location: instructor_dashboard.php');
        } else {
            header('Location: student_dashboard.php');
        }
        exit();
    } else {
        header('Location: login.php?error=Registration failed');
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