<?php
session_start();
require "../requires/connect.php";
require '../libs/dompdf/autoload.inc.php';

use Dompdf\Dompdf;

// Get enrollment ID from URL
$enrollmentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Validate signature image path - FIXED PATH HANDLING
$imgPath = realpath(__DIR__ . '/signature.png');
if (!$imgPath) {
    die("Signature image path could not be resolved. Tried: " . __DIR__ . '/signature.png');
}
if (!file_exists($imgPath)) {
    die("Signature image not found at: " . $imgPath);
}
if (!is_readable($imgPath)) {
    die("Signature image exists but is not readable.");
}

$imgData = base64_encode(file_get_contents($imgPath));
$imgSrc = 'data:image/png;base64,' . $imgData;

// Fetch enrollment data
$stmt = $mysqli->prepare("
    SELECT ec.*, 
        u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
        c.name AS course_name, c.price AS course_price,
        pt.name AS payment_type, 
        ep.transitionId, ep.screenshot_path, ep.amount
    FROM enroll_course ec
    JOIN users u ON ec.userId = u.id
    JOIN courses c ON ec.courseId = c.id
    LEFT JOIN enroll_payment ep ON ep.enroll_courseId = ec.id
    LEFT JOIN payment_type pt ON ep.paymentTypeId = pt.id
    WHERE ec.id = ?
");
$stmt->bind_param("i", $enrollmentId);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) die("Enrollment not found.");

$html = '
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f7fa;
            color: #333;
            padding: 40px;
        }

        .receipt-card {
            max-width: 700px;
            margin: auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
            padding: 30px 40px;
            border: 1px solid #e3e8f0;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header .title {
            font-size: 26px;
            font-weight: bold;
            color: #2c3e50;
        }

        .header .subtitle {
            font-size: 18px;
            margin-top: 8px;
            color: #6c7a89;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .info-table td {
            padding: 12px 8px;
            border-bottom: 1px solid #e0e6ed;
        }

        .info-table td:first-child {
            font-weight: 600;
            width: 40%;
            color: #2f3640;
        }

        .info-table td:last-child {
            text-align: right;
            color: #555;
        }

        .signature {
            margin-top: 40px;
            text-align: right;
        }

        .signature img {
            height: 80px;
        }

        .signature-label {
            font-size: 12px;
            color: #777;
            margin-top: 5px;
        }

        .footer {
            text-align: center;
            font-size: 13px;
            color: #888;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <div class="receipt-card">
        <div class="header">
            <div class="title">Enrollment Receipt</div>
            <div class="subtitle">' . htmlspecialchars($data['course_name']) . '</div>
        </div>

        <table class="info-table">
            <tr><td>Name</td><td>' . htmlspecialchars($data['user_name']) . '</td></tr>
            <tr><td>Email</td><td>' . htmlspecialchars($data['user_email']) . '</td></tr>
            <tr><td>Phone</td><td>' . htmlspecialchars($data['user_phone']) . '</td></tr>
            <tr><td>Enrolled At</td><td>' . date("F j, Y h:i A", strtotime($data['enrolled_at'])) . '</td></tr>
            <tr><td>Payment Type</td><td style="text-transform: capitalize;">' . htmlspecialchars($data['payment_type'] ?? '-') . '</td></tr>
            <tr><td>Payment Amount</td><td>Ks ' . number_format($data['amount']) . '</td></tr>
            <tr><td>Status</td><td>' . ucfirst($data['payment_status']) . '</td></tr>
        </table>

        <div class="signature">
            <img src="' . $imgSrc . '" alt="Zeru">
            <div class="signature-label">Authorized Signature</div>
        </div>

        <div class="footer">Thank you for enrolling with us!</div>
    </div>
</body>
</html>
';

// Generate PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("receipt_{$enrollmentId}.pdf", ["Attachment" => true]);

// Clean output buffer
ob_clean();
$dompdf->stream("receipt_{$enrollmentId}.pdf", ["Attachment" => true]);
exit;