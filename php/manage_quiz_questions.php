<?php
session_start();
require_once __DIR__ . '/../config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    header('Location: login.php'); exit();
}
if (!isset($_GET['quiz_id']) || !isset($_GET['course_id'])) { header('Location: instructor_dashboard.php'); exit(); }
$quiz_id = intval($_GET['quiz_id']);
$course_id = intval($_GET['course_id']);
$conn = getDBConnection();
// Check quiz ownership
$stmt = $conn->prepare('SELECT q.*, c.title as course_title FROM quizzes q JOIN courses c ON q.course_id = c.id WHERE q.id = ? AND c.instructor_id = ?');
$stmt->bind_param('ii', $quiz_id, $_SESSION['user_id']);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();
if (!$quiz) { header('Location: instructor_dashboard.php?msg=Unauthorized'); exit(); }
$stmt->close();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = trim($_POST['question'] ?? '');
    $option1 = trim($_POST['option1'] ?? '');
    $option2 = trim($_POST['option2'] ?? '');
    $option3 = trim($_POST['option3'] ?? '');
    $option4 = trim($_POST['option4'] ?? '');
    $correct = $_POST['correct'] ?? '';
    if ($question === '' || $option1 === '' || $option2 === '' || $option3 === '' || $option4 === '' || $correct === '') {
        $error = 'All fields are required.';
    } else {
        $options = json_encode([$option1, $option2, $option3, $option4]);
        $correct_answer = '';
        if ($correct === 'option1') $correct_answer = $option1;
        elseif ($correct === 'option2') $correct_answer = $option2;
        elseif ($correct === 'option3') $correct_answer = $option3;
        elseif ($correct === 'option4') $correct_answer = $option4;
        $stmt = $conn->prepare('INSERT INTO quiz_questions (quiz_id, question, options, correct_answer) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('isss', $quiz_id, $question, $options, $correct_answer);
        if (!$stmt->execute()) {
            $error = 'Failed to add question.';
        }
        $stmt->close();
    }
}
// Get questions
$stmt = $conn->prepare('SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id ASC');
$stmt->bind_param('i', $quiz_id);
$stmt->execute();
$questions = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Quiz Questions</title>
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
    <div class="dashboard card" style="padding: 2.5rem 2rem 2rem 2rem; margin-top: 2rem; max-width: 900px; margin-left:auto; margin-right:auto;">
        <h1 style="margin-bottom: 1.5rem;">Manage Questions for Quiz: <?php echo htmlspecialchars($quiz['title']); ?></h1>
        <h3 style="margin-bottom: 1.5rem; color: #4a90e2;">Course: <?php echo htmlspecialchars($quiz['course_title']); ?></h3>
        <div class="card" style="padding: 1.5rem; margin-bottom: 2rem;">
            <h2>Add New Question</h2>
            <?php if ($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
            <form method="post" class="auth-form">
                <div class="form-group">
                    <label for="question">Question</label>
                    <input type="text" id="question" name="question" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Options</label>
                    <input type="text" name="option1" class="form-control" placeholder="Option 1" required>
                    <input type="text" name="option2" class="form-control" placeholder="Option 2" required>
                    <input type="text" name="option3" class="form-control" placeholder="Option 3" required>
                    <input type="text" name="option4" class="form-control" placeholder="Option 4" required>
                </div>
                <div class="form-group">
                    <label for="correct">Correct Option</label>
                    <select id="correct" name="correct" class="form-control" required>
                        <option value="">Select correct option</option>
                        <option value="option1">Option 1</option>
                        <option value="option2">Option 2</option>
                        <option value="option3">Option 3</option>
                        <option value="option4">Option 4</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add Question</button>
                </div>
            </form>
        </div>
        <div class="card" style="padding: 1.5rem;">
            <h2>Existing Questions</h2>
            <?php if ($questions->num_rows > 0): ?>
                <table class="table">
                    <thead><tr><th>#</th><th>Question</th><th>Options</th><th>Correct</th></tr></thead>
                    <tbody>
                    <?php $i=1; while ($q = $questions->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($q['question']); ?></td>
                            <td>
                                <?php $opts = json_decode($q['options'], true); if ($opts) { foreach ($opts as $idx => $opt) { echo ($idx+1) . '. ' . htmlspecialchars($opt) . '<br>'; } } ?>
                            </td>
                            <td><?php echo htmlspecialchars($q['correct_answer']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No questions added yet.</p>
            <?php endif; ?>
        </div>
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