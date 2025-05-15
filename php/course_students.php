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
// Get students
$stmt = $conn->prepare('SELECT u.id, u.name, u.email, e.progress, e.completed FROM enrollments e JOIN users u ON e.student_id = u.id WHERE e.course_id = ?');
$stmt->bind_param('i', $course_id);
$stmt->execute();
$students = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Course Students</title><link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head><body>
<nav class="navbar"><div class="container nav-content"><a href="../index.php" class="logo"><img src="../images/EasyLearn.png" alt="EasyLearn Logo" style="height:48px;vertical-align:middle;margin-right:10px;"></a><div class="nav-links"><a href="instructor_dashboard.php">Dashboard</a><a href="profile.php">Profile</a><a href="logout.php">Logout</a></div></div></nav>
<div class="container" style="margin-top: 90px;">
    <div class="dashboard card" style="padding: 2.5rem 2rem 2rem 2rem; margin-top: 2rem;">
        <h1 style="margin-bottom: 1.5rem;">Students in <?php echo htmlspecialchars($course['title']); ?></h1>
        <div class="card" style="padding: 0;">
            <table class="table">
                <thead><tr><th>Name</th><th>Email</th><th>Progress</th><th>Assignments</th><th>Quizzes</th></tr></thead><tbody>
<?php while ($student = $students->fetch_assoc()): ?>
<tr><td><?php echo htmlspecialchars($student['name']); ?></td><td><?php echo htmlspecialchars($student['email']); ?></td><td><?php echo $student['progress']; ?>%</td><td>
<?php
// Assignments
$astmt = $conn->prepare('SELECT a.title, s.grade FROM assignments a LEFT JOIN submissions s ON a.id = s.assignment_id AND s.student_id = ? WHERE a.course_id = ?');
$astmt->bind_param('ii', $student['id'], $course_id);
$astmt->execute();
$asubs = $astmt->get_result();
while ($a = $asubs->fetch_assoc()) { echo htmlspecialchars($a['title']) . ': ' . ($a['grade'] !== null ? $a['grade'] : 'N/A') . '<br>'; }
$astmt->close();
?></td><td>
<?php
// Quizzes
$qstmt = $conn->prepare('SELECT q.title, MAX(r.score) as score FROM quizzes q LEFT JOIN quiz_results r ON q.id = r.quiz_id AND r.student_id = ? WHERE q.course_id = ? GROUP BY q.id');
$qstmt->bind_param('ii', $student['id'], $course_id);
$qstmt->execute();
$qsubs = $qstmt->get_result();
while ($q = $qsubs->fetch_assoc()) { echo htmlspecialchars($q['title']) . ': ' . ($q['score'] !== null ? $q['score'] : 'N/A') . '<br>'; }
$qstmt->close();
?></td></tr>
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