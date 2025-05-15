<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

$user_id = $_SESSION['user_id'];

// Quick Stats
// Total enrolled courses
$enrolled_count = 0;
$completed_count = 0;
$cert_count = 0;

$enrolled_courses = [];
$available_courses = [];
$recent_lessons = [];
$recent_assignments = [];
$recent_quizzes = [];
$certificates = [];

// Enrolled courses and progress
$stmt = $conn->prepare("
    SELECT c.*, u.name as instructor_name, e.progress, e.completed
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN users u ON c.instructor_id = u.id
    WHERE e.student_id = ?
    ORDER BY e.enrolled_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $enrolled_courses[] = $row;
    $enrolled_count++;
    if ($row['completed']) $completed_count++;
}
$stmt->close();

// Available courses
$stmt = $conn->prepare("
    SELECT c.*, u.name as instructor_name
    FROM courses c
    JOIN users u ON c.instructor_id = u.id
    WHERE c.id NOT IN (
        SELECT course_id FROM enrollments WHERE student_id = ?
    )
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $available_courses[] = $row;
}
$stmt->close();

// Certificates
$stmt = $conn->prepare("SELECT c.title, cert.certificate_url, cert.issued_on FROM certificates cert JOIN courses c ON cert.course_id = c.id WHERE cert.student_id = ? ORDER BY cert.issued_on DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $certificates[] = $row;
    $cert_count++;
}
$stmt->close();

// Recent lessons completed
$stmt = $conn->prepare("
    SELECT l.title, c.title as course_title, lp.completed_at
    FROM lesson_progress lp
    JOIN lessons l ON lp.lesson_id = l.id
    JOIN courses c ON l.course_id = c.id
    WHERE lp.student_id = ? AND lp.completed = 1
    ORDER BY lp.completed_at DESC LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $recent_lessons[] = $row;
}
$stmt->close();

// Recent assignments submitted
$stmt = $conn->prepare("
    SELECT a.title, c.title as course_title, s.submitted_at, s.grade
    FROM submissions s
    JOIN assignments a ON s.assignment_id = a.id
    JOIN courses c ON a.course_id = c.id
    WHERE s.student_id = ?
    ORDER BY s.submitted_at DESC LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $recent_assignments[] = $row;
}
$stmt->close();

