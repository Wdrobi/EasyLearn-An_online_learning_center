<?php
session_start();
require_once __DIR__ . '/../config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') { header('Location: login.php'); exit(); }
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
// Get quizzes
$stmt = $conn->prepare('SELECT * FROM quizzes WHERE course_id = ? ORDER BY id ASC');
$stmt->bind_param('i', $course_id);
$stmt->execute();
$quizzes = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Manage Quizzes</title><link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head><body>
<nav class="navbar"><div class="container nav-content"><a href="../index.php" class="logo"><img src="../images/EasyLearn.png" alt="EasyLearn Logo" style="height:48px;vertical-align:middle;margin-right:10px;"></a><div class="nav-links"><a href="instructor_dashboard.php">Dashboard</a><a href="profile.php">Profile</a><a href="logout.php">Logout</a></div></div></nav>
<div class="container" style="margin-top: 90px;">
    <div class="dashboard card" style="padding: 2.5rem 2rem 2rem 2rem; margin-top: 2rem;">
        <h1 style="margin-bottom: 1.5rem;">Manage Quizzes for <?php echo htmlspecialchars($course['title']); ?></h1>
        <a href="add_quiz.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary" style="margin-bottom: 1.5rem;">Add Quiz</a>
        <div class="card" style="padding: 0;">
            <table class="table">
                <thead><tr><th>Title</th><th>Actions</th></tr></thead><tbody>
<?php while ($quiz = $quizzes->fetch_assoc()): ?>
<tr><td><?php echo htmlspecialchars($quiz['title']); ?></td><td>
<a href="edit_quiz.php?id=<?php echo $quiz['id']; ?>&course_id=<?php echo $course_id; ?>" class="btn btn-secondary">Edit</a>
<a href="delete_quiz.php?id=<?php echo $quiz['id']; ?>&course_id=<?php echo $course_id; ?>" class="btn btn-danger" onclick="return confirm('Delete this quiz?');">Delete</a>
<a href="manage_quiz_questions.php?quiz_id=<?php echo $quiz['id']; ?>&course_id=<?php echo $course_id; ?>" class="btn btn-info">Questions</a>
</td></tr>
<?php endwhile; ?>
</tbody></table>
        </div>
    </div>
</div>
<!-- Footer -->
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
</body></html><?php $conn->close(); ?> 