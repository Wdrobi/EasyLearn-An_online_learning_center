<?php
session_start();
require_once 'php/config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-content">
            <a href="index.php" class="logo"><img src="images/EasyLearn.png" alt="EasyLearn Logo" style="height:56px;vertical-align:middle;margin-right:10px;"></a>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="#features">Features</a>
                <a href="#courses">Courses</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['user_role'] === 'student'): ?>
                        <a href="php/student_dashboard.php">Dashboard</a>
                    <?php elseif ($_SESSION['user_role'] === 'instructor'): ?>
                        <a href="php/instructor_dashboard.php">Dashboard</a>
                    <?php else: ?>
                        <a href="php/dashboard.php">Dashboard</a>
                    <?php endif; ?>
                    <a href="php/logout.php">Logout</a>
                <?php else: ?>
                    <a href="php/login.php">Login</a>
                    <a href="php/register.php">Register</a>
                <?php endif; ?>
                <button class="theme-toggle" aria-label="Toggle theme">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            <form class="navbar-search" action="search.php" method="get" style="display:flex;align-items:center;gap:6px;">
                <input type="text" name="q" placeholder="Search courses..." class="search-input" style="padding:6px 12px;border-radius:6px;border:1px solid #ccc;font-size:1rem;">
                <button type="submit" style="background:none;border:none;cursor:pointer;color:#4a90e2;font-size:1.2rem;"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1 class="fade-in">Welcome to Our Learning Platform</h1>
            <p class="fade-in">Discover a world of knowledge with our comprehensive courses and expert instructors.</p>
            <div class="hero-buttons mt-2">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['user_role'] === 'student'): ?>
                        <a href="php/student_dashboard.php" class="btn btn-primary">Get Started</a>
                    <?php elseif ($_SESSION['user_role'] === 'instructor'): ?>
                        <a href="php/instructor_dashboard.php" class="btn btn-primary">Get Started</a>
                    <?php else: ?>
                        <a href="php/dashboard.php" class="btn btn-primary">Get Started</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="php/register.php" class="btn btn-primary">Get Started</a>
                <?php endif; ?>
                <a href="#features" class="btn btn-secondary">Learn More</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <h2 class="text-center mb-2">Why Choose Our Platform?</h2>
            <div class="dashboard-grid">
                <div class="card fade-in">
                    <i class="fas fa-graduation-cap fa-3x mb-1"></i>
                    <h3>Expert Instructors</h3>
                    <p>Learn from industry professionals with years of experience.</p>
                </div>
                <div class="card fade-in">
                    <i class="fas fa-laptop-code fa-3x mb-1"></i>
                    <h3>Interactive Learning</h3>
                    <p>Engage with interactive content and practical exercises.</p>
                </div>
                <div class="card fade-in">
                    <i class="fas fa-certificate fa-3x mb-1"></i>
                    <h3>Certification</h3>
                    <p>Earn certificates upon course completion.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Courses Section -->
    <section id="courses" class="courses">
        <div class="container">
            <h2 class="text-center mb-2">Featured Courses</h2>
            <div class="dashboard-grid">
                <?php
                $conn = getDBConnection();
                if ($conn) {
                    // Step 1: Get top 3 course_ids by enrollment count
                    $topCourses = [];
                    $result = $conn->query("SELECT course_id, COUNT(*) as enroll_count FROM enrollments GROUP BY course_id ORDER BY enroll_count DESC LIMIT 3");
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $topCourses[] = (int)$row['course_id'];
                        }
                    }
                    // Step 2: Fetch course details
                    if (count($topCourses) > 0) {
                        $ids = implode(',', $topCourses);
                        $query = "SELECT c.*, u.name as instructor_name FROM courses c JOIN users u ON c.instructor_id = u.id WHERE c.id IN ($ids)";
                        $result2 = $conn->query($query);
                        if ($result2 && $result2->num_rows > 0) {
                            while ($course = $result2->fetch_assoc()) {
                                echo '<div class="card course-card fade-in">';
                                echo '<img src="' . (!empty($course['thumbnail_url']) ? $course['thumbnail_url'] : 'images/default-course.jpg') . '" alt="' . htmlspecialchars($course['title']) . '">';
                                echo '<div class="course-card-content">';
                                echo '<h3>' . htmlspecialchars($course['title']) . '</h3>';
                                echo '<p>' . htmlspecialchars($course['description']) . '</p>';
                                echo '<p class="instructor">Instructor: ' . htmlspecialchars($course['instructor_name']) . '</p>';
                                echo '<a href="php/course.php?id=' . $course['id'] . '" class="btn btn-primary">View Course</a>';
                                echo '</div></div>';
                            }
                        }
                    } else {
                        echo '<div class="card"><p>No featured courses available.</p></div>';
                    }
                    $conn->close();
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials">
        <div class="container">
            <h2 class="text-center mb-2">What Our Students Say</h2>
            <div class="dashboard-grid review-grid">
                <?php
                $conn = getDBConnection();
                if ($conn) {
                    $sql = "SELECT r.review, r.created_at, u.name, u.profile_photo, c.title as course_title
                            FROM reviews r
                            JOIN users u ON r.student_id = u.id
                            LEFT JOIN courses c ON r.course_id = c.id
                            ORDER BY r.created_at DESC LIMIT 6";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $photo = $row['profile_photo'] ? $row['profile_photo'] : 'images/default-user.png';
                            echo '<div class="card review-card fade-in">';
                            echo '<div class="review-header">';
                            echo '<img class="review-photo" src="' . htmlspecialchars($photo) . '" alt="' . htmlspecialchars($row['name']) . '">';
                            echo '<div class="review-meta">';
                            echo '<span class="review-name">' . htmlspecialchars($row['name']) . '</span>';
                            if ($row['course_title']) {
                                echo '<span class="review-course">' . htmlspecialchars($row['course_title']) . '</span>';
                            }
                            echo '</div></div>';
                            echo '<div class="review-quote">';
                            echo '<i class="fas fa-quote-left"></i>';
                            echo '<p>"' . htmlspecialchars($row['review']) . '"</p>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="card"><p>No reviews yet. Be the first to leave a review!</p></div>';
                    }
                    $conn->close();
                }
                ?>
            </div>
        </div>
    </section>

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

    <script src="js/main.js"></script>
</body>
</html> 