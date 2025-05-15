<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: student_dashboard.php?msg=Invalid+course');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$course_id = intval($_GET['id']);
$conn = getDBConnection();
if (!$conn) {
    die('Database connection failed');
}

// Get course info
$stmt = $conn->prepare('SELECT c.*, u.name as instructor_name FROM courses c JOIN users u ON c.instructor_id = u.id WHERE c.id = ?');
$stmt->bind_param('i', $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$course) {
    $conn->close();
    header('Location: student_dashboard.php?msg=Course+not+found');
    exit();
}

// For students: check enrollment
if ($user_role === 'student') {
    $stmt = $conn->prepare('SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?');
    $stmt->bind_param('ii', $user_id, $course_id);
    $stmt->execute();
    $enrollment = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$enrollment) {
        $conn->close();
        header('Location: student_dashboard.php?msg=Not+enrolled+in+this+course');
        exit();
    }
}

// Get lessons
$stmt = $conn->prepare('SELECT * FROM lessons WHERE course_id = ? ORDER BY lesson_order ASC');
$stmt->bind_param('i', $course_id);
$stmt->execute();
$lessons = $stmt->get_result();
$stmt->close();

// For students: get lesson progress
$lesson_progress = [];
if ($user_role === 'student') {
    $stmt = $conn->prepare('SELECT lesson_id, completed FROM lesson_progress WHERE student_id = ? AND lesson_id IN (SELECT id FROM lessons WHERE course_id = ?)');
    $stmt->bind_param('ii', $user_id, $course_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $lesson_progress[$row['lesson_id']] = $row['completed'];
    }
    $stmt->close();
    // Update progress in enrollments table
    $stmt = $conn->prepare('SELECT COUNT(*) as total FROM lessons WHERE course_id = ?');
    $stmt->bind_param('i', $course_id);
    $stmt->execute();
    $total_lessons = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    $completed_lessons = count(array_filter($lesson_progress));
    $progress = $total_lessons ? round(($completed_lessons/$total_lessons)*100, 2) : 0;
    $completed = ($completed_lessons == $total_lessons && $total_lessons > 0) ? 1 : 0;
    $stmt = $conn->prepare('UPDATE enrollments SET progress = ?, completed = ? WHERE student_id = ? AND course_id = ?');
    $stmt->bind_param('diii', $progress, $completed, $user_id, $course_id);
    $stmt->execute();
    $stmt->close();
}

// Get assignments
$assignments = [];
$stmt = $conn->prepare('SELECT * FROM assignments WHERE course_id = ? ORDER BY id ASC');
$stmt->bind_param('i', $course_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) { $assignments[] = $row; }
$stmt->close();

// Get quizzes
$quizzes = [];
$stmt = $conn->prepare('SELECT * FROM quizzes WHERE course_id = ? ORDER BY id ASC');
$stmt->bind_param('i', $course_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) { $quizzes[] = $row; }
$stmt->close();

