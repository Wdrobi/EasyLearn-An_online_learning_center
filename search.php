<?php
session_start();
require_once 'php/config/database.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$courses = [];

if ($query !== '') {
    $conn = getDBConnection();
    if ($conn) {
        $stmt = $conn->prepare("SELECT c.*, u.name as instructor_name FROM courses c JOIN users u ON c.instructor_id = u.id WHERE c.title LIKE ? OR c.description LIKE ?");
        $like = '%' . $query . '%';
        $stmt->bind_param('ss', $like, $like);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - EasyLearn</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="container nav-content">
            <a href="index.php" class="logo"><img src="images/EasyLearn.png" alt="EasyLearn Logo" style="height:56px;vertical-align:middle;margin-right:10px;"></a>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="#features">Features</a>
                <a href="#courses">Courses</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="php/dashboard.php">Dashboard</a>
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
                <input type="text" name="q" placeholder="Search courses..." class="search-input" style="padding:6px 12px;border-radius:6px;border:1px solid #ccc;font-size:1rem;" value="<?php echo htmlspecialchars($query); ?>">
                <button type="submit" style="background:none;border:none;cursor:pointer;color:#4a90e2;font-size:1.2rem;"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </nav>
    <div class="container" style="margin-top: 100px;">
        <h2 class="text-center mb-2">Search Results for "<?php echo htmlspecialchars($query); ?>"</h2>
        <div class="dashboard-grid">
            <?php if ($query === ''): ?>
                <div class="card"><p>Please enter a search term.</p></div>
            <?php elseif (empty($courses)): ?>
                <div class="card"><p>No courses found matching your search.</p></div>
            <?php else: ?>
                <?php foreach ($courses as $course): ?>
                    <div class="card course-card fade-in">
                        <img src="<?php echo $course['thumbnail_url'] ?? 'images/default-course.jpg'; ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                        <div class="course-card-content">
                            <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                            <p><?php echo htmlspecialchars($course['description']); ?></p>
                            <p class="instructor">Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                            <a href="php/course.php?id=<?php echo $course['id']; ?>" class="btn btn-primary">View Course</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <script src="js/main.js"></script>
</body>
</html> 