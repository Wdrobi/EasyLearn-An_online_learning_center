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

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content_type = $_POST['content_type'] ?? 'text';
    $content = trim($_POST['content_type'] === 'video' ? ($_POST['content_url'] ?? '') : ($_POST['content'] ?? ''));
    if ($title === '' || $content === '') {
        $error = 'Title and content are required.';
    } else {
        // Get next lesson order
        $stmt = $conn->prepare('SELECT MAX(lesson_order) as max_order FROM lessons WHERE course_id = ?');
        $stmt->bind_param('i', $course_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $next_order = ($row['max_order'] ?? 0) + 1;
        $stmt->close();
        // Insert lesson (use content_url for both types)
        $stmt = $conn->prepare('INSERT INTO lessons (course_id, title, content_url, content_type, lesson_order) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('isssi', $course_id, $title, $content, $content_type, $next_order);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header('Location: manage_lessons.php?course_id=' . $course_id);
            exit();
        } else {
            $error = 'Failed to add lesson.';
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
    <title>Add Lesson</title>
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
        <h1 style="margin-bottom: 1.5rem;">Add Lesson to <?php echo htmlspecialchars($course['title']); ?></h1>
        <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
        <form method="post" class="auth-form">
            <div class="form-group">
                <label for="title">Lesson Title</label>
                <input type="text" id="title" name="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="content_type">Content Type</label>
                <select id="content_type" name="content_type" class="form-control" required onchange="toggleContentType()">
                    <option value="text">Text</option>
                    <option value="video">Video</option>
                </select>
            </div>
            <div class="form-group" id="textContentGroup">
                <label for="content">Lesson Content</label>
                <textarea id="content" name="content" class="form-control" rows="6"></textarea>
            </div>
            <div class="form-group" id="videoContentGroup" style="display:none;">
                <label for="content_url">Video URL</label>
                <input type="url" id="content_url" name="content_url" class="form-control">
                <small class="form-text">Enter a YouTube or Vimeo video URL</small>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Lesson</button>
                <a href="manage_lessons.php?course_id=<?php echo $course_id; ?>" class="btn btn-secondary">Cancel</a>
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
<script>
function toggleContentType() {
    var type = document.getElementById('content_type').value;
    document.getElementById('textContentGroup').style.display = (type === 'text') ? '' : 'none';
    document.getElementById('videoContentGroup').style.display = (type === 'video') ? '' : 'none';
}
</script>
<style>
body { background: linear-gradient(120deg, #f8fafc 0%, #e3e9f7 100%); }
</style>
</body></html> 