// Recent quizzes taken
$stmt = $conn->prepare("
    SELECT q.title, c.title as course_title, MAX(qr.score) as score, MAX(qr.completed_at) as completed_at
    FROM quiz_results qr
    JOIN quizzes q ON qr.quiz_id = q.id
    JOIN courses c ON q.course_id = c.id
    WHERE qr.student_id = ?
    GROUP BY qr.quiz_id
    ORDER BY completed_at DESC LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $recent_quizzes[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - EasyLearn</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-content">
            <a href="../index.php" class="logo"><img src="../images/EasyLearn.png" alt="EasyLearn Logo" style="height:56px;vertical-align:middle;margin-right:10px;"></a>
            <div class="nav-links">
                <a href="student_dashboard.php" class="active">Dashboard</a>
                <a href="my_courses.php">My Courses</a>
                <a href="certificates.php">Certificates</a>
                <a href="profile.php">Profile</a>
                <a href="logout.php">Logout</a>
                <button class="theme-toggle" aria-label="Toggle theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            <form class="navbar-search" action="../search.php" method="get" style="display:flex;align-items:center;gap:6px;">
                <input type="text" name="q" placeholder="Search courses..." class="search-input" style="padding:6px 12px;border-radius:6px;border:1px solid #ccc;font-size:1rem;">
                <button type="submit" style="background:none;border:none;cursor:pointer;color:#4a90e2;font-size:1.2rem;"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>

            <!-- Quick Stats -->
            <section class="stats mt-2">
                <div class="dashboard-grid">
                    <div class="card">
                        <i class="fas fa-book fa-2x mb-1"></i>
                        <h3>Enrolled Courses</h3>
                        <p class="stat-number"><?php echo $enrolled_count; ?></p>
                    </div>
                    <div class="card">
                        <i class="fas fa-check-circle fa-2x mb-1"></i>
                        <h3>Courses Completed</h3>
                        <p class="stat-number"><?php echo $completed_count; ?></p>
                    </div>
                    <div class="card">
                        <i class="fas fa-certificate fa-2x mb-1"></i>
                        <h3>Certificates</h3>
                        <p class="stat-number"><?php echo $cert_count; ?></p>
                    </div>
                </div>
            </section>

            <!-- Recent Activity -->
            <section class="recent-activity mt-2">
                <h2>Recent Activity</h2>
                <div class="dashboard-grid">
                    <div class="card">
                        <h4><i class="fas fa-book-reader"></i> Lessons Completed</h4>
                        <?php if ($recent_lessons): ?>
                            <ul>
                                <?php foreach ($recent_lessons as $lesson): ?>
                                    <li><?php echo htmlspecialchars($lesson['title']); ?> <span style="color:#7f9cf5;">(<?php echo htmlspecialchars($lesson['course_title']); ?>)</span> <span style="color:#aaa;font-size:0.95em;">on <?php echo date('M d, Y', strtotime($lesson['completed_at'])); ?></span></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No lessons completed recently.</p>
                        <?php endif; ?>
                    </div>
                    <div class="card">
                        <h4><i class="fas fa-tasks"></i> Assignments Submitted</h4>
                        <?php if ($recent_assignments): ?>
                            <ul>
                                <?php foreach ($recent_assignments as $a): ?>
                                    <li><?php echo htmlspecialchars($a['title']); ?> <span style="color:#7f9cf5;">(<?php echo htmlspecialchars($a['course_title']); ?>)</span> <span style="color:#aaa;font-size:0.95em;">on <?php echo date('M d, Y', strtotime($a['submitted_at'])); ?></span> <?php if ($a['grade'] !== null): ?><span style="color:#ffd54f;">Grade: <?php echo $a['grade']; ?></span><?php endif; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No assignments submitted recently.</p>
                        <?php endif; ?>
                    </div>
                    <div class="card">
                        <h4><i class="fas fa-clipboard-check"></i> Quizzes Taken</h4>
                        <?php if ($recent_quizzes): ?>
                            <ul>
                                <?php foreach ($recent_quizzes as $q): ?>
                                    <li><?php echo htmlspecialchars($q['title']); ?> <span style="color:#7f9cf5;">(<?php echo htmlspecialchars($q['course_title']); ?>)</span> <span style="color:#aaa;font-size:0.95em;">on <?php echo date('M d, Y', strtotime($q['completed_at'])); ?></span> <span style="color:#ffd54f;">Score: <?php echo $q['score']; ?></span></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p>No quizzes taken recently.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Certificates Section -->
            <section class="certificates mt-2">
                <h2>My Certificates</h2>
                <div class="dashboard-grid">
                    <?php if ($certificates): ?>
                        <?php foreach ($certificates as $cert): ?>
                            <div class="card fade-in">
                                <i class="fas fa-certificate fa-2x mb-1" style="color:#ffd54f;"></i>
                                <h4><?php echo htmlspecialchars($cert['title']); ?></h4>
                                <p>Issued on <?php echo date('M d, Y', strtotime($cert['issued_on'])); ?></p>
                                <a href="../<?php echo htmlspecialchars($cert['certificate_url']); ?>" class="btn btn-primary" target="_blank">View Certificate</a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card"><p>No certificates earned yet.</p></div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Enrolled Courses Section -->
            <section class="enrolled-courses mt-2">
                <h2>My Courses</h2>
                <div class="dashboard-grid">
                    <?php if ($enrolled_courses): ?>
                        <?php foreach ($enrolled_courses as $course): ?>
                            <div class="card course-card fade-in">
                                <img src="<?php echo !empty($course['thumbnail_url']) ? '../' . $course['thumbnail_url'] : '../images/default-course.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($course['title']); ?>">
                                <div class="course-card-content">
                                    <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                    <p><?php echo htmlspecialchars($course['description']); ?></p>
                                    <p class="instructor">Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                                    <div class="progress-container">
                                        <div class="progress-bar">
                                            <div class="progress-bar-fill" style="width: <?php echo $course['progress']; ?>%"></div>
                                        </div>
                                        <span class="progress-text"><?php echo $course['progress']; ?>% Complete</span>
                                    </div>
                                    <div class="course-actions">
                                        <a href="course.php?id=<?php echo $course['id']; ?>" class="btn btn-primary">
                                            <?php echo $course['completed'] ? 'Review Course' : 'Continue Learning'; ?>
                                        </a>
                                        <?php if ($course['completed']): ?>
                                            <a href="certificate.php?course_id=<?php echo $course['id']; ?>" class="btn btn-secondary">
                                                View Certificate
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card">
                            <p>You haven't enrolled in any courses yet.</p>
                            <a href="#available-courses" class="btn btn-primary">Browse Courses</a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Available Courses Section -->
            <section id="available-courses" class="available-courses mt-2">
                <h2>Available Courses</h2>
                <div class="dashboard-grid">
                    <?php if ($available_courses): ?>
                        <?php foreach ($available_courses as $course): ?>
                            <div class="card course-card fade-in">
                                <img src="<?php echo !empty($course['thumbnail_url']) ? '../' . $course['thumbnail_url'] : '../images/default-course.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($course['title']); ?>">
                                <div class="course-card-content">
                                    <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                    <p><?php echo htmlspecialchars($course['description']); ?></p>
                                    <p class="instructor">Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                                    <a href="enroll.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary">Enroll Now</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="card">
                            <p>No new courses available at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
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
<?php
$conn->close();
?> 