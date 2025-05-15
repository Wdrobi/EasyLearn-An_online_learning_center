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

// Get course ID from URL
$course_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$course_id) {
    header('Location: instructor_dashboard.php');
    exit();
}

// Verify course ownership
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->bind_param("ii", $course_id, $_SESSION['user_id']);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    header('Location: instructor_dashboard.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    
    // Validate input
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Course title is required";
    }
    
    if (empty($description)) {
        $errors[] = "Course description is required";
    }
    
    // Handle thumbnail upload
    $thumbnail_url = $course['thumbnail_url'];
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
                // Delete old thumbnail if exists
                if ($course['thumbnail_url'] && file_exists('../' . $course['thumbnail_url'])) {
                    unlink('../' . $course['thumbnail_url']);
                }
                $thumbnail_url = 'uploads/course_thumbnails/' . $file_name;
            } else {
                $errors[] = "Failed to upload thumbnail.";
            }
        }
    }
    
    if (empty($errors)) {
        // Update course in database
        $stmt = $conn->prepare("UPDATE courses SET title = ?, description = ?, thumbnail_url = ? WHERE id = ? AND instructor_id = ?");
        if (!$stmt) {
            $errors[] = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("sssii", $title, $description, $thumbnail_url, $course_id, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $success = "Course updated successfully";
                $course['title'] = $title;
                $course['description'] = $description;
                $course['thumbnail_url'] = $thumbnail_url;
            } else {
                $errors[] = "Failed to update course: " . $stmt->error;
            }
            
            $stmt->close();
        }
    }
}

// Get course lessons
$stmt = $conn->prepare("
    SELECT * FROM lessons 
    WHERE course_id = ? 
    ORDER BY lesson_order ASC
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$lessons = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course - Learning Management System</title>
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
                <a href="create_course.php">Create Course</a>
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
            <h1>Edit Course: <?php echo htmlspecialchars($course['title']); ?></h1>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="card mt-2">
                <form action="edit_course.php?id=<?php echo $course_id; ?>" method="POST" enctype="multipart/form-data" class="course-form">
                    <div class="form-group">
                        <label for="title">Course Title</label>
                        <input type="text" id="title" name="title" class="form-control" required
                               value="<?php echo htmlspecialchars($course['title']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="description">Course Description</label>
                        <textarea id="description" name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($course['description']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="thumbnail">Course Thumbnail</label>
                        <?php if ($course['thumbnail_url']): ?>
                            <div class="current-thumbnail">
                                <img src="../<?php echo htmlspecialchars($course['thumbnail_url']); ?>" 
                                     alt="Current thumbnail" style="max-width: 200px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" id="thumbnail" name="thumbnail" class="form-control" accept="image/*">
                        <small class="form-text">Recommended size: 800x450 pixels. Maximum file size: 5MB.</small>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="instructor_dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>

            <!-- Lessons Section -->
            <section class="lessons mt-2">
                <div class="section-header">
                    <h2>Course Lessons</h2>
                    <button class="btn btn-primary" onclick="showAddLessonModal()">
                        <i class="fas fa-plus"></i> Add Lesson
                    </button>
                </div>

                <div class="lessons-list">
                    <?php if ($lessons->num_rows > 0): ?>
                        <div class="card">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="lessonsTableBody">
                                    <?php while ($lesson = $lessons->fetch_assoc()): ?>
                                        <tr data-lesson-id="<?php echo $lesson['id']; ?>">
                                            <td>
                                                <span class="lesson-order"><?php echo $lesson['lesson_order']; ?></span>
                                                <div class="order-controls">
                                                    <button class="btn-icon" onclick="moveLesson(<?php echo $lesson['id']; ?>, 'up')">
                                                        <i class="fas fa-arrow-up"></i>
                                                    </button>
                                                    <button class="btn-icon" onclick="moveLesson(<?php echo $lesson['id']; ?>, 'down')">
                                                        <i class="fas fa-arrow-down"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($lesson['title']); ?></td>
                                            <td><?php echo ucfirst($lesson['content_type']); ?></td>
                                            <td>
                                                <button class="btn-icon" onclick="editLesson(<?php echo $lesson['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-icon" onclick="deleteLesson(<?php echo $lesson['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <p>No lessons added yet. Click "Add Lesson" to create your first lesson.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <!-- Add/Edit Lesson Modal -->
    <div id="lessonModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitle">Add New Lesson</h2>
            <form id="lessonForm" action="lesson_process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                <input type="hidden" name="lesson_id" id="lessonId">
                
                <div class="form-group">
                    <label for="lessonTitle">Lesson Title</label>
                    <input type="text" id="lessonTitle" name="title" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="contentType">Content Type</label>
                    <select id="contentType" name="content_type" class="form-control" required>
                        <option value="video">Video</option>
                        <option value="text">Text</option>
                    </select>
                </div>

                <div class="form-group" id="videoContentGroup">
                    <label for="videoUrl">Video URL</label>
                    <input type="url" id="videoUrl" name="content_url" class="form-control">
                    <small class="form-text">Enter a YouTube or Vimeo video URL</small>
                </div>

                <div class="form-group" id="textContentGroup" style="display: none;">
                    <label for="textContent">Lesson Content</label>
                    <textarea id="textContent" name="content" class="form-control" rows="10"></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Lesson</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
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
        // Modal functionality
        const modal = document.getElementById('lessonModal');
        const closeBtn = document.getElementsByClassName('close')[0];

        function showAddLessonModal() {
            document.getElementById('modalTitle').textContent = 'Add New Lesson';
            document.getElementById('lessonForm').reset();
            document.getElementById('lessonId').value = '';
            modal.style.display = 'block';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        closeBtn.onclick = closeModal;
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }

        // Content type toggle
        document.getElementById('contentType').addEventListener('change', function(e) {
            const videoGroup = document.getElementById('videoContentGroup');
            const textGroup = document.getElementById('textContentGroup');
            
            if (e.target.value === 'video') {
                videoGroup.style.display = 'block';
                textGroup.style.display = 'none';
            } else {
                videoGroup.style.display = 'none';
                textGroup.style.display = 'block';
            }
        });

        // Lesson management functions
        function editLesson(lessonId) {
            // Fetch lesson data and populate modal
            fetch(`get_lesson.php?id=${lessonId}`)
                .then(response => response.json())
                .then(lesson => {
                    document.getElementById('modalTitle').textContent = 'Edit Lesson';
                    document.getElementById('lessonId').value = lesson.id;
                    document.getElementById('lessonTitle').value = lesson.title;
                    document.getElementById('contentType').value = lesson.content_type;
                    
                    if (lesson.content_type === 'video') {
                        document.getElementById('videoUrl').value = lesson.content_url;
                        document.getElementById('videoContentGroup').style.display = 'block';
                        document.getElementById('textContentGroup').style.display = 'none';
                    } else {
                        document.getElementById('textContent').value = lesson.content;
                        document.getElementById('videoContentGroup').style.display = 'none';
                        document.getElementById('textContentGroup').style.display = 'block';
                    }
                    
                    modal.style.display = 'block';
                });
        }

        function deleteLesson(lessonId) {
            if (confirm('Are you sure you want to delete this lesson?')) {
                fetch('lesson_process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete&lesson_id=${lessonId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to delete lesson: ' + data.error);
                    }
                });
            }
        }

        function moveLesson(lessonId, direction) {
            fetch('lesson_process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=move&lesson_id=${lessonId}&direction=${direction}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to move lesson: ' + data.error);
                }
            });
        }
    </script>
</body>
</html>
<?php
$conn->close();
?> 