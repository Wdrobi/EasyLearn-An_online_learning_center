<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php'; // For mPDF

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header('Location: login.php'); exit();
}
if (!isset($_GET['course_id'])) { header('Location: student_dashboard.php'); exit(); }
$course_id = intval($_GET['course_id']);
$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Fetch course
$stmt = $conn->prepare('SELECT * FROM courses WHERE id = ?');
$stmt->bind_param('i', $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$course) { echo '<p>Course not found.</p>'; exit(); }

// Check if already has certificate
$stmt = $conn->prepare('SELECT * FROM certificates WHERE student_id = ? AND course_id = ?');
$stmt->bind_param('ii', $user_id, $course_id);
$stmt->execute();
$cert = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check eligibility: all lessons, assignments, and quizzes completed
$eligible = true;
// Lessons
$stmt = $conn->prepare('SELECT COUNT(*) as total FROM lessons WHERE course_id = ?');
$stmt->bind_param('i', $course_id);
$stmt->execute();
$total_lessons = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
$stmt = $conn->prepare('SELECT COUNT(*) as completed FROM lesson_progress WHERE student_id = ? AND lesson_id IN (SELECT id FROM lessons WHERE course_id = ?) AND completed = 1');
$stmt->bind_param('ii', $user_id, $course_id);
$stmt->execute();
$completed_lessons = $stmt->get_result()->fetch_assoc()['completed'];
$stmt->close();
if ($total_lessons == 0 || $completed_lessons < $total_lessons) $eligible = false;
// Assignments
$stmt = $conn->prepare('SELECT COUNT(*) as total FROM assignments WHERE course_id = ?');
$stmt->bind_param('i', $course_id);
$stmt->execute();
$total_assignments = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
$stmt = $conn->prepare('SELECT COUNT(*) as submitted FROM submissions WHERE student_id = ? AND assignment_id IN (SELECT id FROM assignments WHERE course_id = ?)');
$stmt->bind_param('ii', $user_id, $course_id);
$stmt->execute();
$submitted_assignments = $stmt->get_result()->fetch_assoc()['submitted'];
$stmt->close();
if ($total_assignments > 0 && $submitted_assignments < $total_assignments) $eligible = false;
// Quizzes
$stmt = $conn->prepare('SELECT COUNT(*) as total FROM quizzes WHERE course_id = ?');
$stmt->bind_param('i', $course_id);
$stmt->execute();
$total_quizzes = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
$stmt = $conn->prepare('SELECT COUNT(DISTINCT quiz_id) as taken FROM quiz_results WHERE student_id = ? AND quiz_id IN (SELECT id FROM quizzes WHERE course_id = ?) AND score >= 40');
$stmt->bind_param('ii', $user_id, $course_id);
$stmt->execute();
$taken_quizzes = $stmt->get_result()->fetch_assoc()['taken'];
$stmt->close();
if ($total_quizzes > 0 && $taken_quizzes < $total_quizzes) $eligible = false;

if (!$eligible) {
    echo '<h2>You are not eligible for a certificate yet. Complete all lessons, assignments, and quizzes (min 40% in quizzes).</h2>';
    exit();
}

// Generate unique certificate number
function generateCertNumber($user_id, $course_id) {
    return strtoupper(dechex($user_id) . '-' . dechex($course_id) . '-' . substr(md5($user_id . $course_id . time()), 0, 6));
}

