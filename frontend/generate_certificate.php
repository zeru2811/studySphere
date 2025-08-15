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


// ----------- CREATE CERTIFICATE IMAGE -------------
$width  = 1200;
$height = 848;

// Create white background
$canvas = imagecreatetruecolor($width, $height);
$white  = imagecolorallocate($canvas, 255, 255, 255);
imagefilledrectangle($canvas, 0, 0, $width, $height, $white);

// Load background and scale it
$backgroundPath = __DIR__ . '/../assets/img/certificate-bg.png';
if (file_exists($backgroundPath)) {
    $bg = imagecreatefrompng($backgroundPath);
    $bgWidth  = imagesx($bg);
    $bgHeight = imagesy($bg);
    imagecopyresampled($canvas, $bg, 0, 0, 0, 0, $width, $height, $bgWidth, $bgHeight);
    imagedestroy($bg);
}
$image = $canvas;

// Text colors
$titleColor    = imagecolorallocate($image, 50, 50, 50);
$subtitleColor = imagecolorallocate($image, 80, 80, 80);
$nameColor     = imagecolorallocate($image, 20, 40, 80);
$highlight     = imagecolorallocate($image, 139, 0, 0);

// Fonts
$titleFont   = __DIR__ . '/../assets/fonts/Open_Sans/static/OpenSans-Bold.ttf';
$bodyFont    = __DIR__ . '/../assets/fonts/Open_Sans/static/OpenSans-Regular.ttf';

// Helper function to center text
function centerText($img, $text, $font, $size, $y, $color) {
    $bbox = imagettfbbox($size, 0, $font, $text);
    $textWidth = $bbox[2] - $bbox[0];
    $x = (imagesx($img) - $textWidth) / 2;
    imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
}

// Title
centerText($image, "Certificate of Completion", $titleFont, 54, 200, $highlight);

// Subtitle
centerText($image, "This is to certify that", $bodyFont, 24, 270, $subtitleColor);

// Name
centerText($image, strtoupper($user['name']), $titleFont, 42, 350, $nameColor);

// Course description
centerText($image, "has successfully completed the course:", $bodyFont, 24, 410, $subtitleColor);

// Course name
centerText($image, $course['name'], $titleFont, 30, 470, $highlight);

// Certificate ID
centerText($image, "Certificate ID: $certificateId", $bodyFont, 18, 530, $subtitleColor);

// Date
centerText($image, "Issued on: " . date("F j, Y"), $bodyFont, 18, 560, $subtitleColor);

// Signature
$signaturePath = __DIR__ . '/../assets/signatures/signature.png';
if (file_exists($signaturePath)) {
    $signature = imagecreatefrompng($signaturePath);
    $sigWidth  = imagesx($signature);
    $sigHeight = imagesy($signature);
    imagecopyresampled($image, $signature, 500, 600, 0, 0, 200, 80, $sigWidth, $sigHeight);
    imagedestroy($signature);
}

// Founder label
centerText($image, "Zeru, Founder", $bodyFont, 18, 700, $subtitleColor);

// Output
header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="certificate_'.$userId.'_'.$courseId.'.png"');
imagepng($image);
imagedestroy($image);
?>
