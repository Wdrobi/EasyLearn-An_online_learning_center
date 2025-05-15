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

// Get all certificates with course details
$stmt = $conn->prepare("
    SELECT c.title as course_title, cert.*, u.name as instructor_name
    FROM certificates cert
    JOIN courses c ON cert.course_id = c.id
    JOIN users u ON c.instructor_id = u.id
    WHERE cert.student_id = ?
    ORDER BY cert.issued_on DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$certificates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Certificates - EasyLearn</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-content">
            <a href="../index.php" class="logo"><img src="../images/EasyLearn.png" alt="EasyLearn Logo" style="height:56px;vertical-align:middle;margin-right:10px;"></a>
            <div class="nav-links">
                <a href="student_dashboard.php">Dashboard</a>
                <a href="my_courses.php">My Courses</a>
                <a href="certificates.php" class="active">Certificates</a>
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
            <h1>My Certificates</h1>
            
            <?php if (empty($certificates)): ?>
                <div class="card">
                    <p>You haven't earned any certificates yet.</p>
                    <a href="my_courses.php" class="btn btn-primary">View My Courses</a>
                </div>
            <?php else: ?>
                <div class="dashboard-grid">
                    <?php foreach ($certificates as $cert): ?>
                        <div class="card certificate-card fade-in">
                            <div class="certificate-icon">
                                <i class="fas fa-certificate fa-3x" style="color: #ffd54f;"></i>
                            </div>
                            <div class="certificate-content">
                                <h3><?php echo htmlspecialchars($cert['course_title']); ?></h3>
                                <p class="instructor">Instructor: <?php echo htmlspecialchars($cert['instructor_name']); ?></p>
                                <p class="issue-date">Issued on: <?php echo date('F d, Y', strtotime($cert['issued_on'])); ?></p>
                                <p class="certificate-id">Certificate ID: <?php echo htmlspecialchars($cert['id']); ?></p>
                                
                                <div class="certificate-actions">
                                    <a href="../<?php echo htmlspecialchars($cert['certificate_url']); ?>" class="btn btn-primary" target="_blank">
                                        <i class="fas fa-eye"></i> View Certificate
                                    </a>
                                    <a href="../<?php echo htmlspecialchars($cert['certificate_url']); ?>" class="btn btn-secondary" download>
                                        <i class="fas fa-download"></i> Download PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
<?php
$conn->close();
?> 