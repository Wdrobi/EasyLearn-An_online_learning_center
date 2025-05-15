<?php
session_start();
require_once __DIR__ . '/../config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: login.php'); exit();
}
if (!isset($_GET['id']) || !isset($_GET['course_id'])) { header('Location: student_dashboard.php'); exit(); }
$assignment_id = intval($_GET['id']);
$course_id = intval($_GET['course_id']);
$user_id = $_SESSION['user_id'];
$conn = getDBConnection();
// Fetch assignment
$stmt = $conn->prepare('SELECT a.*, c.title as course_title FROM assignments a JOIN courses c ON a.course_id = c.id WHERE a.id = ? AND a.course_id = ?');
$stmt->bind_param('ii', $assignment_id, $course_id);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$assignment) { echo '<p>Assignment not found.</p>'; exit(); }
// Fetch submission
$stmt = $conn->prepare('SELECT * FROM submissions WHERE student_id = ? AND assignment_id = ?');
$stmt->bind_param('ii', $user_id, $assignment_id);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();
$stmt->close();
$submit_error = '';
$submit_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$submission) {
    $text_content = trim($_POST['text_content'] ?? '');
    $file_url = null;
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/assignments/';
        if (!file_exists($upload_dir)) { mkdir($upload_dir, 0777, true); }
        $file_name = uniqid() . '_' . basename($_FILES['file']['name']);
        $target_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_path)) {
            $file_url = 'uploads/assignments/' . $file_name;
        } else {
            $submit_error = 'File upload failed.';
        }
    }
    if (!$submit_error && ($file_url || $text_content !== '')) {
        $stmt = $conn->prepare('INSERT INTO submissions (student_id, assignment_id, file_url, text_content, submitted_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->bind_param('iiss', $user_id, $assignment_id, $file_url, $text_content);
        if ($stmt->execute()) {
            $submit_success = true;
        } else {
            $submit_error = 'Submission failed.';
        }
        $stmt->close();
    } elseif (!$submit_error) {
        $submit_error = 'Please provide a file or text content.';
    }
    // Refresh submission
    $stmt = $conn->prepare('SELECT * FROM submissions WHERE student_id = ? AND assignment_id = ?');
    $stmt->bind_param('ii', $user_id, $assignment_id);
    $stmt->execute();
    $submission = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment - <?php echo htmlspecialchars($assignment['title']); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<nav class="navbar">
    <div class="container nav-content">
        <a href="../index.php" class="logo"><img src="../images/EasyLearn.png" alt="EasyLearn Logo" style="height:48px;vertical-align:middle;margin-right:10px;"></a>
        <div class="nav-links">
            <a href="student_dashboard.php">Dashboard</a>
            <a href="my_courses.php">My Courses</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</nav>
<div class="container" style="margin-top: 90px;">
    <div class="dashboard card" style="padding: 2.5rem 2rem 2rem 2rem; margin-top: 2rem; max-width: 700px; margin-left:auto; margin-right:auto;">
        <h1><?php echo htmlspecialchars($assignment['title']); ?></h1>
        <h3 style="color:#4a90e2;">Course: <?php echo htmlspecialchars($assignment['course_title']); ?></h3>
        <p><strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?></p>
        <div class="card" style="padding:1.5rem;margin-bottom:2rem;background:#f8fafc;">
            <h3>Description</h3>
            <p><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
        </div>
        <?php if ($submission): ?>
            <div class="alert alert-success">You have submitted this assignment.</div>
            <div class="card" style="padding:1.2rem;margin-bottom:1.5rem;">
                <h4>Your Submission</h4>
                <?php if ($submission['file_url']): ?>
                    <p><a href="../<?php echo htmlspecialchars($submission['file_url']); ?>" target="_blank">Download Submitted File</a></p>
                <?php endif; ?>
                <?php if ($submission['text_content']): ?>
                    <div style="background:#fff; color:#222; padding:1rem;border-radius:8px;margin-top:0.5rem;"><strong>Text:</strong><br><?php echo nl2br(htmlspecialchars($submission['text_content'])); ?></div>
                <?php endif; ?>
                <p><strong>Submitted at:</strong> <?php echo date('M d, Y H:i', strtotime($submission['submitted_at'])); ?></p>
                <?php if ($submission['grade'] !== null): ?>
                    <p><strong>Grade:</strong> <?php echo $submission['grade']; ?></p>
                <?php endif; ?>
                <?php if ($submission['feedback']): ?>
                    <div class="alert alert-info"><strong>Instructor Feedback:</strong> <?php echo htmlspecialchars($submission['feedback']); ?></div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <h3 style="margin-bottom:1rem;">Submit Your Assignment</h3>
            <?php if ($submit_error): ?><div class="alert alert-error"><?php echo $submit_error; ?></div><?php endif; ?>
            <?php if ($submit_success): ?><div class="alert alert-success">Submission successful!</div><?php endif; ?>
            <form method="post" enctype="multipart/form-data" style="margin-bottom:1.5rem;">
                <div class="form-group">
                    <label for="file">Upload File (optional)</label>
                    <input type="file" id="file" name="file" class="form-control">
                </div>
                <div class="form-group">
                    <label for="text_content">Or Enter Text</label>
                    <textarea id="text_content" name="text_content" class="form-control" rows="5"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Submit Assignment</button>
            </form>
        <?php endif; ?>
        <a href="course.php?id=<?php echo $course_id; ?>" class="btn btn-secondary">Back to Course</a>
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