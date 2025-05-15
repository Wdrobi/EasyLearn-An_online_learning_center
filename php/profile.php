<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($name) || empty($email)) {
        $error_message = "Name and email are required fields.";
    } else {
        // Check if email is already taken by another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error_message = "Email is already taken by another user.";
        } else {
            // Get current user data
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $current_user = $stmt->get_result()->fetch_assoc();
            
            // Handle password change if requested
            if (!empty($current_password)) {
                if (password_verify($current_password, $current_user['password'])) {
                    if (empty($new_password) || empty($confirm_password)) {
                        $error_message = "New password and confirmation are required.";
                    } elseif ($new_password !== $confirm_password) {
                        $error_message = "New passwords do not match.";
                    } else {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
                        $stmt->bind_param("sssi", $name, $email, $hashed_password, $user_id);
                    }
                } else {
                    $error_message = "Current password is incorrect.";
                }
            } else {
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $stmt->bind_param("ssi", $name, $email, $user_id);
            }
            
            if (empty($error_message)) {
                if ($stmt->execute()) {
                    $success_message = "Profile updated successfully!";
                    $_SESSION['user_name'] = $name;
                } else {
                    $error_message = "Failed to update profile. Please try again.";
                }
            }
        }
    }
}

// Get current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - EasyLearn</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container nav-content">
            <a href="../index.php" class="logo"><img src="../images/EasyLearn.png" alt="EasyLearn Logo" style="height:56px;vertical-align:middle;margin-right:10px;"></a>
            <div class="nav-links">
                <?php if ($_SESSION['user_role'] === 'instructor'): ?>
                    <a href="instructor_dashboard.php">Dashboard</a>
                    <a href="my_courses.php">My Courses</a>
                    <a href="create_course.php">Create Course</a>
                <?php else: ?>
                    <a href="student_dashboard.php">Dashboard</a>
                    <a href="my_courses.php">My Courses</a>
                    <a href="certificates.php">Certificates</a>
                <?php endif; ?>
                <a href="profile.php" class="active">Profile</a>
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
            <h1>My Profile</h1>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <img src="<?php echo !empty($user['profile_photo']) ? '../' . $user['profile_photo'] : '../images/default-avatar.jpg'; ?>" 
                             alt="Profile Photo" class="avatar-image">
                        <div class="avatar-overlay">
                            <label for="profile-photo" class="avatar-upload">
                                <i class="fas fa-camera"></i>
                            </label>
                            <input type="file" id="profile-photo" accept="image/*" style="display: none;">
                        </div>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                        <p class="user-role"><i class="fas fa-user-tie"></i> <?php echo ucfirst($user['role']); ?></p>
                        <p class="join-date">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
                
                <form method="POST" class="profile-form">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" placeholder="Enter current password to change">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Enter new password">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
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
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('profile-photo').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const formData = new FormData();
                formData.append('profile_photo', e.target.files[0]);
                fetch('upload_profile_photo.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector('.avatar-image').src = data.url;
                    } else {
                        alert('Failed to upload profile photo. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while uploading the photo.');
                });
            }
        });
    });
    </script>
</body>
</html>
<?php $conn->close(); ?> 