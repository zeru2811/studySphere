<?php
session_start();
require "requires/common.php";
require "requires/title.php";
require "requires/connect.php";


$statsTotalUsers = $mysqli->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$activeUsers = $mysqli->query("SELECT COUNT(*) as total FROM users WHERE status = 1")->fetch_assoc()['total'];
$pendingTeachers = $mysqli->query("SELECT COUNT(*) as total FROM users WHERE role_id = 2 AND status = 0")->fetch_assoc()['total'];
$students = $mysqli->query("SELECT COUNT(*) as total FROM users WHERE role_id = 3")->fetch_assoc()['total'];

function getRoleName($role_id) {
    switch ($role_id) {
        case 1: return 'Admin';
        case 2: return 'Teacher';
        case 3: return 'Student';
        case 4: return 'External User';
        default: return 'Unknown';
    }
}

$baseURL = strtok($_SERVER["REQUEST_URI"], '?');
$queryParams = $_GET;
unset($queryParams['page']);
$baseQuery = http_build_query($queryParams);

$roleClasses = [
    1 => 'bg-purple-100 text-purple-800',
    2 => 'bg-blue-100 text-blue-800',
    3 => 'bg-green-100 text-green-800',
    4 => 'bg-gray-100 text-gray-800'
];

$perPage = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// Role Filter
$roleFilter = isset($_GET['role']) && $_GET['role'] != 'all' ? intval($_GET['role']) : null;
$whereClause = $roleFilter ? "WHERE role_id = $roleFilter" : '';

// Pagination counts
$totalUsersResult = $mysqli->query("SELECT COUNT(*) as total FROM users $whereClause");
$totalUsers = $totalUsersResult->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $perPage);

// Pagination range
$maxLinksToShow = 5;
$half = floor($maxLinksToShow / 2);
$start = max(1, $page - $half);
$end = min($totalPages, $start + $maxLinksToShow - 1);
if ($end - $start + 1 < $maxLinksToShow) {
    $start = max(1, $end - $maxLinksToShow + 1);
}

// Fetch users with filter + pagination
$usersSql = "SELECT * FROM users $whereClause ORDER BY id DESC LIMIT $perPage OFFSET $offset";
$userTableResult = $mysqli->query($usersSql);

// Actions: Delete, Toggle, Edit
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $userId = intval($_GET['id']);

    if ($action === 'delete' && $userId > 0) {
        $stmt1 = $mysqli->prepare("DELETE FROM password_token WHERE userId = ?");
        $stmt1->bind_param("i", $userId);
        $stmt1->execute();

        $stmt2 = $mysqli->prepare("DELETE FROM users WHERE id = ?");
        $stmt2->bind_param("i", $userId);
        $stmt2->execute();

        $_SESSION['message'] = "User deleted successfully.";
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit;
    }

    if ($action === 'toggle' && $userId > 0) {
        $stmt = $mysqli->prepare("UPDATE users SET status = IF(status=1, 0, 1), updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $_SESSION['message'] = "User status updated.";
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit;
    }

    if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $role_id = intval($_POST['role_id']);
        $stmt = $mysqli->prepare("UPDATE users SET name=?, email=?, role_id=?, updated_at = NOW() WHERE id=?");
        $stmt->bind_param("ssii", $name, $email, $role_id, $userId);
        $stmt->execute();
        $_SESSION['message'] = "User updated successfully.";
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit;
    }
}


