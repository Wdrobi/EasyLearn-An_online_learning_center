<?php
session_start();
require_once __DIR__ . '/../config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    header('Location: login.php'); exit();
}
if (!isset($_GET['course_id'])) { header('Location: instructor_dashboard.php'); exit(); }
$course_id = intval($_GET['course_id']);
$conn = getDBConnection();
// Check ownership
$stmt = $conn->prepare('SELECT * FROM courses WHERE id = ? AND instructor_id = ?');
$stmt->bind_param('ii', $course_id, $_SESSION['user_id']);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
if (!$course) { header('Location: instructor_dashboard.php?msg=Unauthorized'); exit(); }
$stmt->close();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $due_date = $_POST['due_date'] ?? '';
    if ($title === '' || $due_date === '') {
        $error = 'Title and due date are required.';
    } else {
        $stmt = $conn->prepare('INSERT INTO assignments (course_id, title, description, due_date) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('isss', $course_id, $title, $description, $due_date);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header('Location: manage_assignments.php?course_id=' . $course_id);
            exit();
        } else {
            $error = 'Failed to add assignment.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Assignment</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<nav class="navbar">
    <div class="container nav-content">
        <a href="../index.php" class="logo"><img src="../images/EasyLearn.png" alt="EasyLearn Logo" style="height:48px;vertical-align:middle;margin-right:10px;"></a>
        <div class="nav-links">
            <a href="instructor_dashboard.php">Dashboard</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</nav>
<div class="container" style="margin-top: 90px;">
    <div class="dashboard card" style="padding: 2.5rem 2rem 2rem 2rem; margin-top: 2rem; max-width: 600px; margin-left:auto; margin-right:auto;">
        <h1 style="margin-bottom: 1.5rem;">Add Assignment to <?php echo htmlspecialchars($course['title']); ?></h1>
        <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
        <form method="post" class="auth-form">
            <div class="form-group">
                <label for="title">Assignment Title</label>
                <input type="text" id="title" name="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4"></textarea>
            </div>
            <div class="form-group">
                <label for="due_date">Due Date</label>
                <input type="date" id="due_date" name="due_date" class="form-control" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Assignment</button>
                <a href="manage_assignments.php?course_id=<?php echo $course_id; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<footer class="footer modern-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section about">
                <h3><i class="fas fa-info-circle"></i> About Us</h3>
                <p>We are dedicated to providing quality education through our online learning platform.</p>
            </div>
            <div class="footer-section links">
                <h3><i class="fas fa-link"></i> Quick Links</h3>
                <ul>
                    <li><a href="../index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="../index.php#features"><i class="fas fa-star"></i> Features</a></li>
                    <li><a href="../index.php#courses"><i class="fas fa-book"></i> Courses</a></li>
                    <li><a href="../php/contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
                </ul>
            </div>
            <div class="footer-section contact">
                <h3><i class="fas fa-address-book"></i> Contact Us</h3>
                <p><i class="fas fa-envelope"></i> info@lms.com</p>
                <p><i class="fas fa-phone"></i> (123) 456-7890</p>
                <div class="footer-social">
                    <a href="https://web.facebook.com/wdrobi17" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://x.com/wdrobi21" title="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.instagram.com/wdrobi" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.linkedin.com/in/wdrobi/" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <hr>
            <p>&copy; <?php echo date('Y'); ?> Robiul&Arifa. All rights reserved.</p>
        </div>
    </div>
</footer>
<script src="../js/main.js"></script>
<style>
body { background: linear-gradient(120deg, #f8fafc 0%, #e3e9f7 100%); }
</style>
</body></html> 