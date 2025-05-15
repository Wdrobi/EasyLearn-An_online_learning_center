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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $instructor_id = $_SESSION['user_id'];
    $lessons = isset($_POST['lessons']) && is_array($_POST['lessons']) ? $_POST['lessons'] : [];
    
    // Validate input
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Course title is required";
    }
    
    if (empty($description)) {
        $errors[] = "Course description is required";
    }
    
    // Handle thumbnail upload
    $thumbnail_url = null;
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['thumbnail']['type'], $allowed_types)) {
            $errors[] = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
        }
        
        if ($_FILES['thumbnail']['size'] > $max_size) {
            $errors[] = "File size too large. Maximum size is 5MB.";
        }
        
        if (empty($errors)) {
            $upload_dir = '../uploads/course_thumbnails/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $target_path)) {
                $thumbnail_url = 'uploads/course_thumbnails/' . $file_name;
            } else {
                $errors[] = "Failed to upload thumbnail.";
            }
        }
    }
    
    if (empty($errors)) {
        // Insert course into database
        $stmt = $conn->prepare("INSERT INTO courses (instructor_id, title, description, thumbnail_url) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            $errors[] = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("isss", $instructor_id, $title, $description, $thumbnail_url);
            
            if ($stmt->execute()) {
                $course_id = $stmt->insert_id;
                // Insert lessons if any
                if (!empty($lessons) && is_array($lessons)) {
                    $order = 1;
                    $lesson_stmt = $conn->prepare("INSERT INTO lessons (course_id, title, content, lesson_order) VALUES (?, ?, ?, ?)");
                    foreach ($lessons as $lesson) {
                        $lesson_title = trim($lesson['title']);
                        $lesson_content = trim($lesson['content']);
                        if ($lesson_title !== '') {
                            $lesson_stmt->bind_param("issi", $course_id, $lesson_title, $lesson_content, $order);
                            $lesson_stmt->execute();
                            $order++;
                        }
                    }
                    $lesson_stmt->close();
                }
                header("Location: edit_course.php?id=" . $course_id);
                exit();
            } else {
                $errors[] = "Failed to create course: " . $stmt->error;
            }
            
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Course - Learning Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-content">
            <a href="../index.php" class="logo"><img src="../images/EasyLearn.png" alt="EasyLearn Logo" style="height:48px;vertical-align:middle;margin-right:10px;"></a>
            <div class="nav-links">
                <a href="instructor_dashboard.php">Dashboard</a>
                <a href="my_courses.php">My Courses</a>
                <a href="create_course.php" class="active">Create Course</a>
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
            <h1>Create New Course</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card mt-2">
                <form action="create_course.php" method="POST" enctype="multipart/form-data" class="course-form">
                    <div class="form-group">
                        <label for="title">Course Title</label>
                        <input type="text" id="title" name="title" class="form-control" required
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="description">Course Description</label>
                        <textarea id="description" name="description" class="form-control" rows="5" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="thumbnail">Course Thumbnail</label>
                        <input type="file" id="thumbnail" name="thumbnail" class="form-control" accept="image/*">
                        <small class="form-text">Recommended size: 800x450 pixels. Maximum file size: 5MB.</small>
                        <div id="thumbnail-preview"></div>
                    </div>

                    <div class="form-group">
                        <label>Lessons</label>
                        <div id="lessons-list"></div>
                        <button type="button" class="btn btn-info" id="add-lesson-btn"><i class="fas fa-plus"></i> Add Lesson</button>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Course
                        </button>
                        <a href="instructor_dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
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
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var thumbnailInput = document.getElementById('thumbnail');
        if (thumbnailInput) {
            thumbnailInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                const previewContainer = document.getElementById('thumbnail-preview');
                previewContainer.innerHTML = '';
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = document.createElement('img');
                        preview.src = e.target.result;
                        preview.style.maxWidth = '200px';
                        preview.style.marginTop = '10px';
                        previewContainer.appendChild(preview);
                    }
                    reader.readAsDataURL(file);
                }
            });
        }
    });

    // Dynamic lessons
    let lessonCount = 0;
    document.getElementById('add-lesson-btn').onclick = function() {
        lessonCount++;
        const lessonDiv = document.createElement('div');
        lessonDiv.className = 'lesson-item card mt-1';
        lessonDiv.innerHTML = `
            <div class="form-group">
                <label>Lesson Title</label>
                <input type="text" name="lessons[${lessonCount}][title]" class="form-control">
            </div>
            <div class="form-group">
                <label>Lesson Content</label>
                <textarea name="lessons[${lessonCount}][content]" class="form-control" rows="3"></textarea>
            </div>
            <button type="button" class="btn btn-danger remove-lesson-btn">Remove</button>
        `;
        document.getElementById('lessons-list').appendChild(lessonDiv);
        lessonDiv.querySelector('.remove-lesson-btn').onclick = function() {
            lessonDiv.remove();
        };
    };
    </script>
</body>
</html>
<?php
$conn->close();
?> 