if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $escaped = $mysqli->real_escape_string($q);

    // Optional: Add pagination on search
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = 5;
    $offset = ($page - 1) * $perPage;

    $countSql = "SELECT COUNT(*) as total FROM users WHERE name LIKE '%$escaped%' OR email LIKE '%$escaped%'";
    $countResult = $mysqli->query($countSql);
    $totalUsers = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalUsers / $perPage);

    $sql = "SELECT * FROM users WHERE name LIKE '%$escaped%' OR email LIKE '%$escaped%' ORDER BY id DESC LIMIT $offset, $perPage";
    $result = $mysqli->query($sql);

    ob_start(); // Start output buffer for table body
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            ?>
            <tr>
                <td class="py-4 px-6">
                    <div class="flex items-center">
                        <div class="bg-purple-500 text-white rounded-full w-10 h-10 flex items-center justify-center">
                            <span><?= htmlspecialchars(substr($row['name'], 0, 2)) ?></span>
                        </div>
                        <div class="ml-4">
                            <div class="font-medium text-gray-900"><?= htmlspecialchars($row['name']) ?></div>
                            <div class="text-gray-500 text-sm"><?= htmlspecialchars($row['email']) ?></div>
                        </div>
                    </div>
                </td>
                <td class="py-4 px-6 hidden sm:table-cell">
                    <span class="role-badge <?= $roleClasses[$row['role_id']] ?? 'bg-gray-100 text-gray-800' ?>">
                        <?= htmlspecialchars(getRoleName($row['role_id'])) ?>
                    </span>
                </td>
                <td class="py-4 px-6 hidden md:table-cell">
                    <?php if ($row['status'] == 1): ?>
                        <span class="status-badge bg-green-100 text-green-800">Active</span>
                    <?php else: ?>
                        <span class="status-badge bg-red-500 text-white">Inactive</span>
                    <?php endif; ?>
                </td>
                <td class="py-4 px-6 hidden md:table-cell">
                    <div class="text-gray-900"><?= date('M d Y', strtotime($row['created_at'])) ?></div>
                    <div class="text-gray-500 text-sm"><?= htmlspecialchars(getRoleName($row['role_id'])) ?></div>
                </td>
                <td class="py-4 px-6">
                    <div class="user-actions flex space-x-2">
                        <a href="?action=edit&id=<?= $row['id'] ?>" class="text-blue-600 hover:text-blue-900" title="Edit">
                            <i class="fas fa-edit"></i><span class="sr-only">Edit</span>
                        </a>
                        <a href="?action=toggle&id=<?= $row['id'] ?>" class="text-gray-600 hover:text-gray-900 open-toggle-modal" title="Toggle Status">
                            <i class="fas <?= $row['status'] == 1 ? 'fa-lock-open' : 'fa-lock' ?>"></i><span class="sr-only">Toggle</span>
                        </a>
                        <?php if ($row['role_id'] != 1): ?>
                        <a href="?action=delete&id=<?= $row['id'] ?>" class="text-red-600 hover:text-red-900 open-delete-modal" title="Delete">
                            <i class="fas fa-trash"></i><span class="sr-only">Delete</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php
        }
    } else {
        echo '<tr><td colspan="5" class="text-center py-4 text-gray-500">No users found.</td></tr>';
    }
    $body = ob_get_clean();

    ob_start(); // Start output buffer for footer
    ?>
    <div class="bg-light px-6 py-4 flex flex-col md:flex-row items-center justify-between">
        <div class="text-sm text-gray-700 mb-4 md:mb-0">
            Showing <span class="font-semibold"><?= $offset + 1 ?> - <?= min($offset + $perPage, $totalUsers) ?></span> of <span class="font-semibold"><?= $totalUsers ?></span> users
        </div>
        <div class="pagination flex items-center space-x-2">
            <?php if ($page > 1): ?>
                <a href="#" data-page="1" class="page-link px-3 py-1 border rounded text-gray-600 hover:bg-gray-50">&laquo;</a>
                <a href="#" data-page="<?= $page - 1 ?>" class="page-link px-3 py-1 border rounded text-gray-600 hover:bg-gray-50">&lsaquo;</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="#" data-page="<?= $i ?>" class="page-link px-3 py-1 <?= $i == $page ? 'bg-blue-600 text-white' : 'border text-gray-600 hover:bg-gray-50' ?> rounded"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="#" data-page="<?= $page + 1 ?>" class="page-link px-3 py-1 border rounded text-gray-600 hover:bg-gray-50">&rsaquo;</a>
                <a href="#" data-page="<?= $totalPages ?>" class="page-link px-3 py-1 border rounded text-gray-600 hover:bg-gray-50">&raquo;</a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    $footer = ob_get_clean();

    echo json_encode([
        'body' => $body,
        'footer' => $footer,
    ]);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | StudySphere Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4f46e5',
                        secondary: '#818cf8',
                        dark: '#1e293b',
                        light: '#f1f5f9'
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        
        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar {
            transition: all 0.3s ease;
        }
        
        .user-table tr {
            transition: background-color 0.2s ease;
        }
        
        .user-table tr:hover {
            background-color: #f1f5f9;
        }
        
        .role-badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .tabs .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .tabs .tab.active {
            border-bottom: 2px solid #4f46e5;
            color: #4f46e5;
        }
        
        .stat-card {
            border-left: 4px solid;
        }
        
        /* Mobile menu toggle */
        .mobile-menu-button {
            display: none;
        }

        .modal {
            transition: opacity 0.3s ease;
        }
        .modal-content {
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }
        .modal.active {
            opacity: 1;
            pointer-events: auto;
        }
        .modal.active .modal-content {
            transform: translateY(0);
        }
        
        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .sidebar {
                position: fixed;
                left: -100%;
                top: 0;
                bottom: 0;
                z-index: 50;
                transition: left 0.3s ease;
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .mobile-menu-button {
                display: block;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .tabs {
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 10px;
            }
            
            .tabs .tab {
                padding: 8px 12px;
                font-size: 0.875rem;
            }
            
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .header-actions {
                width: 100%;
                margin-top: 1rem;
            }
            
            .search-input {
                width: 100%;
            }
        }
        
        @media (max-width: 768px) {
            .stat-cards {
                grid-template-columns: 1fr 1fr;
            }
            
            .role-permissions {
                grid-template-columns: 1fr;
            }
            
            .user-table th, .user-table td {
                padding: 0.5rem;
                font-size: 0.875rem;
            }
            
            .user-actions {
                flex-direction: column;
                gap: 0.25rem;
            }
            
            .user-actions button {
                padding: 0.25rem;
            }
        }
        
        @media (max-width: 640px) {
            .stat-cards {
                grid-template-columns: 1fr;
            }
            
            .table-footer {
                flex-direction: column;
                gap: 1rem;
            }
            
            .pagination {
                flex-wrap: wrap;
                justify-content: center;
            }
        }

        @keyframes slide-in {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .animate-slide-in {
            animation: slide-in 0.3s ease-out forwards;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-button fixed top-4 left-4 z-50 p-2 rounded-md bg-primary text-white lg:hidden">
        <i class="fas fa-bars"></i>
    </button>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="sidebar bg-white w-64 shadow-lg py-6 flex flex-col lg:relative">
            <div class="px-6 mb-8">
                <h1 class="text-2xl font-bold text-primary flex items-center">
                    <i class="fas fa-graduation-cap mr-2"></i>
                    StudySphere
                </h1>
                <p class="text-xs text-gray-500 mt-1">Learning & Career Platform</p>
            </div>
            
            <nav class="flex-1">
                <div class="px-4 mb-4">
                    <h3 class="text-xs uppercase text-gray-500 font-semibold tracking-wider">Navigation</h3>
                </div>
                <a href="#" class="block py-3 px-6 bg-primary text-white">
                    <i class="fas fa-users mr-3"></i> User Management
                </a>
                <a href="#" class="block py-3 px-6 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-book mr-3"></i> Courses
                </a>
                <a href="#" class="block py-3 px-6 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-chart-line mr-3"></i> Analytics
                </a>
                <a href="#" class="block py-3 px-6 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-comments mr-3"></i> Discussions
                </a>
                <a href="#" class="block py-3 px-6 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-cog mr-3"></i> Settings
                </a>
                
                <div class="px-4 mt-8 mb-4">
                    <h3 class="text-xs uppercase text-gray-500 font-semibold tracking-wider">Account</h3>
                </div>
                <a href="#" class="block py-3 px-6 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-user mr-3"></i> My Profile
                </a>
                <a href="#" class="block py-3 px-6 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-sign-out-alt mr-3"></i> Logout
                </a>
            </nav>
            
            <div class="px-6 mt-auto">
                <div class="bg-light rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="bg-secondary rounded-full p-2">
                            <i class="fas fa-headset text-white text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium">Need Help?</p>
                            <p class="text-xs text-gray-600">Contact our support team</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 main-content">
            <!-- Header -->
            <header class="bg-white shadow-sm py-4 px-6">
                <div class="header-content flex flex-col lg:flex-row lg:justify-between lg:items-center">
                    <div>
                        <h2 class="text-xl font-semibold text-dark">User Management</h2>
                        <p class="text-sm text-gray-600">Manage all platform users and permissions</p>
                    </div>
                    
                    <div class="header-actions flex items-center mt-4 lg:mt-0">
                        <div class="relative mr-4 flex-1">
                            <input type="text" id="searchInput" placeholder="Search users..." class="search-input pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent w-full">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        
                        <div class="relative">
                            <button class="flex items-center focus:outline-none">
                                <div class="bg-primary text-white rounded-full w-8 h-8 flex items-center justify-center">
                                    <span>AM</span>
                                </div>
                                <span class="ml-2 font-medium text-gray-700 hidden sm:inline">Admin</span>
                                <i class="fas fa-chevron-down ml-2 text-gray-500 hidden sm:inline"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Stats Cards -->
            <div class="stat-cards grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 px-6 mt-6">
                <div class="stat-card bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm text-gray-600">Total Users</p>
                            <p class="text-2xl font-bold mt-1"><?= $statsTotalUsers ?></p>
                        </div>
                        <div class="bg-blue-100 rounded-full p-3">
                            <i class="fas fa-users text-blue-600"></i>
                        </div>
                    </div>
                    
                </div>
                
                <div class="stat-card bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm text-gray-600">Active Users</p>
                            <p class="text-2xl font-bold mt-1"><?= $activeUsers ?></p>
                        </div>
                        <div class="bg-purple-100 rounded-full p-3">
                            <i class="fas fa-user-check text-purple-600"></i>
                        </div>
                    </div>
                    
                </div>
                
                <div class="stat-card bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm text-gray-600">Pending Teachers</p>
                            <p class="text-2xl font-bold mt-1"><?= $pendingTeachers ?></p>
                        </div>
                        <div class="bg-yellow-100 rounded-full p-3">
                            <i class="fas fa-user-clock text-yellow-600"></i>
                        </div>
                    </div>
                    
                </div>
                
                <div class="stat-card bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm text-gray-600">Students</p>
                            <p class="text-2xl font-bold mt-1"><?= $students ?></p>
                        </div>
                        <div class="bg-green-100 rounded-full p-3">
                            <i class="fas fa-graduation-cap text-green-600"></i>
                        </div>
                    </div>
                    
                </div>
            </div>
            
            <!-- Tabs and Controls -->
            <div class="bg-white shadow-sm rounded-lg mx-6 mt-6 p-4">
                <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4">
                    <!-- Tabs -->
                    <div class="tabs flex space-x-2 border-b border-gray-200 mb-4" id="userTabs">
                        <a href="?role=all" class="tab <?= !isset($_GET['role']) || $_GET['role'] == 'all' ? 'active' : '' ?>">All Users</a>
                        <a href="?role=3" class="tab <?= isset($_GET['role']) && $_GET['role'] == '3' ? 'active' : '' ?>">Students</a>
                        <a href="?role=2" class="tab <?= isset($_GET['role']) && $_GET['role'] == '2' ? 'active' : '' ?>">Teachers</a>
                        <a href="?role=4" class="tab <?= isset($_GET['role']) && $_GET['role'] == '4' ? 'active' : '' ?>">External</a>
                    </div>

                    <!-- Buttons -->
                    <div class="flex space-x-3 mt-4">
                        <button id="openModal" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            <i class="fas fa-plus mr-2"></i>Add User
                        </button>
                    </div>

                    <!-- Modal Form -->
                    <div id="userModal" class="fixed z-10 inset-0 overflow-y-auto hidden bg-black bg-opacity-50">
                      <div class="flex items-center justify-center min-h-screen px-4">
                        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-8 relative">
                          <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-semibold text-gray-800">Add New User</h2>
                            <button id="closeModal" class="text-gray-500 hover:text-gray-700 text-xl">
                              <i class="fas fa-times"></i>
                            </button>
                          </div>

                          <form id="addUserForm" method="POST" action="add_user.php" class="space-y-5">
                            <div>
                              <label class="block text-sm font-medium text-gray-700 mb-1" for="name">Name</label>
                              <input name="name" id="name" class="w-full border border-gray-300 p-2.5 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Enter full name" required>
                            </div>

                            <div>
                              <label class="block text-sm font-medium text-gray-700 mb-1" for="email">Email</label>
                              <input name="email" id="email" type="email" class="w-full border border-gray-300 p-2.5 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="example@email.com" required>
                            </div>

                            <div>
                              <label class="block text-sm font-medium text-gray-700 mb-1" for="phone">Phone</label>
                              <input name="phone" id="phone" class="w-full border border-gray-300 p-2.5 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="+959...">
                            </div>

                            <div>
                              <label class="block text-sm font-medium text-gray-700 mb-1" for="password">Password</label>
                              <input name="password" id="password" type="password" class="w-full border border-gray-300 p-2.5 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="••••••••" required>
                            </div>

                            <div>
                              <label class="block text-sm font-medium text-gray-700 mb-1" for="role_id">Role</label>
                              <select name="role_id" id="role_id" class="w-full border border-gray-300 p-2.5 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="1">Student</option>
                                <option value="2">Teacher</option>
                                <option value="3">External</option>
                              </select>
                            </div>

                            <div class="flex justify-end space-x-3 pt-4">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-md shadow-sm transition duration-150">
                                    Save
                                </button>
                                <button type="button" id="closeModalBtn" class="text-gray-600 hover:text-gray-800 px-5 py-2.5 rounded-md border border-gray-300 transition">
                                    Cancel
                                </button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>

                </div>
            </div>
            
            <!-- User Table -->
            <div class="bg-white shadow rounded-lg mx-6 my-6 overflow-hidden">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="fixed top-4 right-4 z-50">
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg shadow-lg transform transition-all duration-300 ease-in-out animate-slide-in" role="alert">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></p>
                                </div>
                                <button class="ml-auto -mx-1.5 -my-1.5 bg-green-100 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex h-8 w-8" onclick="this.parentElement.parentElement.remove()">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="fixed top-4 right-4 z-50">
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-lg transform transition-all duration-300 ease-in-out animate-slide-in" role="alert">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
                                </div>
                                <button class="ml-auto -mx-1.5 -my-1.5 bg-red-100 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex h-8 w-8" onclick="this.parentElement.parentElement.remove()">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full user-table">
                        <thead class="bg-light">
                            <tr>
                                <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Role</th>
                                <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Status</th>
                                <!-- <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Courses</th> -->
                                <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Joined</th>
                                <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="userTableBody" class="divide-y divide-gray-200">
                            <!-- Admin User -->
                            <?php if ($userTableResult->num_rows > 0): ?>
                                <?php while ($row = $userTableResult->fetch_assoc()): ?>
                                    <tr>
                                        <td class="py-4 px-6">
                                            <div class="flex items-center">
                                                <div class="bg-purple-500 text-white rounded-full w-10 h-10 flex items-center justify-center">
                                                    <span><?= htmlspecialchars(substr($row['name'], 0, 2)) ?></span>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($row['name']) ?></div>
                                                    <div class="text-gray-500 text-sm"><?= htmlspecialchars($row['email']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 hidden sm:table-cell">
                                            <span class="role-badge <?= $roleClasses[$row['role_id']] ?? 'bg-gray-100 text-gray-800' ?>">
                                                <?= htmlspecialchars(getRoleName($row['role_id'])) ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 hidden md:table-cell">
                                            <?php if ($row['status'] == 1): ?>
                                                <span class="status-badge bg-green-100 text-green-800">Active</span>
                                            <?php else: ?>
                                                <span class="status-badge bg-red-500 text-white">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <!-- <td class="py-4 px-6 hidden lg:table-cell">
                                            <div class="text-gray-900">All Courses</div>
                                            <div class="text-gray-500 text-sm">Full access</div>
                                        </td> -->
                                        <td class="py-4 px-6 hidden md:table-cell">
                                            <div class="text-gray-900"><?= date('M d Y', strtotime($row['created_at'])) ?></div>
                                            <div class="text-gray-500 text-sm"><?= htmlspecialchars(getRoleName($row['role_id'])) ?></div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="user-actions flex space-x-2">
                                                <a href="?action=edit&id=<?= $row['id'] ?>" class="text-blue-600 hover:text-blue-900" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                    <span class="sr-only">Edit</span>
                                                </a>
                                                <a href="?action=toggle&id=<?= $row['id'] ?>" class="text-gray-600 hover:text-gray-900 open-toggle-modal" data-user-id="<?= $row['id'] ?>" title="Toggle Status" >
                                                    <i class="fas <?= $row['status'] == 1 ? 'fa-lock-open' : 'fa-lock' ?>"></i>
                                                    <span class="sr-only">Toggle Status</span>
                                                </a>
                                                <?php if ($row['role_id'] != 1): ?>
                                                <a href="?action=delete&id=<?= $row['id'] ?>" class="text-red-600 hover:text-red-900 open-delete-modal" data-user-id="<?= $row['id'] ?>" title="Delete" >
                                                    <i class="fas fa-trash"></i>
                                                    <span class="sr-only">Delete</span>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                    <tr  data-role-id="<?= $row['role_id'] ?>">
                                        <td colspan="4" class="border p-2 text-center text-gray-500">No users found</td>
                                    </tr>
                            <?php endif; ?>   
                        </tbody>
                    </table>

                </div>

                <div id="toggleModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                  <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
                    <h2 class="text-xl font-semibold mb-4">Toggle User Status</h2>
                    <p class="text-gray-700 mb-4">Are you sure you want to toggle this user's status?</p>
                    <form method="get">
                      <input type="hidden" name="action" value="toggle">
                      <input type="hidden" name="id" id="toggleUserId">
                      <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeToggleModal()" class="bg-gray-300 px-4 py-2 rounded">Cancel</button>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Confirm</button>
                      </div>
                    </form>
                  </div>
                </div>
                
                <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                  <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
                    <h2 class="text-xl font-semibold mb-4">Delete User</h2>
                    <p class="text-gray-700 mb-4">Are you sure you want to delete this user? This action cannot be undone.</p>
                    <form method="get">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" id="deleteUserId">
                      <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeDeleteModal()" class="bg-gray-300 px-4 py-2 rounded">Cancel</button>
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded">Delete</button>
                      </div>
                    </form>
                  </div>
                </div>

                <!-- Table Footer -->
                <div id="tableFooter" class="bg-light px-6 py-4 flex flex-col md:flex-row items-center justify-between">
                    <div class="text-sm text-gray-700 mb-4 md:mb-0">
                        Showing <span class="font-semibold"><?= $offset + 1 ?> - <?= min($offset + $perPage, $totalUsers) ?></span> of <span class="font-semibold"><?= $totalUsers ?></span> users
                    </div>
                    <div class="pagination flex items-center space-x-2">
                        <!-- First page & Prev -->
                        <?php if ($page > 1): ?>
                            <a href="<?= $baseURL . '?' . $baseQuery . '&page=1' ?>" class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-50">&laquo;</a>
                            <a href="<?= $baseURL . '?' . $baseQuery . '&page=' . ($page - 1) ?>" class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-50">&lsaquo;</a>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <a href="<?= $baseURL . '?' . $baseQuery . '&page=' . $i ?>"
                               class="px-3 py-1 <?= $i == $page ? 'bg-blue-600 text-white' : 'border text-gray-600 hover:bg-gray-50' ?> rounded"><?= $i ?></a>
                        <?php endfor; ?>

                        <!-- Next & Last -->
                        <?php if ($page < $totalPages): ?>
                            <a href="<?= $baseURL . '?' . $baseQuery . '&page=' . ($page + 1) ?>" class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-50">&rsaquo;</a>
                            <a href="<?= $baseURL . '?' . $baseQuery . '&page=' . $totalPages ?>" class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-50">&raquo;</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Role Permissions Section -->
            <div class="role-permissions grid grid-cols-1 lg:grid-cols-2 gap-6 px-6 mb-8">
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-dark mb-4">Role Permissions</h3>
                    <div class="space-y-4">
                        <div class="border-b pb-4">
                            <h4 class="font-medium text-gray-900 flex items-center">
                                <span class="role-badge bg-purple-100 text-purple-800 mr-3">Admin</span>
                                Full Platform Access
                            </h4>
                            <p class="text-gray-600 text-sm mt-2">Can manage users, courses, content, and platform settings.</p>
                        </div>
                        
                        <div class="border-b pb-4">
                            <h4 class="font-medium text-gray-900 flex items-center">
                                <span class="role-badge bg-blue-100 text-blue-800 mr-3">Teacher</span>
                                Course Management Access
                            </h4>
                            <p class="text-gray-600 text-sm mt-2">Can create and manage their own courses, approve discussions, and respond to student queries.</p>
                        </div>
                        
                        <div class="border-b pb-4">
                            <h4 class="font-medium text-gray-900 flex items-center">
                                <span class="role-badge bg-green-100 text-green-800 mr-3">Student</span>
                                Learning Path Access
                            </h4>
                            <p class="text-gray-600 text-sm mt-2">Can access courses, track progress, participate in discussions, and get career support.</p>
                        </div>
                        
                        <div>
                            <h4 class="font-medium text-gray-900 flex items-center">
                                <span class="role-badge bg-gray-100 text-gray-800 mr-3">External User</span>
                                Limited Access
                            </h4>
                            <p class="text-gray-600 text-sm mt-2">Can browse courses, participate in free discussions, and upgrade to student role.</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-dark mb-4">Teacher Approval Process</h3>
                    <div class="space-y-6">
                        <div class="flex items-start">
                            <div class="bg-blue-100 rounded-full p-3 mt-1">
                                <i class="fas fa-user-check text-blue-600"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="font-medium text-gray-900">Application Submission</h4>
                                <p class="text-gray-600 text-sm mt-1">Teachers submit applications with credentials and expertise details.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="bg-yellow-100 rounded-full p-3 mt-1">
                                <i class="fas fa-search text-yellow-600"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="font-medium text-gray-900">Admin Review</h4>
                                <p class="text-gray-600 text-sm mt-1">Admins review qualifications, experience, and background.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="bg-green-100 rounded-full p-3 mt-1">
                                <i class="fas fa-check-circle text-green-600"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="font-medium text-gray-900">Approval & Onboarding</h4>
                                <p class="text-gray-600 text-sm mt-1">Approved teachers gain course creation tools and teaching resources.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="bg-purple-100 rounded-full p-3 mt-1">
                                <i class="fas fa-chalkboard-teacher text-purple-600"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="font-medium text-gray-900">Teaching & Moderation</h4>
                                <p class="text-gray-600 text-sm mt-1">Teachers can create content, moderate discussions, and schedule classes.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])):
                $editId = intval($_GET['id']);
                $stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $editId);
                $stmt->execute();
                $result = $stmt->get_result();
                $editUser = $result->fetch_assoc();
            ?>
            <div class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
                <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
                    <h2 class="text-xl font-bold mb-4">Edit User</h2>
                    <form method="post" action="?action=edit&id=<?= $editId ?>">
                        <div class="mb-4">
                            <label class="block text-gray-700 mb-1">Name</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($editUser['name']) ?>" class="w-full border rounded p-2">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($editUser['email']) ?>" class="w-full border rounded p-2">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 mb-1">Role</label>
                            <select name="role_id" class="w-full border rounded p-2">
                                <option value="1" <?= $editUser['role_id'] == 1 ? 'selected' : '' ?>>Admin</option>
                                <option value="2" <?= $editUser['role_id'] == 2 ? 'selected' : '' ?>>Teacher</option>
                                <option value="3" <?= $editUser['role_id'] == 3 ? 'selected' : '' ?>>Student</option>
                            </select>
                        </div>
                        <div class="flex justify-end space-x-2">
                            <a href="<?= strtok($_SERVER["REQUEST_URI"], '?') ?>" class="px-4 py-2 bg-gray-300 rounded">Cancel</a>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Mobile menu toggle
        const mobileMenuButton = document.querySelector('.mobile-menu-button');
        const sidebar = document.querySelector('.sidebar');
        
        mobileMenuButton.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
        
        // Tab switching functionality
        document.addEventListener('DOMContentLoaded', function () {
            const tabs = document.querySelectorAll('#userTabs .tab');
            const rows = document.querySelectorAll('tr[data-role-id]');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    // Remove active class from all tabs
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');

                    const role = tab.getAttribute('data-role');

                    rows.forEach(row => {
                        const roleId = row.getAttribute('data-role-id');

                        if (role === 'all' || roleId === role) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            });
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 1024 && 
                !sidebar.contains(e.target) && 
                e.target !== mobileMenuButton && 
                !mobileMenuButton.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });
        
        // Simulate role-based view switching
        document.querySelector('.role-switch')?.addEventListener('click', function() {
            const currentRole = document.querySelector('.current-role').textContent;
            const newRole = currentRole === 'Admin View' ? 'Teacher View' : 'Admin View';
            document.querySelector('.current-role').textContent = newRole;
            
            // Simulate view change
            if(newRole === 'Teacher View') {
                document.querySelector('.admin-only').style.display = 'none';
                document.querySelector('.teacher-view-message').style.display = 'block';
            } else {
                document.querySelector('.admin-only').style.display = 'block';
                document.querySelector('.teacher-view-message').style.display = 'none';
            }
        });

        $(function() {
            // Modal open/close
            $('#openModal').click(() => $('#userModal').removeClass('hidden'));
            $('#closeModal, #closeModalBtn').click(() => $('#userModal').addClass('hidden'));


            $('#EditopenModal').click(() => $('#userEditModal').removeClass('hidden'));
            $('#EditcloseModal, #EditcloseModalBtn').click(() => $('#userEditModal').addClass('hidden'));

            
        });

        $(document).ready(function() {
            $('[role="alert"]').each(function() {
                var $message = $(this);
                setTimeout(function() {
                    $message.animate({ opacity: 0 }, 300, function() {
                        $(this).remove();
                    });
                }, 5000);
            });
        });

        document.querySelectorAll('.open-toggle-modal').forEach(button => {
            button.addEventListener('click', function(e) {
              e.preventDefault();
              const userId = this.dataset.userId;
              document.getElementById('toggleUserId').value = userId;
              document.getElementById('toggleModal').classList.remove('hidden');
            });
          });

          // Close modal
          function closeToggleModal() {
            document.getElementById('toggleModal').classList.add('hidden');
          }

        // Delete modal open
          document.querySelectorAll('.open-delete-modal').forEach(button => {
            button.addEventListener('click', function(e) {
              e.preventDefault();
              const userId = this.dataset.userId;
              document.getElementById('deleteUserId').value = userId;
              document.getElementById('deleteModal').classList.remove('hidden');
            });
          });

          // Delete modal close
          function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
          }

        const searchInput = document.getElementById('searchInput');
        const tableBody = document.getElementById('userTableBody');
        const tableFooter = document.getElementById('tableFooter'); // Add an ID to the footer div

        function fetchUsers(page = 1) {
            const query = searchInput.value;

            fetch(`?ajax=1&q=${encodeURIComponent(query)}&page=${page}`)
                .then(response => response.json())
                .then(data => {
                    tableBody.innerHTML = data.body;
                    tableFooter.innerHTML = data.footer;

                    // Re-bind page link events
                    document.querySelectorAll('.page-link').forEach(link => {
                        link.addEventListener('click', function (e) {
                            e.preventDefault();
                            const newPage = this.getAttribute('data-page');
                            fetchUsers(newPage);
                        });
                    });
                });
        }

        searchInput.addEventListener('input', () => fetchUsers());
    </script>
</body>
</html>