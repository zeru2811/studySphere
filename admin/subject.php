<?php
session_start();
require "../requires/common.php";  
require "../requires/title.php";  
require "../requires/connect.php";  
$currentPage = basename($_SERVER['PHP_SELF']);  
$pagetitle = "Subjects";  

// Handle Add Subject Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['subject_name'])) {
        $name = trim($_POST['subject_name']);
        if ($name !== '') {
            $stmt = $mysqli->prepare("INSERT INTO subject (name) VALUES (?)");
            if ($stmt) {
                $stmt->bind_param("s", $name);
                $stmt->execute();
                $stmt->close();
                $_SESSION['message'] = "Subject added successfully.";
            } else {
                $_SESSION['error'] = "Failed to prepare statement.";
            }
        } else {
            $_SESSION['error'] = "Subject name cannot be empty.";
        }
    } elseif (isset($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        $name = trim($_POST['edit_name']);
        if ($name !== '') {
            $stmt = $mysqli->prepare("UPDATE subject SET name = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("si", $name, $id);
                $stmt->execute();
                $stmt->close();
                $_SESSION['message'] = "Subject updated successfully.";
            } else {
                $_SESSION['error'] = "Failed to prepare statement.";
            }
        } else {
            $_SESSION['error'] = "Subject name cannot be empty.";
        }
    }
    header("Location: subject.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $mysqli->prepare("DELETE FROM subject WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Subject deleted successfully.";
        } else {
            if ($stmt->errno == 1451) {
                $_SESSION['error'] = "Cannot delete this subject because it is linked to other data.";
            } else {
                $_SESSION['error'] = "Delete failed: " . $stmt->error;
            }
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Failed to prepare delete statement.";
    }

    header("Location: subject.php");
    exit;
}

// Fetch Subjects
$subjects = [];
$result = $mysqli->query("SELECT * FROM subject ORDER BY id DESC");
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
}
?>

<?php
require './templates/admin_header.php';
require './templates/admin_sidebar.php';
?>

<body class="bg-gray-50 w-full min-h-screen p-4 md:p-6">

<div class="max-w-6xl w-full pt-10 mx-auto">
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
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Subject Management</h1>
            <p class="text-gray-600">Manage all subjects in the system</p>
        </div>
        <button onclick="openModal('addModal')" 
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
            <i class="fas fa-plus"></i>
            <span>Add Subject</span>
        </button>
    </div>

    <!-- Subjects Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Updated At</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($subjects) > 0): ?>
                        <?php foreach ($subjects as $subject): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $subject['id'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($subject['name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('M j, Y h:i A', strtotime($subject['created_at'])) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('M j, Y h:i A', strtotime($subject['updated_at'])) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <button onclick="openEditModal(<?= $subject['id'] ?>, '<?= htmlspecialchars(addslashes($subject['name'])) ?>')"
                                                class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="confirmDelete(<?= $subject['id'] ?>)"
                                                class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No subjects found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Subject Modal -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md transform transition-all">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Add New Subject</h2>
                <button onclick="closeModal('addModal')" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="subject.php">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject Name</label>
                    <input type="text" name="subject_name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('addModal')"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Save Subject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Subject Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md transform transition-all">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Edit Subject</h2>
                <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="subject.php">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject Name</label>
                    <input type="text" name="edit_name" id="edit_name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('editModal')"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Update Subject
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
                <p class="text-gray-700">Are you sure you want to delete this subject? This action cannot be undone.</p>
            </div>
            <div class="flex justify-end gap-2">
                <button onclick="closeModal('deleteModal')"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <a id="deleteLink" href="#"
                   class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Delete Subject
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

    function openEditModal(id, name) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
        openModal('editModal');
    }

    function confirmDelete(id) {
        document.getElementById('deleteLink').href = `subject.php?delete=${id}`;
        openModal('deleteModal');
    }

    // Close modal when clicking outside
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('fixed')) {
            event.target.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    });
</script>
</body>
</html>