// Handle review submission
$review_submitted = false;
$review_error = '';
if ($user_role === 'student' && isset($_POST['course_review']) && $completed) {
    $review_text = trim($_POST['course_review']);
    if ($review_text !== '') {
        // Check if already reviewed
        $stmt = $conn->prepare('SELECT id FROM reviews WHERE student_id = ? AND course_id = ?');
        $stmt->bind_param('ii', $user_id, $course_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $stmt->close();
            $stmt = $conn->prepare('INSERT INTO reviews (student_id, course_id, review) VALUES (?, ?, ?)');
            $stmt->bind_param('iis', $user_id, $course_id, $review_text);
            $stmt->execute();
            $stmt->close();
            $review_submitted = true;
        } else {
            $review_error = 'You have already submitted a review for this course.';
            $stmt->close();
        }
    } else {
        $review_error = 'Please enter your feedback before submitting.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - EasyLearn</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="container nav-content">
            <a href="../index.php" class="logo"><img src="../images/EasyLearn.png" alt="EasyLearn Logo" style="height:56px;vertical-align:middle;margin-right:10px;"></a>
            <div class="nav-links">
                <?php if ($user_role === 'instructor'): ?>
                    <a href="instructor_dashboard.php">Dashboard</a>
                <?php else: ?>
                    <a href="student_dashboard.php">Dashboard</a>
                <?php endif; ?>
                <a href="my_courses.php">My Courses</a>
                <a href="profile.php">Profile</a>
                <a href="logout.php">Logout</a>
                <button class="theme-toggle" aria-label="Toggle theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </div>
    </nav>
    <div class="container">
        <div class="dashboard">
            <h1><?php echo htmlspecialchars($course['title']); ?></h1>
            <p class="instructor">Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?></p>
            <img src="<?php echo !empty($course['thumbnail_url']) ? '../' . $course['thumbnail_url'] : '../images/default-course.jpg'; ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" style="max-width:320px;margin-bottom:1rem;">
            <p><?php echo htmlspecialchars($course['description']); ?></p>
            <?php if ($user_role === 'student'): ?>
                <?php $total = $lessons->num_rows; $completed = count(array_filter($lesson_progress)); $progress = $total ? round(($completed/$total)*100) : 0; ?>
                <div class="progress-bar-container" style="background:#232946;padding:0.5rem 1rem;border-radius:12px;margin-bottom:1.5rem;box-shadow:0 2px 8px rgba(80,120,200,0.07);max-width:600px;">
                    <div class="progress-bar" style="background:#e3e9f7;height:18px;border-radius:8px;overflow:hidden;position:relative;">
                        <div style="background:linear-gradient(90deg,#4a90e2,#7f9cf5);height:100%;width:<?php echo $progress; ?>%;border-radius:8px;transition:width 0.4s;"></div>
                        <span style="position:absolute;left:50%;top:0;transform:translateX(-50%);color:#222;font-weight:700;line-height:18px;font-size:1rem;letter-spacing:0.5px;"><?php echo $progress; ?>% Complete</span>
                    </div>
                </div>
                <?php if ($completed < $total): ?>
                    <?php foreach ($lessons as $lesson) { if (!isset($lesson_progress[$lesson['id']]) || !$lesson_progress[$lesson['id']]) { $next_lesson = $lesson; break; } } ?>
                    <?php if (isset($next_lesson)): ?>
                        <a href="lesson_process.php?lesson_id=<?php echo $next_lesson['id']; ?>&course_id=<?php echo $course_id; ?>" class="btn btn-success" style="margin-bottom:1.5rem;">Continue Course</a>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-success" style="margin-bottom:1.5rem;">Congratulations! You have completed all lessons.</div>
                <?php endif; ?>
            <?php endif; ?>
            <h2>Lessons</h2>
            <div class="dashboard-grid">
                <?php if ($lessons->num_rows > 0): ?>
                    <?php $lessons->data_seek(0); while ($lesson = $lessons->fetch_assoc()): ?>
                        <div class="card">
                            <h3><?php echo htmlspecialchars($lesson['title']); ?></h3>
                            <?php if ($user_role === 'student'): ?>
                                <p>Status: <?php echo isset($lesson_progress[$lesson['id']]) && $lesson_progress[$lesson['id']] ? '<span style="color:green;">Completed</span>' : '<span style="color:#aaa;">Not completed</span>'; ?></p>
                                <a href="lesson_process.php?lesson_id=<?php echo $lesson['id']; ?>&course_id=<?php echo $course_id; ?>" class="btn btn-primary"><?php echo isset($lesson_progress[$lesson['id']]) && $lesson_progress[$lesson['id']] ? 'Review Lesson' : 'Continue Lesson'; ?></a>
                            <?php else: ?>
                                <?php if ($lesson['content_type'] === 'video'): ?>
                                    <?php if (!empty($lesson['content_url'])): ?>
                                        <div style="margin: 1rem 0;">
                                            <iframe width="420" height="236" src="<?php echo htmlspecialchars($lesson['content_url']); ?>" frameborder="0" allowfullscreen></iframe>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p><?php echo mb_strimwidth(strip_tags($lesson['content_url']), 0, 120, '...'); ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="card"><p>No lessons added yet.</p></div>
                <?php endif; ?>
            </div>
            <h2 style="margin-top:2.5rem;">Assignments</h2>
            <div class="dashboard-grid">
                <?php if (count($assignments) > 0): ?>
                    <?php foreach ($assignments as $assignment): ?>
                        <div class="card">
                            <h3><?php echo htmlspecialchars($assignment['title']); ?></h3>
                            <p>Due: <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?></p>
                            <a href="assignment.php?id=<?php echo $assignment['id']; ?>&course_id=<?php echo $course_id; ?>" class="btn btn-primary">View / Submit</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="card"><p>No assignments for this course.</p></div>
                <?php endif; ?>
            </div>
            <h2 style="margin-top:2.5rem;">Quizzes</h2>
            <div class="dashboard-grid">
                <?php if (count($quizzes) > 0): ?>
                    <?php foreach ($quizzes as $quiz): ?>
                        <div class="card">
                            <h3><?php echo htmlspecialchars($quiz['title']); ?></h3>
                            <a href="quiz.php?id=<?php echo $quiz['id']; ?>&course_id=<?php echo $course_id; ?>" class="btn btn-primary">Take Quiz</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="card"><p>No quizzes for this course.</p></div>
                <?php endif; ?>
            </div>
            <?php if ($user_role === 'student' && $completed): ?>
                <?php
                // Check if review already exists
                $stmt = $conn->prepare('SELECT id FROM reviews WHERE student_id = ? AND course_id = ?');
                $stmt->bind_param('ii', $user_id, $course_id);
                $stmt->execute();
                $stmt->store_result();
                $has_review = $stmt->num_rows > 0;
                $stmt->close();
                ?>
                <div class="card" style="margin:2rem auto 2rem auto;max-width:600px;">
                    <?php if ($review_submitted): ?>
                        <div class="alert alert-success">Thank you for your feedback!</div>
                    <?php elseif (!$has_review): ?>
                        <form method="post" style="padding:1.5rem;">
                            <h3 style="margin-bottom:1rem;">We value your feedback!</h3>
                            <?php if ($review_error): ?><div class="alert alert-error"><?php echo $review_error; ?></div><?php endif; ?>
                            <textarea name="course_review" class="form-control" rows="4" placeholder="Share your experience with this course..." required></textarea>
                            <button type="submit" class="btn btn-primary" style="margin-top:1rem;">Submit Review</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info">You have already submitted a review for this course.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
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
                        <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                        <li><a href="#features"><i class="fas fa-star"></i> Features</a></li>
                        <li><a href="#courses"><i class="fas fa-book"></i> Courses</a></li>
                        <li><a href="php/contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
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
</body>
</html>
<?php $conn->close(); ?> 