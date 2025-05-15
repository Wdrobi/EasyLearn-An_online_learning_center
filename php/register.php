<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Learning Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="auth-bg">
    <div class="container">
        <div class="auth-container">
            <div class="register-form card auth-card">
                <div class="auth-logo">
                    <img src="../images/EasyLearn.png" alt="EasyLearn Logo" style="height:64px;margin-bottom:10px;">
                </div>
                <h2 class="text-center mb-2">Register</h2>
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>
                <form action="register_process.php" method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-input-group">
                            <input type="password" id="password" name="password" class="form-control" required>
                            <button type="button" id="password-toggle" class="password-toggle">👁️</button>
                        </div>
                        <div class="password-strength-container">
                            <div class="password-strength"></div>
                        </div>
                        <div class="password-requirements">
                            <p>Password must contain:</p>
                            <ul>
                                <li id="length">At least 8 characters</li>
                                <li id="uppercase">One uppercase letter</li>
                                <li id="lowercase">One lowercase letter</li>
                                <li id="number">One number</li>
                                <li id="special">One special character</li>
                            </ul>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm-password">Confirm Password</label>
                        <div class="password-input-group">
                            <input type="password" id="confirm-password" name="confirm_password" class="form-control" required>
                            <button type="button" id="confirm-password-toggle" class="password-toggle">👁️</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="role">I am a:</label>
                        <select id="role" name="role" class="form-control" required>
                            <option value="student">Student</option>
                            <option value="instructor">Instructor</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Register</button>
                </form>
                <p class="text-center mt-2">
                    Already have an account? 
                    <a href="login.php" class="btn-link">Login</a>
                </p>
            </div>
        </div>
    </div>

    <script src="../js/main.js"></script>
</body>
</html> 