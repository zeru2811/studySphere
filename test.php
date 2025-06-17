<?php
session_start();
require "requires/common.php";
require "requires/title.php";
require "requires/connect.php";

function getRoleName($role_id) {
    switch ($role_id) {
        case 1: return 'Admin';
        case 2: return 'Teacher';
        case 3: return 'Student';
        case 4: return 'External User';
        default: return 'Unknown';
    }
}

function getStatusLabel($status) {
    return $status == 1 ? 'Active' : 'Inactive';
}

$totalUsers = $mysqli->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$activeUsers = $mysqli->query("SELECT COUNT(*) AS active FROM users WHERE status = 1")->fetch_assoc()['active'];
$pendingTeachers = $mysqli->query("SELECT COUNT(*) AS pending FROM users WHERE role_id = 2 AND status = 0")->fetch_assoc()['pending'];
$students = $mysqli->query("SELECT COUNT(*) AS student FROM users WHERE role_id = 3")->fetch_assoc()['student'];



$Usertablesql = "SELECT * FROM users ORDER BY id DESC";
$userTableResult = $mysqli->query($Usertablesql);





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
                            <input type="text" placeholder="Search users..." class="search-input pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent w-full">
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
                            <p class="text-2xl font-bold mt-1"><?= $totalUsers ?></p>
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
                    <div class="tabs flex space-x-2 border-b border-gray-200" id="userTabs">
                        <div class="tab active" data-role="all">All Users</div>
                        <div class="tab" data-role="1">Students</div>
                        <div class="tab" data-role="2">Teachers</div>
                        <div class="tab" data-role="3">External</div>
                        <div class="tab" data-role="pending">Pending</div>
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
                            <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6">
                            <h2 class="text-xl font-bold mb-4">Add New User</h2>
                            <form id="addUserForm" method="POST" action="add_user.php">
                                <input name="name" class="w-full border p-2 mb-3" placeholder="Name" required>
                                <input name="email" type="email" class="w-full border p-2 mb-3" placeholder="Email" required>
                                <input name="phone" class="w-full border p-2 mb-3" placeholder="Phone">
                                <input name="password" type="password" class="w-full border p-2 mb-3" placeholder="Password" required>
                                <select name="role_id" class="w-full border p-2 mb-3">
                                <option value="1">Student</option>
                                <option value="2">Teacher</option>
                                <option value="3">External</option>
                                </select>
                                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Save</button>
                                <button type="button" id="closeModal" class="ml-2 text-gray-600">Cancel</button>
                            </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- User Table -->
            <div class="bg-white shadow rounded-lg mx-6 my-6 overflow-hidden">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
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
                        <tbody class="divide-y divide-gray-200">
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
                                            <span class="role-badge bg-purple-100 text-purple-800"><?= htmlspecialchars(getRoleName($row['role_id'])) ?></span>
                                        </td>
                                        <td class="py-4 px-6 hidden md:table-cell">
                                            <?php if ($row['status'] == 1): ?>
                                                <span class="status-badge bg-green-100 text-green-800">Active</span>
                                            <?php else: ?>
                                                <span class="status-badge bg-red-100 text-green-800">Inactive</span>
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
                                                <button class="text-blue-600 hover:text-blue-900" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                    <span class="sr-only">Edit</span>
                                                </button>
                                                <button class="text-gray-600 hover:text-gray-900" title="Toggle Status">
                                                    <i class="fas fa-lock"></i>
                                                    <span class="sr-only">Toggle Status</span>
                                                </button>
                                                <?php if ($row['role_id'] != 1): ?>
                                                <button class="text-red-600 hover:text-red-900" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                    <span class="sr-only">Delete</span>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="border p-2 text-center text-gray-500">No users found</td>
                                    </tr>
                            <?php endif; ?>   
                        </tbody>
                    </table>
                </div>
                
                <!-- Table Footer -->
                <div class="bg-light px-6 py-4 flex flex-col md:flex-row items-center justify-between table-footer">
                    <div class="text-sm text-gray-700 mb-4 md:mb-0">
                        Showing <span class="font-semibold">1-5</span> of <span class="font-semibold">1,248</span> users
                    </div>
                    <div class="pagination flex items-center space-x-2">
                        <button class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-50">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="px-3 py-1 bg-primary text-white rounded">1</button>
                        <button class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-50">2</button>
                        <button class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-50">3</button>
                        <span class="px-2 hidden sm:inline">...</span>
                        <button class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-50 hidden sm:inline">24</button>
                        <button class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-50">
                            <i class="fas fa-chevron-right"></i>
                        </button>
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
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
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
            $('#closeModal').click(() => $('#userModal').addClass('hidden'));

            // Load users on tab click
            $('.tab').click(function() {
                $('.tab').removeClass('active');
                $(this).addClass('active');

                const role = $(this).data('role');
                $.get('fetch_users.php', { role }, function(data) {
                    const users = JSON.parse(data);
                    let html = '';
                    users.forEach(user => {
                        html += `<tr><td>${user.name}</td><td>${user.email}</td><td>${user.role_id}</td></tr>`;
                    });
                    $('#userTable tbody').html(html);
                });
            });

            // Load all users on page load
            $('.tab[data-role="all"]').click();
        });
    </script>
</body>
</html>