// Generate certificate if not exists
if (!$cert) {
    $cert_number = generateCertNumber($user_id, $course_id);
    $cert_file = 'certificates/cert_' . $user_id . '_' . $course_id . '_' . $cert_number . '.pdf';
    $cert_url = $cert_file;
    // Generate PDF using mPDF
    $mpdf = new \Mpdf\Mpdf([
        'format' => 'A4-L',
        'margin_left' => 0,
        'margin_right' => 0,
        'margin_top' => 0,
        'margin_bottom' => 0,
        'defaultPagebreak' => 0
    ]);
    $html = '<style>
        @page { margin: 0; }
        body { margin: 0; }
        .cert-bg {
            width: 100%; height: 100%; background: #fff;
            border: 12px solid #d4af37; border-radius: 18px; box-sizing: border-box;
            display: flex; flex-direction: column; justify-content: space-between; align-items: stretch;
            page-break-after: avoid; page-break-before: avoid; page-break-inside: avoid;
        }
        .cert-content {
            flex: 1 1 auto;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 24px 40px 0 40px; box-sizing: border-box; text-align: center;
            font-family: Georgia, serif;
        }
        .cert-logo { height: 50px; margin-bottom: 12px; max-width: 200px; }
        .cert-title {
            font-size: 2.8rem; font-weight: bold; color: #1a237e; letter-spacing: 2px; margin-bottom: 0.3rem;
        }
        .cert-subtitle {
            font-size: 1.2rem; color: #232946; margin-bottom: 1.8rem; letter-spacing: 1px;
        }
        .cert-name {
            font-size: 2.2rem; color: #0d47a1; font-family: "Brush Script MT", cursive, sans-serif; margin: 1.2rem 0 0.3rem 0; font-weight: bold;
        }
        .cert-desc {
            font-size: 1rem; color: #232946; margin: 1.2rem 0 2rem 0; max-width: 75%; margin-left: auto; margin-right: auto;
        }
        .cert-no { margin-top: 1rem; font-size: 0.9rem; color: #888; }
        .cert-bottom-row {
            flex: 0 0 80px; 
            width: 100%; 
            display: flex; 
            flex-direction: row; 
            justify-content: space-between; 
            align-items: flex-end; 
            padding: 0 60px 20px 60px; 
            box-sizing: border-box;
            position: relative;
        }
        .cert-sign-block { 
            text-align: center; 
            width: 180px; 
            flex: 0 0 auto;
        }
        .cert-sign-block.right {
            position: absolute;
            right: 60px;
            bottom: 20px;
        }
        .cert-sign-block.left {
            position: absolute;
            left: 60px;
            bottom: 20px;
        }
        .cert-sign-block img { 
            height: 28px; 
            max-width: 90px; 
            object-fit: contain; 
            margin-bottom: 4px;
        }
        .cert-sign-name { 
            font-weight: bold; 
            color: #1a237e; 
            font-size: 0.85rem; 
            margin-top: 0.2rem; 
            line-height: 1.2;
        }
        .cert-sign-title { 
            font-size: 0.75rem; 
            color: #232946; 
            line-height: 1.2;
        }
        .cert-seal {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: 20px;
            background: #fff; 
            border-radius: 50%; 
            border: 3px solid #d4af37; 
            width: 42px; 
            height: 42px; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            box-shadow: 0 2px 8px rgba(80,120,200,0.07);
        }
        .cert-seal-inner {
            width: 30px; 
            height: 30px; 
            background: linear-gradient(135deg,#ffd700 0%,#fffbe7 100%); 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 1rem; 
            color: #bfa100; 
            font-weight: bold;
        }
    </style>';
    $html .= '<div class="cert-bg">
        <div class="cert-content">
            <img src="../images/EasyLearn.png" class="cert-logo" alt="Logo">
            <div class="cert-title">CERTIFICATE</div>
            <div class="cert-subtitle">OF ACHIEVEMENT</div>
            <div style="font-size:1.1rem;color:#232946;margin-bottom:1.5rem;">THIS CERTIFICATE IS PROUDLY PRESENTED TO</div>
            <div class="cert-name">' . htmlspecialchars($_SESSION['user_name']) . '</div>
            <div class="cert-desc">For successfully completing the course <b>' . htmlspecialchars($course['title']) . '</b> on EasyLearn. We recognize your dedication and achievement.</div>
            <div class="cert-no">Certificate No: <strong>' . $cert_number . '</strong> &nbsp; | &nbsp; Date: ' . date('M d, Y') . '</div>
        </div>
        <div class="cert-bottom-row">
            <div class="cert-sign-block left">
                <img src="../images/ceo-sign.png" alt="CEO Signature">
                <div class="cert-sign-name">Md. Robiul Islam</div>
                <div class="cert-sign-title">CEO & Founder</div>
            </div>
            <div class="cert-seal"><div class="cert-seal-inner"><span>&#x1F396;</span></div></div>
            <div class="cert-sign-block right">
                <img src="../images/cofounder-sign.png" alt="Co-Founder Signature">
                <div class="cert-sign-name">Ashrafun Nahar Arifa</div>
                <div class="cert-sign-title">Co-Founder</div>
            </div>
        </div>
    </div>';
    $mpdf->WriteHTML($html);
    if (!file_exists('../certificates')) { mkdir('../certificates', 0777, true); }
    $mpdf->Output('../' . $cert_file, \Mpdf\Output\Destination::FILE);
    // Store in DB
    $stmt = $conn->prepare('INSERT INTO certificates (student_id, course_id, certificate_url) VALUES (?, ?, ?)');
    $stmt->bind_param('iis', $user_id, $course_id, $cert_url);
    $stmt->execute();
    $stmt->close();
    // Reload cert
    $cert = [ 'certificate_url' => $cert_url, 'issued_on' => date('Y-m-d H:i:s'), 'id' => $conn->insert_id, 'student_id' => $user_id, 'course_id' => $course_id ];
}

// Download as PDF, JPG, or PNG
$download = $_GET['download'] ?? '';
if ($download === 'pdf') {
     header('Content-Type: application/pdf');
     header('Content-Disposition: attachment; filename="certificate.pdf"');
     readfile('../' . $cert['certificate_url']);
     exit();
} 
//elseif ($download === 'jpg' || $download === 'png') {
//     // Convert PDF to image (requires Imagick)
//     $pdf_path = '../' . $cert['certificate_url'];
//     if (!extension_loaded('imagick')) {
//         echo '<h2 style="color:red;">Image download is not available: Imagick PHP extension is not installed or enabled on the server.</h2>';
//         exit();
//     }
//     try {
//         $img = new Imagick();
//         $img->setResolution(300, 300);
//         $img->readImage($pdf_path.'[0]');
//         $img->setImageFormat($download);
//         header('Content-Type: image/' . $download);
//         header('Content-Disposition: attachment; filename="certificate.' . $download . '"');
//         echo $img;
//         $img->clear();
//         $img->destroy();
//         exit();
//     } catch (Exception $e) {
//         echo '<h2 style="color:red;">Failed to convert certificate to image: ' . htmlspecialchars($e->getMessage()) . '</h2>';
//         exit();
//     }
// }

if ($download === 'jpg' || $download === 'png') {
    // Convert PDF to image (requires Imagick)
    $pdf_path = '../' . $cert['certificate_url'];
    if (!extension_loaded('imagick')) {
        echo '<h2 style="color:red;">Image download is not available: Imagick PHP extension is not installed or enabled on the server.</h2>';
        exit();
    }
    try {
        $img = new Imagick();
        $img->setResolution(300, 300);
        $img->readImage($pdf_path.'[0]');
        $img->setImageFormat($download);
        header('Content-Type: image/' . $download);
        header('Content-Disposition: attachment; filename="certificate.' . $download . '"');
        echo $img;
        $img->clear();
        $img->destroy();
        exit();
    } catch (Exception $e) {
        echo '<h2 style="color:red;">Failed to convert certificate to image: ' . htmlspecialchars($e->getMessage()) . '</h2>';
        exit();
    }
}
elseif ($download === 'jpg' || $download === 'png') {
    $pdf_path = '../' . $cert['certificate_url'];
    $absolute = realpath($pdf_path);

    if (!extension_loaded('imagick')) {
        header('Content-Type: text/plain');
        echo 'Image download is not available: Imagick PHP extension is not installed or enabled on the server.';
        exit();
    }

    if ($absolute === false || !file_exists($absolute)) {
        header('Content-Type: text/plain');
        echo "File does not exist at: $absolute";
        exit();
    }

    try {
        $img = new Imagick();
        $img->setResolution(300, 300);
        $img->readImage($absolute . '[0]');
        $img->setImageFormat($download);
        header('Content-Type: image/' . $download);
        header('Content-Disposition: attachment; filename="certificate.' . $download . '"');
        echo $img;
        $img->clear();
        $img->destroy();
        exit();
    } catch (Exception $e) {
        header('Content-Type: text/plain');
        echo "Failed to convert certificate to image: " . $e->getMessage();
        exit();
    }
}

// Show certificate preview and download options
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - <?php echo htmlspecialchars($course['title']); ?></title>
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
        </div>
    </nav>
<div class="container" style="margin-top: 30px;">
    <div class="dashboard card" style="padding:2rem 2rem 2rem 2rem; max-width:900px; margin:auto;">
        <h1>Certificate of Completion</h1>
        <iframe src="../<?php echo $cert['certificate_url']; ?>" style="width:100%;height:500px;border:1px solid #ccc;"></iframe>
        <div style="margin-top:2em;">
            <a href="certificate.php?course_id=<?php echo $course_id; ?>&download=pdf" class="btn btn-primary">Download PDF</a>
            <a href="certificate.php?course_id=<?php echo $course_id; ?>&download=jpg" class="btn btn-secondary">Download JPG</a>
            <a href="certificate.php?course_id=<?php echo $course_id; ?>&download=png" class="btn btn-secondary">Download PNG</a>
        </div>
        <div style="margin-top:1em;font-size:1.1rem;">Certificate No: <strong><?php echo htmlspecialchars(basename($cert['certificate_url'], '.pdf')); ?></strong></div>
        <a href="student_dashboard.php" class="btn btn-link" style="margin-top:2em;">Back to Dashboard</a>
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