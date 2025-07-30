<?php
session_start();
require "../requires/common.php";
require "../requires/connect.php";
require "../requires/title.php";
$pagetitle = "User Details";

// Get user ID from URL
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch user details
$user = [];
$stmt = $mysqli->prepare("SELECT id, name, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
}
$stmt->close();

// Fetch user's enrolled courses
$enrollments = [];
$stmt = $mysqli->prepare("
    SELECT ec.id, ec.payment_status, ec.enrolled_at, 
           c.name as course_name, c.price as course_price
    FROM enroll_course ec
    JOIN courses c ON ec.courseId = c.id
    WHERE ec.userId = ?
    ORDER BY ec.enrolled_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $enrollments[] = $row;
}
$stmt->close();

require './templates/admin_header.php';
require './templates/admin_sidebar.php';
?>

<body class="bg-gray-50 w-full min-h-screen p-4 md:p-6">
    <div class="max-w-6xl w-full pt-10 mx-auto">
        <!-- User Information Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">User Details</h1>
                </div>
                
            </div>

            <?php if (empty($user)): ?>
                <div class="text-center py-8">
                    <p class="text-gray-500">User not found.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 mb-2">Basic Information</h2>
                        <div class="space-y-2">
                            <p><span class="text-gray-600">Name:</span> <?= htmlspecialchars($user['name']) ?></p>
                            <p><span class="text-gray-600">Email:</span> <?= htmlspecialchars($user['email']) ?></p>
                            <p><span class="text-gray-600">Phone:</span> <?= htmlspecialchars($user['phone']) ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Enrolled Courses Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Enrolled Courses</h2>
            
            <?php if (empty($enrollments)): ?>
                <div class="text-center py-8">
                    <p class="text-gray-500">This user hasn't enrolled in any courses yet.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrolled At</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($enrollments as $enrollment): ?>
                                <tr>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($enrollment['course_name']) ?></div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                        Ks<?= number_format($enrollment['course_price'], 2) ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?= $enrollment['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 
                                               ($enrollment['payment_status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                            <?= ucfirst($enrollment['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('M j, Y h:i A', strtotime($enrollment['enrolled_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>