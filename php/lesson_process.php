<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Student lesson view: must come before instructor-only check
if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    isset($_SESSION['user_id']) &&
    $_SESSION['user_role'] === 'student' &&
    isset($_GET['lesson_id']) && isset($_GET['course_id'])
) {
    $user_id = $_SESSION['user_id'];
    $lesson_id = intval($_GET['lesson_id']);
    $course_id = intval($_GET['course_id']);
    $conn = getDBConnection();
    // Fetch lesson
    $stmt = $conn->prepare('SELECT * FROM lessons WHERE id = ? AND course_id = ?');
    $stmt->bind_param('ii', $lesson_id, $course_id);
    $stmt->execute();
    $lesson = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$lesson) { echo '<p>Lesson not found.</p>'; exit(); }
    // Check progress
    $stmt = $conn->prepare('SELECT completed FROM lesson_progress WHERE student_id = ? AND lesson_id = ?');
    $stmt->bind_param('ii', $user_id, $lesson_id);
    $stmt->execute();
    $progress = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $completed = $progress ? $progress['completed'] : 0;
    // Mark as completed
    if (isset($_GET['mark_completed']) && !$completed) {
        $stmt = $conn->prepare('INSERT INTO lesson_progress (student_id, lesson_id, completed, completed_at) VALUES (?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE completed=1, completed_at=NOW()');
        $stmt->bind_param('ii', $user_id, $lesson_id);
        $stmt->execute();
        $stmt->close();
        header('Location: lesson_process.php?lesson_id=' . $lesson_id . '&course_id=' . $course_id);
        exit();
    }
    // Fetch next lesson
    $stmt = $conn->prepare('SELECT id FROM lessons WHERE course_id = ? AND lesson_order > (SELECT lesson_order FROM lessons WHERE id = ?) ORDER BY lesson_order ASC LIMIT 1');
    $stmt->bind_param('ii', $course_id, $lesson_id);
    $stmt->execute();
    $next = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($lesson['title']); ?> - Lesson</title>
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
            <h1><?php echo htmlspecialchars($lesson['title']); ?></h1>
            <?php if ($lesson['content_type'] === 'video'): ?>
                <div style="margin: 1rem 0;">
                    <iframe width="560" height="315" src="<?php echo htmlspecialchars($lesson['content_url']); ?>" frameborder="0" allowfullscreen></iframe>
                </div>
            <?php else: ?>
                <div class="lesson-content" style="margin: 1rem 0; padding: 1.5rem; background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(80,120,200,0.07); font-size:1.15rem; color: #222;">
                    <?php 
                    $text = !empty($lesson['content']) ? $lesson['content'] : $lesson['content_url'];
                    echo nl2br(htmlspecialchars($text)); 
                    ?>
                </div>
            <?php endif; ?>
            <div style="margin-top:2.5rem; display:flex; gap:1rem; flex-wrap:wrap; align-items:center;">
                <?php if (!$completed): ?>
                    <a href="lesson_process.php?lesson_id=<?php echo $lesson_id; ?>&course_id=<?php echo $course_id; ?>&mark_completed=1" class="btn btn-success">Mark as Completed</a>
                <?php else: ?>
                    <span class="alert alert-success">Lesson Completed</span>
                <?php endif; ?>
                <a href="course.php?id=<?php echo $course_id; ?>" class="btn btn-secondary">Back to Course</a>
                <?php if ($next): ?>
                    <a href="lesson_process.php?lesson_id=<?php echo $next['id']; ?>&course_id=<?php echo $course_id; ?>" class="btn btn-primary">Next Lesson</a>
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
    body { background: linear-gradient(120deg, #181f2a 0%, #232946 100%); }
    .lesson-content { font-size: 1.15rem; background: #fff; color: #222; }
    </style>
    </body>
    </html>
    <?php exit(); }

// Check if user is logged in and is an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// Handle different actions
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add':
    case 'edit':
        handleAddEdit($conn);
        break;
    case 'delete':
        handleDelete($conn);
        break;
    case 'move':
        handleMove($conn);
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

function handleAddEdit($conn) {
    $lesson_id = filter_input(INPUT_POST, 'lesson_id', FILTER_VALIDATE_INT);
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $content_type = filter_input(INPUT_POST, 'content_type', FILTER_SANITIZE_STRING);
    
    // Validate input
    if (!$course_id || empty($title) || empty($content_type)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit();
    }
    
    // Verify course ownership
    $stmt = $conn->prepare("SELECT id FROM courses WHERE id = ? AND instructor_id = ?");
    $stmt->bind_param("ii", $course_id, $_SESSION['user_id']);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized access to course']);
        exit();
    }
    
    // Get content based on type
    if ($content_type === 'video') {
        $content = filter_input(INPUT_POST, 'content_url', FILTER_SANITIZE_URL);
    } else {
        $content = filter_input(INPUT_POST, 'content', FILTER_SANITIZE_STRING);
    }
    
    if (empty($content)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Content is required']);
        exit();
    }
    
    if ($lesson_id) {
        // Update existing lesson
        $stmt = $conn->prepare("UPDATE lessons SET title = ?, content_type = ?, content = ? WHERE id = ? AND course_id = ?");
        $stmt->bind_param("sssii", $title, $content_type, $content, $lesson_id, $course_id);
    } else {
        // Get the next lesson order
        $stmt = $conn->prepare("SELECT MAX(lesson_order) as max_order FROM lessons WHERE course_id = ?");
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $next_order = ($result['max_order'] ?? 0) + 1;
        
        // Insert new lesson
        $stmt = $conn->prepare("INSERT INTO lessons (course_id, title, content_type, content, lesson_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $course_id, $title, $content_type, $content, $next_order);
    }
    
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
    }
}

function handleDelete($conn) {
    $lesson_id = filter_input(INPUT_POST, 'lesson_id', FILTER_VALIDATE_INT);
    
    if (!$lesson_id) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid lesson ID']);
        exit();
    }
    
    // Verify lesson ownership through course
    $stmt = $conn->prepare("
        SELECT l.id 
        FROM lessons l 
        JOIN courses c ON l.course_id = c.id 
        WHERE l.id = ? AND c.instructor_id = ?
    ");
    $stmt->bind_param("ii", $lesson_id, $_SESSION['user_id']);
    $stmt->execute();
    
    if (!$stmt->get_result()->fetch_assoc()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized access to lesson']);
        exit();
    }
    
    // Get the lesson order before deleting
    $stmt = $conn->prepare("SELECT course_id, lesson_order FROM lessons WHERE id = ?");
    $stmt->bind_param("i", $lesson_id);
    $stmt->execute();
    $lesson = $stmt->get_result()->fetch_assoc();
    
    // Delete the lesson
    $stmt = $conn->prepare("DELETE FROM lessons WHERE id = ?");
    $stmt->bind_param("i", $lesson_id);
    
    if ($stmt->execute()) {
        // Reorder remaining lessons
        $stmt = $conn->prepare("
            UPDATE lessons 
            SET lesson_order = lesson_order - 1 
            WHERE course_id = ? AND lesson_order > ?
        ");
        $stmt->bind_param("ii", $lesson['course_id'], $lesson['lesson_order']);
        $stmt->execute();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
    }
}

function handleMove($conn) {
    $lesson_id = filter_input(INPUT_POST, 'lesson_id', FILTER_VALIDATE_INT);
    $direction = filter_input(INPUT_POST, 'direction', FILTER_SANITIZE_STRING);
    
    if (!$lesson_id || !in_array($direction, ['up', 'down'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
        exit();
    }
    
    // Get current lesson info
    $stmt = $conn->prepare("
        SELECT l.id, l.course_id, l.lesson_order 
        FROM lessons l 
        JOIN courses c ON l.course_id = c.id 
        WHERE l.id = ? AND c.instructor_id = ?
    ");
    $stmt->bind_param("ii", $lesson_id, $_SESSION['user_id']);
    $stmt->execute();
    $lesson = $stmt->get_result()->fetch_assoc();
    
    if (!$lesson) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized access to lesson']);
        exit();
    }
    
    // Get the lesson to swap with
    $new_order = $direction === 'up' ? $lesson['lesson_order'] - 1 : $lesson['lesson_order'] + 1;
    $stmt = $conn->prepare("
        SELECT id, lesson_order 
        FROM lessons 
        WHERE course_id = ? AND lesson_order = ?
    ");
    $stmt->bind_param("ii", $lesson['course_id'], $new_order);
    $stmt->execute();
    $swap_lesson = $stmt->get_result()->fetch_assoc();
    
    if (!$swap_lesson) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Cannot move lesson further']);
        exit();
    }
    
    // Swap the lesson orders
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("UPDATE lessons SET lesson_order = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_order, $lesson['id']);
        $stmt->execute();
        
        $stmt->bind_param("ii", $lesson['lesson_order'], $swap_lesson['id']);
        $stmt->execute();
        
        $conn->commit();
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

$conn->close();
?> 