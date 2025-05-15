<?php
session_start();
require_once __DIR__ . '/../config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: login.php'); exit();
}
if (!isset($_GET['id']) || !isset($_GET['course_id'])) { header('Location: student_dashboard.php'); exit(); }
$quiz_id = intval($_GET['id']);
$course_id = intval($_GET['course_id']);
$user_id = $_SESSION['user_id'];
$conn = getDBConnection();
// Fetch quiz
$stmt = $conn->prepare('SELECT q.*, c.title as course_title FROM quizzes q JOIN courses c ON q.course_id = c.id WHERE q.id = ? AND q.course_id = ?');
$stmt->bind_param('ii', $quiz_id, $course_id);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$quiz) { echo '<p>Quiz not found.</p>'; exit(); }
// Fetch all attempts
$stmt = $conn->prepare('SELECT * FROM quiz_results WHERE student_id = ? AND quiz_id = ? ORDER BY attempt ASC, completed_at ASC');
$stmt->bind_param('ii', $user_id, $quiz_id);
$stmt->execute();
$attempts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$last_attempt = end($attempts);
$can_retake = false;
if ($last_attempt && $last_attempt['score'] < 40) {
    $can_retake = true;
}
$max_score = null;
if ($attempts) {
    $max_score = max(array_column($attempts, 'score'));
}
// Fetch questions
$stmt = $conn->prepare('SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id ASC');
$stmt->bind_param('i', $quiz_id);
$stmt->execute();
$questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$score = null;
$answers = [];
$show_result = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($can_retake || !$last_attempt)) {
    $correct = 0;
    $total = count($questions);
    foreach ($questions as $q) {
        $qid = $q['id'];
        $selected = $_POST['answer_' . $qid] ?? '';
        $answers[$qid] = $selected;
        if ($selected === $q['correct_answer']) $correct++;
    }
    $score = $total > 0 ? round(($correct / $total) * 100, 2) : 0;
    $attempt_num = $last_attempt ? ($last_attempt['attempt'] + 1) : 1;
    // Save result
    $stmt = $conn->prepare('INSERT INTO quiz_results (student_id, quiz_id, attempt, score) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('iiid', $user_id, $quiz_id, $attempt_num, $score);
    $stmt->execute();
    $stmt->close();
    // Refresh attempts
    $stmt = $conn->prepare('SELECT * FROM quiz_results WHERE student_id = ? AND quiz_id = ? ORDER BY attempt ASC, completed_at ASC');
    $stmt->bind_param('ii', $user_id, $quiz_id);
    $stmt->execute();
    $attempts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $last_attempt = end($attempts);
    $can_retake = $last_attempt && $last_attempt['score'] < 40;
    $max_score = max(array_column($attempts, 'score'));
    $show_result = true;
} elseif ($last_attempt) {
    $show_result = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz - <?php echo htmlspecialchars($quiz['title']); ?></title>
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
    <div class="dashboard card" style="padding: 2.5rem 2rem 2rem 2rem; margin-top: 2rem; max-width: 800px; margin-left:auto; margin-right:auto;">
        <h1><?php echo htmlspecialchars($quiz['title']); ?></h1>
        <h3 style="color:#4a90e2;">Course: <?php echo htmlspecialchars($quiz['course_title']); ?></h3>
        <p><?php echo nl2br(htmlspecialchars($quiz['description'])); ?></p>
        <?php if ($show_result): ?>
            <div class="alert alert-success" style="font-size:1.2rem;">Your Score: <strong><?php echo $last_attempt['score']; ?>%</strong></div>
            <div class="card" style="padding:1.5rem;margin-bottom:2rem;background:#f8fafc;">
                <h3>Quiz Review (Last Attempt)</h3>
                <?php foreach ($questions as $idx => $q): ?>
                    <div style="margin-bottom:1.2rem;">
                        <strong>Q<?php echo $idx+1; ?>: <?php echo htmlspecialchars($q['question']); ?></strong><br>
                        <?php $opts = json_decode($q['options'], true); ?>
                        <?php foreach ($opts as $opt): ?>
                            <div style="margin-left:1.5em;<?php if ($q['correct_answer'] === $opt) echo 'color:green;font-weight:bold;'; ?>">
                                <?php echo htmlspecialchars($opt); ?>
                                <?php if (isset($answers[$q['id']]) && $answers[$q['id']] === $opt && $q['correct_answer'] !== $opt) echo ' <span style=\"color:red;\">(Your answer)</span>'; ?>
                                <?php if (isset($answers[$q['id']]) && $answers[$q['id']] === $opt && $q['correct_answer'] === $opt) echo ' <span style=\"color:green;\">(Correct)</span>'; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="card" style="padding:1.5rem;margin-bottom:2rem;background:#f8fafc;">
                <h3>All Attempts</h3>
                <table class="table">
                    <thead><tr><th>Attempt</th><th>Score (%)</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($attempts as $a): ?>
                        <tr<?php if ($a['score'] == $max_score) echo ' style="background:#d4edda;font-weight:bold;"'; ?>>
                            <td><?php echo $a['attempt']; ?></td>
                            <td><?php echo $a['score']; ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($a['completed_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="margin-top:1em;">Highest Score: <strong><?php echo $max_score; ?>%</strong></div>
            </div>
            <?php if ($can_retake): ?>
                <form method="post">
                    <button type="submit" class="btn btn-primary">Retake Quiz</button>
                </form>
                <div class="alert alert-info" style="margin-top:1em;">You can retake the quiz because your last score is less than 40%.</div>
            <?php else: ?>
                <div class="alert alert-info" style="margin-top:1em;">Retake is only allowed if your last score is less than 40%.</div>
            <?php endif; ?>
        <?php elseif (count($questions) > 0): ?>
            <form method="post">
                <?php foreach ($questions as $idx => $q): ?>
                    <div class="card" style="padding:1.2rem;margin-bottom:1.5rem;">
                        <strong>Q<?php echo $idx+1; ?>: <?php echo htmlspecialchars($q['question']); ?></strong><br>
                        <?php $opts = json_decode($q['options'], true); shuffle($opts); ?>
                        <?php foreach ($opts as $opt): ?>
                            <div style="margin-left:1.5em;">
                                <label><input type="radio" name="answer_<?php echo $q['id']; ?>" value="<?php echo htmlspecialchars($opt); ?>" required> <?php echo htmlspecialchars($opt); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-primary">Submit Quiz</button>
            </form>
        <?php else: ?>
            <div class="alert alert-info">No questions in this quiz yet.</div>
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
</body></html> 