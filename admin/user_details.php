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
$stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
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
    SELECT ec.id, ec.payment_status, ec.enrolled_at, ep.amount as payment_amount,
           c.name as course_name, c.price as course_price
    FROM enroll_course ec
    JOIN courses c ON ec.courseId = c.id
    LEFT JOIN enroll_payment ep ON ep.enroll_courseId = ec.id
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pagetitle) ?></title>
    <style>
        .card-3d {
            transform-style: preserve-3d;
            perspective: 1000px;
            transition: all 0.3s ease;
        }
        .card-3d:hover {
            transform: translateY(-5px) rotateX(5deg);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .profile-3d {
            transform-style: preserve-3d;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        .profile-3d:hover {
            transform: translateY(-3px) rotateY(5deg);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #f9f9ff 0%, #f0f4ff 100%);
        }
        .status-badge {
            position: relative;
            overflow: hidden;
        }
        .status-badge:after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(to bottom right, rgba(255,255,255,0.3), rgba(255,255,255,0));
            transform: rotate(30deg);
        }
        .course-card {
            transition: all 0.3s ease;
            background: linear-gradient(to right, #ffffff 0%, #f8fafc 100%);
        }
        .course-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="gradient-bg w-full min-h-screen p-4 md:p-6">
    <div class="max-w-7xl w-full pt-10 mx-auto px-2">
        <!-- User Information Section -->
        <div class="card-3d bg-white rounded-2xl shadow-xl border border-gray-100 p-6 mb-8">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">User Profile</h1>
                    <p class="text-gray-500 mt-1">Detailed information about the user</p>
                </div>
                <div class="flex space-x-3">
                    <a href="#" class="px-4 py-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-all">
                        <i class="fas fa-edit mr-2"></i>Edit
                    </a>
                </div>
            </div>

            <?php if (empty($user)): ?>
                <div class="text-center py-12">
                    <div class="mx-auto w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-user-slash text-gray-400 text-3xl"></i>
                    </div>
                    <p class="text-gray-600 text-lg">User not found</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Profile Column -->
                    <div class="lg:col-span-1">
                        <div class="profile-3d bg-white rounded-xl p-6 text-center">
                            <?php if (!empty($user['profile_photo'])): ?>
                                <img src="../uploads/profiles/<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile Photo" class="w-32 h-32 rounded-full mx-auto mb-4 border-4 border-white shadow-md object-cover">
                            <?php else: ?>
                                <div class="bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-full w-32 h-32 flex items-center justify-center mx-auto mb-4 shadow-md">
                                    <span class="text-4xl font-bold"><?= htmlspecialchars(substr($user['name'], 0, 2)) ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <h2 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($user['name']) ?></h2>
                            <p class="text-gray-600 mb-4"><?= htmlspecialchars($user['email']) ?></p>
                            
                            <div class="flex justify-center mb-4">
                                <span class="status-badge px-3 py-1 rounded-full text-sm font-medium 
                                    <?= $user['status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= $user['status'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </div>
                            
                            <div class="border-t border-gray-100 pt-4">
                                <p class="text-gray-600 mb-2"><i class="fas fa-phone-alt mr-2 text-blue-500"></i> <?= htmlspecialchars($user['phone']) ?></p>
                                <p class="text-gray-600">
                                    <i class="fas fa-user-tag mr-2 text-purple-500"></i> 
                                    <?php 
                                    if ($user['role_id'] == 1) {
                                        echo 'Admin';
                                    } elseif ($user['role_id'] == 2) {
                                        echo 'teacher';
                                    } elseif ($user['role_id'] == 3) {
                                        echo 'Student';
                                    }else {
                                        echo 'External User';
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Details Column -->
                    <div class="lg:col-span-2">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Basic Info Card -->
                            <div class="course-card bg-white rounded-xl p-6 border border-gray-100">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                    <i class="fas fa-info-circle text-blue-500 mr-2"></i> Basic Information
                                </h3>
                                <div class="space-y-3">
                                    <div>
                                        <p class="text-sm text-gray-500">Joined Date</p>
                                        <p class="font-medium"><?= date('M j, Y', strtotime($user['created_at'])) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Last Updated</p>
                                        <p class="font-medium"><?= date('M j, Y', strtotime($user['updated_at'])) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Member Since</p>
                                        <p class="font-medium"><?= max(0, floor((time() - strtotime($user['created_at'])) / (60 * 60 * 24))) ?> days</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Notes Card -->
                            <div class="course-card bg-white rounded-xl p-6 border border-gray-100">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                    <i class="fas fa-sticky-note text-yellow-500 mr-2"></i> Notes
                                </h3>
                                <div class="bg-yellow-50 p-4 rounded-lg">
                                    <?php if (!empty($user['note'])): ?>
                                        <p class="text-gray-700"><?= htmlspecialchars($user['note']) ?></p>
                                    <?php else: ?>
                                        <p class="text-gray-400 italic">No notes available</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Stats Section -->
                        <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-indigo-50 rounded-xl p-4 text-center">
                                <p class="text-sm text-indigo-600 font-medium">Enrollments</p>
                                <p class="text-2xl font-bold text-indigo-800"><?= count($enrollments) ?></p>
                            </div>
                            <div class="bg-green-50 rounded-xl p-4 text-center">
                                <p class="text-sm text-green-600 font-medium">Paid</p>
                                <p class="text-2xl font-bold text-green-800">
                                    <?= count(array_filter($enrollments, function($e) { return $e['payment_status'] === 'paid'; })) ?>
                                </p>
                            </div>
                            <div class="bg-yellow-50 rounded-xl p-4 text-center">
                                <p class="text-sm text-yellow-600 font-medium">Pending</p>
                                <p class="text-2xl font-bold text-yellow-800">
                                    <?= count(array_filter($enrollments, function($e) { return $e['payment_status'] === 'pending'; })) ?>
                                </p>
                            </div>
                            <div class="bg-purple-50 rounded-xl p-4 text-center">
                                <p class="text-sm text-purple-600 font-medium">Total Spent</p>
                                <p class="text-2xl font-bold text-purple-800">
                                    Ks<?= number_format(array_sum(array_column($enrollments, 'payment_amount'))) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Enrolled Courses Section -->
        <div class="card-3d bg-white rounded-2xl shadow-xl border border-gray-100 p-6 mt-8">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Enrolled Courses</h2>
                    <p class="text-gray-500 mt-1">All courses this user has enrolled in</p>
                </div>
                <!-- <button class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-100 transition-all">
                    <i class="fas fa-download mr-2"></i>Export
                </button> -->
            </div>
            
            <?php if (empty($enrollments)): ?>
                <div class="text-center py-12">
                    <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-book-open text-gray-400 text-3xl"></i>
                    </div>
                    <p class="text-gray-600 text-lg">No courses enrolled yet</p>
                    <p class="text-gray-500 mt-1">This user hasn't enrolled in any courses</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrolled At</th>
                                <!-- <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th> -->
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($enrollments as $enrollment): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-book text-indigo-600"></i>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($enrollment['course_name']) ?></div>
                                                <div class="text-sm text-gray-500">Course ID: <?= $enrollment['id'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 font-medium">Ks<?= number_format($enrollment['payment_amount']) ?></div>
                                        <div class="text-xs text-gray-500"><?= $enrollment['payment_amount'] < $enrollment['course_price'] ? 'Discount applied' : 'Full price' ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?= $enrollment['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 
                                               ($enrollment['payment_status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                            <i class="fas
                                                <?= $enrollment['payment_status'] === 'paid' ? 'fa-check-circle' : 
                                                   ($enrollment['payment_status'] === 'pending' ? 'fa-clock' : 'fa-times-circle') ?> 
                                                me-1 mt-1"></i>
                                            <?= ucfirst($enrollment['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('M j, Y', strtotime($enrollment['enrolled_at'])) ?>
                                        <div class="text-xs text-gray-400"><?= date('h:i A', strtotime($enrollment['enrolled_at'])) ?></div>
                                    </td>
                                    <!-- <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button class="text-indigo-600 hover:text-indigo-900 mr-3"><i class="fas fa-eye"></i></button>
                                        <button class="text-yellow-600 hover:text-yellow-900"><i class="fas fa-edit"></i></button>
                                    </td> -->
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination would go here -->
                <div class="mt-6 flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                        Showing <span class="font-medium">1</span> to <span class="font-medium"><?= count($enrollments) ?></span> of <span class="font-medium"><?= count($enrollments) ?></span> results
                    </div>
                    <div class="flex space-x-2">
                        <button class="px-3 py-1 bg-gray-100 text-gray-600 rounded-md hover:bg-gray-200">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="px-3 py-1 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            1
                        </button>
                        <button class="px-3 py-1 bg-gray-100 text-gray-600 rounded-md hover:bg-gray-200">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 justify-center items-center">
      <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-lg">
        <form action="./components/user_edit.php" method="POST">
          <input type="hidden" name="id" value="<?= $user['id'] ?>">

          <div class="mb-4">
            <label class="block text-sm font-medium">Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" class="w-full border rounded px-3 py-2" required>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium">Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="w-full border rounded px-3 py-2" required>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium">Phone</label>
            <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" class="w-full border rounded px-3 py-2">
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium">Note</label>
            <textarea name="note" class="w-full border rounded px-3 py-2"><?= htmlspecialchars($user['note']) ?></textarea>
          </div>

          <div class="flex justify-end space-x-3 mt-6">
            <button type="button" id="cancelEdit" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
    
    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/your-code.js" crossorigin="anonymous"></script>
    <script>
    document.querySelector('a[href="#"][class*="text-blue-600"]').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('editModal').classList.remove('hidden');
        document.getElementById('editModal').classList.add('flex');
    });

    document.getElementById('cancelEdit').addEventListener('click', function() {
        document.getElementById('editModal').classList.remove('flex');
        document.getElementById('editModal').classList.add('hidden');
    });
    </script>

</body>
</html>