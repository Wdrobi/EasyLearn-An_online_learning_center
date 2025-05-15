<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in and is an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

// Get instructor's courses with enrollment stats
$stmt = $conn->prepare("
    SELECT 
        c.*,
        COUNT(DISTINCT e.student_id) as total_students,
        AVG(e.progress) as avg_progress
    FROM courses c
    LEFT JOIN enrollments e ON c.id = e.course_id
    WHERE c.instructor_id = ?
    GROUP BY c.id
    ORDER BY c.created_at DESC
");

$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$courses = $stmt->get_result();

// Get recent enrollments
$stmt = $conn->prepare("
    SELECT 
        e.*,
        c.title as course_title,
        u.name as student_name
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN users u ON e.student_id = u.id
    WHERE c.instructor_id = ?
    ORDER BY e.enrolled_at DESC
    LIMIT 5
");

$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$recent_enrollments = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard - Learning Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-content">
            <a href="../index.php" class="logo"><img src="../images/EasyLearn.png" alt="EasyLearn Logo" style="height:56px;vertical-align:middle;margin-right:10px;"></a>
            <div class="nav-links">
                <a href="instructor_dashboard.php" class="active">Dashboard</a>
                <a href="my_courses.php">My Courses</a>
                <a href="create_course.php">Create Course</a>
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
                        <i class="fas fa-book fa-3x mb-1"></i>
                        <h3>Total Courses</h3>
                        <p class="stat-number"><?php echo $courses->num_rows; ?></p>
                    </div>
                    <div class="card">
                        <i class="fas fa-users fa-3x mb-1"></i>
                        <h3>Total Students</h3>
                        <p class="stat-number">
                            <?php
                            $total_students = 0;
                            while ($course = $courses->fetch_assoc()) {
                                $total_students += $course['total_students'];
                            }
                            echo $total_students;
                            $courses->data_seek(0); // Reset pointer
                            ?>
                        </p>
                    </div>
                    <div class="card">
                        <i class="fas fa-chart-line fa-3x mb-1"></i>
                        <h3>Average Progress</h3>
                        <p class="stat-number">
                            <?php
                            $total_progress = 0;
                            $course_count = 0;
                            while ($course = $courses->fetch_assoc()) {
                                if ($course['avg_progress'] !== null) {
                                    $total_progress += $course['avg_progress'];
                                    $course_count++;
                                }
                            }
                            echo $course_count > 0 ? round($total_progress / $course_count, 1) . '%' : '0%';
                            $courses->data_seek(0); // Reset pointer
                            ?>
                        </p>
                    </div>
                </div>
            </section>

            <!-- My Courses Section -->
            <section class="my-courses mt-2">
                <div class="section-header">
                    <h2>My Courses</h2>
                    <a href="create_course.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create New Course
                    </a>
                </div>
                <div class="dashboard-grid">
                    <?php if ($courses->num_rows > 0): ?>
                        <?php while ($course = $courses->fetch_assoc()): ?>
                            <div class="card course-card fade-in">
                                <img src="<?php echo !empty($course['thumbnail_url']) ? '../' . $course['thumbnail_url'] : '../images/default-course.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($course['title']); ?>">
                                <div class="course-card-content">
                                    <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                    <p><?php echo htmlspecialchars($course['description']); ?></p>
                                    
                                    <div class="course-stats">
                                        <div class="stat">
                                            <i class="fas fa-users"></i>
                                            <span><?php echo $course['total_students']; ?> Students</span>
                                        </div>
                                        <div class="stat">
                                            <i class="fas fa-chart-line"></i>
                                            <span><?php echo round($course['avg_progress'], 1); ?>% Avg. Progress</span>
                                        </div>
                                    </div>

                                    <div class="course-actions">
                                        <a href="edit_course.php?id=<?php echo $course['id']; ?>" class="btn btn-secondary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="course.php?id=<?php echo $course['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="manage_lessons.php?course_id=<?php echo $course['id']; ?>" class="btn btn-info">
                                            <i class="fas fa-list"></i> Manage Lessons
                                        </a>
                                        <a href="course_students.php?course_id=<?php echo $course['id']; ?>" class="btn btn-success">
                                            <i class="fas fa-users"></i> View Students
                                        </a>
                                        <a href="manage_assignments.php?course_id=<?php echo $course['id']; ?>" class="btn btn-warning">
                                            <i class="fas fa-tasks"></i> Assignments
                                        </a>
                                        <a href="manage_quizzes.php?course_id=<?php echo $course['id']; ?>" class="btn btn-dark">
                                            <i class="fas fa-question-circle"></i> Quizzes
                                        </a>
                                        <button class="btn btn-danger delete-course-btn" data-course-id="<?php echo $course['id']; ?>">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="card">
                            <p>You haven't created any courses yet.</p>
                            <a href="create_course.php" class="btn btn-primary">Create Your First Course</a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Recent Enrollments Section -->
            <section class="recent-enrollments mt-2">
                <h2>Recent Enrollments</h2>
                <div class="card">
                    <?php if ($recent_enrollments->num_rows > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Course</th>
                                    <th>Enrolled On</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($enrollment = $recent_enrollments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($enrollment['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($enrollment['course_title']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($enrollment['enrolled_at'])); ?></td>
                                        <td>
                                            <div class="progress-bar">
                                                <div class="progress-bar-fill" style="width: <?php echo $enrollment['progress']; ?>%"></div>
                                            </div>
                                            <span class="progress-text"><?php echo $enrollment['progress']; ?>%</span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No recent enrollments.</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);align-items:center;justify-content:center;z-index:9999;">
        <div class="modal-content" style="background:#fff;padding:2rem;border-radius:10px;max-width:400px;text-align:center;">
            <h3>Delete Course</h3>
            <p>Are you sure you want to delete this course? This action cannot be undone.</p>
            <form id="deleteCourseForm" method="POST" action="delete_course.php">
                <input type="hidden" name="course_id" id="deleteCourseId">
                <button type="submit" class="btn btn-danger">Delete</button>
                <button type="button" class="btn btn-secondary" id="cancelDelete">Cancel</button>
            </form>
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
    <script>
    document.querySelectorAll('.delete-course-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('deleteCourseId').value = this.getAttribute('data-course-id');
            document.getElementById('deleteModal').style.display = 'flex';
        });
    });
    document.getElementById('cancelDelete').onclick = function() {
        document.getElementById('deleteModal').style.display = 'none';
    };
    </script>
</body>
</html>
<?php
$conn->close();
?> 