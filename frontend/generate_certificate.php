<?php
session_start();
require '../requires/connect.php';
require '../requires/common_function.php';

// Check authentication and course ID
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    die("Invalid course ID");
}

$courseId = (int)$_GET['course_id'];
$userId   = (int)$_SESSION['id'];

// Verify completion
$completedQuery = $mysqli->prepare("
    SELECT COUNT(DISTINCT l.id) as completed_lessons,
           (SELECT COUNT(DISTINCT l2.id) 
            FROM lessons l2
            JOIN course_subject cs2 ON l2.course_subject_id = cs2.id
            WHERE cs2.courseId = ?) as total_lessons
    FROM lesson_completions lc
    JOIN lessons l ON lc.lesson_id = l.id
    JOIN course_subject cs ON l.course_subject_id = cs.id
    WHERE cs.courseId = ? AND lc.user_id = ?
");
$completedQuery->bind_param("iii", $courseId, $courseId, $userId);
$completedQuery->execute();
$result = $completedQuery->get_result()->fetch_assoc();

if (!$result || $result['completed_lessons'] < $result['total_lessons']) {
    $_SESSION['error'] = "You must complete all lessons to download the certificate.";
    header("Location: subject.php?id=$courseId");
    exit();
}

// Get course and user details
$courseQuery = $mysqli->prepare("SELECT name FROM courses WHERE id = ?");
$courseQuery->bind_param("i", $courseId);
$courseQuery->execute();
$course = $courseQuery->get_result()->fetch_assoc();

$userQuery = $mysqli->prepare("SELECT name FROM users WHERE id = ?");
$userQuery->bind_param("i", $userId);
$userQuery->execute();
$user = $userQuery->get_result()->fetch_assoc();

// Check if certificate exists or create new one
$certQuery = $mysqli->prepare("SELECT certificate_id FROM certificates WHERE user_id = ? AND course_id = ?");
$certQuery->bind_param("ii", $userId, $courseId);
$certQuery->execute();
$certificate = $certQuery->get_result()->fetch_assoc();

if (!$certificate) {
    $certificateId = 'CERT-' . uniqid() . '-' . bin2hex(random_bytes(2));
    $insertCert = $mysqli->prepare("
        INSERT INTO certificates (certificate_id, user_id, course_id, issue_date)
        VALUES (?, ?, ?, NOW())
    ");
    $insertCert->bind_param("sii", $certificateId, $userId, $courseId);
    $insertCert->execute();
} else {
    $certificateId = $certificate['certificate_id'];
}

// Log the certificate download
$logQuery = $mysqli->prepare("
    INSERT INTO certificate_logs (certificate_id, user_id, course_id, download_date)
    VALUES (?, ?, ?, NOW())
");
$logQuery->bind_param("sii", $certificateId, $userId, $courseId);
$logQuery->execute();

// ----------- CREATE CERTIFICATE IMAGE WITH GD -------------
$width  = 1200;
$height = 848;

// Load background image (PNG or JPG) - transparent border design works best
$backgroundPath = __DIR__ . '/../assets/certificate_bg.png'; // replace with your image path
if (file_exists($backgroundPath)) {
    $image = imagecreatefrompng($backgroundPath);
} else {
    $image = imagecreatetruecolor($width, $height);
    $bgColor = imagecolorallocate($image, 249, 247, 232);
    imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
}

// Text colors
$textColor   = imagecolorallocate($image, 51, 51, 51);
$redColor    = imagecolorallocate($image, 139, 0, 0);

// Fonts
$titleFont   = __DIR__ . '/../assets/fonts/Open_Sans/static/OpenSans-Bold.ttf';
$bodyFont    = __DIR__ . '/../assets/fonts/Open_Sans/static/OpenSans-Regular.ttf';

// Title
imagettftext($image, 48, 0, 280, 180, $redColor, $titleFont, "Certificate of Completion");

// Subtitle
imagettftext($image, 22, 0, 460, 230, $textColor, $bodyFont, "This is to certify that");

// Name
imagettftext($image, 38, 0, 380, 300, $textColor, $titleFont, $user['name']);

// Course line
imagettftext($image, 22, 0, 390, 360, $textColor, $bodyFont, "has successfully completed the course:");

// Course name
imagettftext($image, 28, 0, 340, 410, $redColor, $titleFont, $course['name']);

// Certificate ID & Date
imagettftext($image, 18, 0, 400, 500, $textColor, $bodyFont, "Certificate ID: $certificateId");
imagettftext($image, 18, 0, 430, 530, $textColor, $bodyFont, "Issued on: " . date("F j, Y"));

// Add signature
$signaturePath = __DIR__ . '/../assets/signatures/signature.png'; // transparent PNG of Zeru's signature
if (file_exists($signaturePath)) {
    $signature = imagecreatefrompng($signaturePath);
    $sigWidth  = imagesx($signature);
    $sigHeight = imagesy($signature);
    imagecopyresampled($image, $signature, 450, 580, 0, 0, 200, 80, $sigWidth, $sigHeight);
    imagedestroy($signature);
}

// Add "Founder" label
imagettftext($image, 18, 0, 500, 680, $textColor, $bodyFont, "Zeru, Founder");

// Output
header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="certificate_'.$userId.'_'.$courseId.'.png"');
imagepng($image);
imagedestroy($image);
?>
