<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

if ($user_role === 'instructor') {
    // Instructor: show created courses
    $stmt = $conn->prepare("
        SELECT c.*, 
               (SELECT COUNT(DISTINCT e.student_id) FROM enrollments e WHERE e.course_id = c.id) as total_students,
               (SELECT AVG(e.progress) FROM enrollments e WHERE e.course_id = c.id) as avg_progress
        FROM courses c
        WHERE c.instructor_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $courses = $stmt->get_result();
    $stmt->close();
} else {
    // Student: show enrolled courses
    $stmt = $conn->prepare("
        SELECT c.*, u.name as instructor_name, e.progress, e.completed, e.enrolled_at
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        JOIN users u ON c.instructor_id = u.id
        WHERE e.student_id = ?
        ORDER BY e.enrolled_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $courses = $stmt->get_result();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - EasyLearn</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-content">
            <a href="../index.php" class="logo"><img src="../images/EasyLearn.png" alt="EasyLearn Logo" style="height:56px;vertical-align:middle;margin-right:10px;"></a>
            <div class="nav-links">
                <?php if ($user_role === 'instructor'): ?>
                    <a href="instructor_dashboard.php">Dashboard</a>
                    <a href="my_courses.php" class="active">My Courses</a>
                    <a href="create_course.php">Create Course</a>
                <?php else: ?>
                    <a href="student_dashboard.php">Dashboard</a>
                    <a href="my_courses.php" class="active">My Courses</a>
                    <a href="certificates.php">Certificates</a>
                <?php endif; ?>
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
            <h1>My Courses</h1>
            <?php if ($user_role === 'instructor'): ?>
                <a href="create_course.php" class="btn btn-primary mb-1"><i class="fas fa-plus"></i> Create New Course</a>
            <?php endif; ?>
            <div class="dashboard-grid">
                <?php if ($courses->num_rows > 0): ?>
                    <?php while ($course = $courses->fetch_assoc()): ?>
                        <div class="card course-card fade-in">
                            <img src="<?php echo !empty($course['thumbnail_url']) ? '../' . $course['thumbnail_url'] : '../images/default-course.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($course['title']); ?>">
                            <div class="course-card-content">
                                <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                <p><?php echo htmlspecialchars($course['description']); ?></p>
                                <?php if ($user_role === 'instructor'): ?>
                                    <div class="course-stats">
                                        <span><i class="fas fa-users"></i> <?php echo $course['total_students']; ?> Students</span>
                                        <span><i class="fas fa-chart-line"></i> <?php echo $course['avg_progress'] !== null ? round($course['avg_progress'], 1) : 0; ?>% Avg. Progress</span>
                                    </div>
                                    <div class="course-actions">
                                        <a href="edit_course.php?id=<?php echo $course['id']; ?>" class="btn btn-secondary"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="course.php?id=<?php echo $course['id']; ?>" class="btn btn-primary"><i class="fas fa-eye"></i> View</a>
                                        <a href="manage_lessons.php?course_id=<?php echo $course['id']; ?>" class="btn btn-info"><i class="fas fa-list"></i> Manage Lessons</a>
                                        <a href="course_students.php?course_id=<?php echo $course['id']; ?>" class="btn btn-success"><i class="fas fa-users"></i> View Students</a>
                                        <a href="manage_assignments.php?course_id=<?php echo $course['id']; ?>" class="btn btn-warning"><i class="fas fa-tasks"></i> Assignments</a>
                                        <a href="manage_quizzes.php?course_id=<?php echo $course['id']; ?>" class="btn btn-dark"><i class="fas fa-question-circle"></i> Quizzes</a>
                                        <form action="delete_course.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this course?');">
                                            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                            <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
                                        </form>
                                    </div>
                                <?php else: ?>
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
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="card">
                        <p>You haven't <?php echo $user_role === 'instructor' ? 'created' : 'enrolled in'; ?> any courses yet.</p>
                        <?php if ($user_role === 'instructor'): ?>
                            <a href="create_course.php" class="btn btn-primary">Create Your First Course</a>
                        <?php else: ?>
                            <a href="student_dashboard.php#available-courses" class="btn btn-primary">Browse Courses</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
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