    <?php
    session_start();
    require "../requires/common.php";
    require "../requires/title.php";
    require "../requires/connect.php";
    $currentPage = basename($_SERVER['PHP_SELF']);
    $pagetitle = "Enroll Course List";

    // Handle Excel Export
    if (isset($_GET['export'])){
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename=enrollments_' . date('Y-m-d') . '.xls');
    
        // Get filtered data
        $enrollments = getFilteredEnrollments($mysqli);
    
        echo "ID\tUser Name\tUser Email\tUser Phone\tCourse Name\tCourse Price\tPayment Status\tPayment Type\tTransaction ID\tEnrolled At\tUpdated At\n";
    
        foreach ($enrollments as $enrollment) {
            $paymentType = $enrollment['payment'] ? $enrollment['payment']['payment_type'] : 'None';
            $transactionId = $enrollment['payment'] ? $enrollment['payment']['transitionId'] : 'None';
        
            echo $enrollment['id'] . "\t" .
                 $enrollment['user_name'] . "\t" .
                 $enrollment['user_email'] . "\t" .
                 $enrollment['user_phone'] . "\t" .
                 $enrollment['course_name'] . "\t" .
                 $enrollment['course_price'] . "\t" .
                 $enrollment['payment_status'] . "\t" .
                 $paymentType . "\t" .
                 $transactionId . "\t" .
                 $enrollment['enrolled_at'] . "\t" .
                 ($enrollment['updated_at'] ? $enrollment['updated_at'] : 'N/A') . "\n";
        }
        exit;
    }

    // Handle Payment Status Update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
        $enrollId = intval($_POST['enroll_id']);
        $status = $_POST['payment_status'];
    
        $stmt = $mysqli->prepare("UPDATE enroll_course SET payment_status = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $status, $enrollId);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Payment status updated successfully.";
            } else {
                $_SESSION['error'] = "Failed to update payment status: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "Failed to prepare statement.";
        }
        header("Location: enroll_list.php" . getFilterQueryString());
        exit;
    }

    // Handle Delete Enrollment
    if (isset($_GET['delete'])) {
        $id = intval($_GET['delete']);
        $stmt = $mysqli->prepare("DELETE FROM enroll_course WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Enrollment deleted successfully.";
            } else {
                $_SESSION['error'] = "Delete failed: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "Failed to prepare delete statement.";
        }
        header("Location: enroll_list.php" . getFilterQueryString());
        exit;
    }

    // Function to get filter query string
    function getFilterQueryString() {
        $params = [];
        if (!empty($_GET['search'])) $params['search'] = $_GET['search'];
        if (!empty($_GET['status'])) $params['status'] = $_GET['status'];
        return $params ? '?' . http_build_query($params) : '';
    }

    // Function to get filtered enrollments
    function getFilteredEnrollments($mysqli) {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
    
        $query = "SELECT 
                    ec.id, 
                    ec.payment_status, 
                    ec.enrolled_at, 
                    ec.updated_at,
                    u.name as user_name,
                    u.email as user_email,
                    u.phone as user_phone,
                    c.name as course_name,
                    c.price as course_price
                  FROM enroll_course ec
                  JOIN users u ON ec.userId = u.id
                  JOIN courses c ON ec.courseId = c.id
                  WHERE 1=1";
    
        $params = [];
        $types = '';
    
        if (!empty($search)) {
            $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR c.name LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $types .= 'ssss';
        }
    
        if (!empty($status)){
            $query .= " AND ec.payment_status = ?";
            $params[] = $status;
            $types .= 's';
        }
    
        $query .= " ORDER BY ec.id DESC";
    
        $stmt = $mysqli->prepare($query);
        if ($stmt) {
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $enrollments = [];
            while ($row = $result->fetch_assoc()) {
                $enrollments[] = $row;
            }
            $stmt->close();
        
            // Fetch payment details for each enrollment
            foreach ($enrollments as &$enrollment) {
                $paymentQuery = $mysqli->prepare("
                    SELECT ep.*, pt.name as payment_type 
                    FROM enroll_payment ep
                    JOIN payment_type pt ON ep.paymentTypeId = pt.id
                    WHERE ep.enroll_courseId = ?
                ");
                $paymentQuery->bind_param("i", $enrollment['id']);
                $paymentQuery->execute();
                $paymentResult = $paymentQuery->get_result();
                $enrollment['payment'] = $paymentResult->fetch_assoc();
                $paymentQuery->close();
            }
        
            return $enrollments;
        }
    
        return [];
    }

    // Get filtered enrollments
    $enrollments = getFilteredEnrollments($mysqli);
    ?>

    <?php
    require './templates/admin_header.php';
    require './templates/admin_sidebar.php';
    ?>

    <body class="bg-gray-50 w-full min-h-screen p-4 md:p-6">

    <div class="max-w-6xl w-full px-4 pt-10 mx-auto">
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
                            <p class="text-sm font-medium"><?php echo $_SESSION['message'];
                                                            unset($_SESSION['message']); ?></p>
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
                            <p class="text-sm font-medium"><?php echo $_SESSION['error'];
                                                            unset($_SESSION['error']); ?></p>
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

        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Enrollment Management</h1>
                <p class="text-gray-600">Manage all course enrollments in the system</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="enroll_list.php?export=1<?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?>" 
                   class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-medium hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 flex items-center gap-2">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <form method="GET" action="enroll_list.php" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" name="search" id="search" placeholder="Search by name, email, phone or course" 
                               value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
                               class="pl-10 w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
            
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
                    <select name="status" id="status" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= isset($_GET['status']) && $_GET['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="paid" <?= isset($_GET['status']) && $_GET['status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="failed" <?= isset($_GET['status']) && $_GET['status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
                    </select>
                </div>
            
                <div class="flex items-end gap-2">
                    <button type="submit" 
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 h-[42px]">
                        Apply Filters
                    </button>
                    <?php if (isset($_GET['search']) || isset($_GET['status'])): ?>
                        <a href="enroll_list.php" 
                           class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 h-[42px] flex items-center">
                            Clear
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Enrollments Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Phone</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Payment Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden xl:table-cell">Payment Details</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Enrolled At</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (count($enrollments) > 0): ?>
                            <?php foreach ($enrollments as $enrollment): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <!-- ID - Hidden on mobile -->
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 hidden sm:table-cell"><?= $enrollment['id'] ?></td>
                            
                                    <!-- User - Always visible -->
                                    <td class="px-4 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($enrollment['user_name']) ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($enrollment['user_email']) ?></div>
                                        <div class="text-sm text-gray-500 md:hidden"><?= htmlspecialchars($enrollment['user_phone']) ?></div>
                                    </td>
                            
                                    <!-- Phone - Hidden on mobile -->
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 hidden md:table-cell">
                                        <?= htmlspecialchars($enrollment['user_phone']) ?>
                                    </td>
                            
                                    <!-- Course - Always visible -->
                                    <td class="px-4 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($enrollment['course_name']) ?></div>
                                        <div class="text-sm text-gray-500">Ks<?= number_format($enrollment['course_price'], 2) ?></div>
                                    </td>
                            
                                    <!-- Payment Status - Hidden on mobile and tablet -->
                                    <td class="px-4 py-4 whitespace-nowrap hidden lg:table-cell">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?= $enrollment['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 
                                               ($enrollment['payment_status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                            <?= ucfirst($enrollment['payment_status']) ?>
                                        </span>
                                    </td>
                            
                                    <!-- Payment Details - Hidden on mobile, tablet and small desktop -->
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 hidden xl:table-cell">
                                        <?php if ($enrollment['payment']): ?>
                                            <div>
                                                <span class="font-medium"><?= ucfirst($enrollment['payment']['payment_type']) ?>:</span>
                                                <?= htmlspecialchars($enrollment['payment']['transitionId']) ?>
                                                <?php if ($enrollment['payment']['screenshot_path']): ?>
                                                    <br><a href="../uploads/payments/<?= htmlspecialchars($enrollment['payment']['screenshot_path']) ?>" target="_blank" class="text-indigo-600 hover:text-indigo-900">View Screenshot</a>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            No payment recorded
                                        <?php endif; ?>
                                    </td>
                            
                                    <!-- Enrolled At - Hidden on mobile -->
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 hidden md:table-cell">
                                        <?= date('M j, Y h:i A', strtotime($enrollment['enrolled_at'])) ?>
                                    </td>
                            
                                    <!-- Actions - Always visible -->
                                    <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end gap-2">
                                            <!-- Info button - visible only on mobile -->
                                            <button onclick="openInfoModal(<?= htmlspecialchars(json_encode($enrollment)) ?>)"
                                                    class="text-blue-600 hover:text-blue-900 sm:hidden"
                                                    title="View Details">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                    
                                            <!-- Edit button - hidden on mobile -->
                                            <button onclick="openStatusModal(<?= $enrollment['id'] ?>, '<?= $enrollment['payment_status'] ?>')"
                                                    class="text-indigo-600 hover:text-indigo-900"
                                                    title="Edit Status">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                    
                                            <!-- Delete button - hidden on mobile -->
                                            <button onclick="confirmDelete(<?= $enrollment['id'] ?>)"
                                                    class="text-red-600 hover:text-red-900"
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                    <?php if (isset($_GET['search']) || isset($_GET['status'])): ?>
                                        No enrollments found matching your criteria. <a href="enroll_list.php" class="text-indigo-600 hover:text-indigo-900">Clear filters</a> to see all enrollments.
                                    <?php else: ?>
                                        No enrollments found.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Info Modal -->
        <div id="infoModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4">
            <div class="bg-white rounded-xl shadow-lg w-full max-w-md transform transition-all">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-gray-800">Enrollment Details</h2>
                        <button onclick="closeModal('infoModal')" class="text-gray-400 hover:text-gray-500">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="space-y-4 text-sm">
                        <div>
                            <div class="mt-1 grid grid-cols-2 gap-2">
                                <div>
                                    <p class="text-gray-500">Name:</p>
                                    <p id="info-user-name" class="font-medium"></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Email:</p>
                                    <p id="info-user-email" class="font-medium"></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Phone:</p>
                                    <p id="info-user-phone" class="font-medium"></p>
                                </div>
                            </div>
                        </div>
                
                        <div>
                            <div class="mt-1 grid grid-cols-2 gap-2">
                                <div>
                                    <p class="text-gray-500">Course:</p>
                                    <p id="info-course-name" class="font-medium"></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Price:</p>
                                    <p id="info-course-price" class="font-medium"></p>
                                </div>
                            </div>
                        </div>
                
                        <div>
                            <div class="mt-1 grid grid-cols-2 gap-2">
                                <div>
                                    <p class="text-gray-500">Status:</p>
                                    <p id="info-payment-status" class="font-medium"></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Type:</p>
                                    <p id="info-payment-type" class="font-medium"></p>
                                </div>
                                <div class="col-span-1">
                                    <p class="text-gray-500">Transaction ID:</p>
                                    <p id="info-transaction-id" class="font-medium"></p>
                                </div>
                            
                                <div>
                                    <span class="text-gray-500">Screenshot Link:</span>
                                    <div id="info-screenshot-container">

                                    </div>
                                </div>
                            </div>
                        </div>
                
                        <div>
                            <div class="mt-1 grid grid-cols-2 gap-2">
                                <div>
                                    <p class="text-gray-500">Enrolled At:</p>
                                    <p id="info-enrolled-at" class="font-medium"></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Last Updated:</p>
                                    <p id="info-updated-at" class="font-medium"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 pt-4">
                        <button onclick="closeModal('infoModal')"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md transform transition-all">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Update Payment Status</h2>
                    <button onclick="closeModal('statusModal')" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="enroll_list.php<?= getFilterQueryString() ?>">
                    <input type="hidden" name="enroll_id" id="status_enroll_id">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
                        <select name="payment_status" id="payment_status" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" onclick="closeModal('statusModal')"
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" name="update_status"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md transform transition-all">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Confirm Deletion</h2>
                    <button onclick="closeModal('deleteModal')" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-6">
                    <p class="text-gray-700">Are you sure you want to delete this enrollment? This action cannot be undone.</p>
                </div>
                <div class="flex justify-end gap-2">
                    <button onclick="closeModal('deleteModal')"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <a id="deleteLink" href="#"
                       class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Delete Enrollment
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/jquery-3.7.1.min.js"></script>

    <script>
        $(document).ready(function() {
            $('[role="alert"]').each(function() {
                var $message = $(this);
                setTimeout(function() {
                    $message.animate({
                        opacity: 0
                    }, 300, function() {
                        $(this).remove();
                    });
                }, 5000);
            });
        });

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        function openStatusModal(id, status) {
            document.getElementById('status_enroll_id').value = id;
            document.getElementById('payment_status').value = status;
            openModal('statusModal');
        }

         function confirmDelete(id) {
            document.getElementById('deleteLink').href = `enroll_list.php?delete=${id}`;
            openModal('deleteModal');
        }


        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('fixed')) {
                event.target.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        });

        // Function to open info modal with enrollment data
        function openInfoModal(enrollment) {
            // Set all the data in the modal
            document.getElementById('info-user-name').textContent = enrollment.user_name;
            document.getElementById('info-user-email').textContent = enrollment.user_email;
            document.getElementById('info-user-phone').textContent = enrollment.user_phone;
            document.getElementById('info-course-name').textContent = enrollment.course_name;
            document.getElementById('info-course-price').textContent = 'Ks' + parseFloat(enrollment.course_price).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('info-payment-status').textContent = enrollment.payment_status.charAt(0).toUpperCase() + enrollment.payment_status.slice(1);
            document.getElementById('info-enrolled-at').textContent = new Date(enrollment.enrolled_at).toLocaleString();
            document.getElementById('info-updated-at').textContent = enrollment.updated_at ? new Date(enrollment.updated_at).toLocaleString() : 'N/A';
        
            // Handle payment details
            const paymentContainer = document.getElementById('info-screenshot-container');
            paymentContainer.innerHTML = ''; // Clear previous content
        
            if (enrollment.payment) {
                document.getElementById('info-payment-type').textContent = enrollment.payment.payment_type.charAt(0).toUpperCase() + enrollment.payment.payment_type.slice(1);
                document.getElementById('info-transaction-id').textContent = enrollment.payment.transitionId;
            
                if (enrollment.payment.screenshot_path) {
                    const screenshotLink = document.createElement('a');
                    screenshotLink.href = '../uploads/payments/' + enrollment.payment.screenshot_path;
                    screenshotLink.target = '_blank';
                    screenshotLink.className = 'text-indigo-600 hover:text-indigo-900';
                    screenshotLink.textContent = 'View Payment Screenshot';
                    paymentContainer.appendChild(screenshotLink);
                } else {
                    paymentContainer.textContent = 'No screenshot available';
                }
            } else {
                document.getElementById('info-payment-type').textContent = 'None';
                document.getElementById('info-transaction-id').textContent = 'None';
                paymentContainer.textContent = 'No payment details';
            }
        
            // Show the modal
            document.getElementById('infoModal').classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        // Close modal function
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    </script>
    </body>
    </html>

   