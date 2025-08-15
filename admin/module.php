<?php
session_start();

// Check if user is logged in and has teacher/admin role
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role_id'] > 2) { // Only allow teachers/admins (role_id 1 or 2)
    header("Location: ../error.php");
    exit();
}

// Database connection and common functions
require "../requires/common.php";
require "../requires/connect.php";
require "../requires/common_function.php";

$pagetitle = "Modules Management";

// Handle Module Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_module'])) {
    $name = trim($_POST['name']);
    
    if (empty($name)) {
        $_SESSION['error'] = "Module name cannot be empty";
    } else {
        $stmt = $mysqli->prepare("INSERT INTO module (name) VALUES (?)");
        if (!$stmt) {
            $_SESSION['error'] = "Database error: " . $mysqli->error;
        } else {
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Module created successfully!";
            } else {
                $_SESSION['error'] = "Failed to create module: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    header('Location: module.php');
    exit();
}

// Handle Module Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_module'])) {
    $id = intval($_POST['module_id']);
    $name = trim($_POST['name']);
    
    if (empty($name)) {
        $_SESSION['error'] = "Module name cannot be empty";
    } else {
        $stmt = $mysqli->prepare("UPDATE module SET name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        if (!$stmt) {
            $_SESSION['error'] = "Database error: " . $mysqli->error;
        } else {
            $stmt->bind_param("si", $name, $id);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Module updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to update module: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    header('Location: module.php');
    exit();
}

// Handle Module Deletion
if (isset($_GET['delete_module'])) {
    $id = intval($_GET['delete_module']);
    
    // First check if there are any questions in this module
    $checkStmt = $mysqli->prepare("SELECT COUNT(*) FROM question WHERE moduleId = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkStmt->bind_result($questionCount);
    $checkStmt->fetch();
    $checkStmt->close();
    
    if ($questionCount > 0) {
        $_SESSION['error'] = "Cannot delete module with existing questions. Please delete or move the questions first.";
    } else {
        $stmt = $mysqli->prepare("DELETE FROM module WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['message'] = "Module deleted successfully.";
            } else {
                $_SESSION['error'] = "Delete failed: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "Database error: " . $mysqli->error;
        }
    }
    header("Location: module.php");
    exit();
}

// Get all modules
$modules = [];
$modulesQuery = $mysqli->prepare("SELECT m.*, 
    (SELECT COUNT(*) FROM question q WHERE q.moduleId = m.id) as question_count
    FROM module m ORDER BY m.name ASC");

if ($modulesQuery && $modulesQuery->execute()) {
    $modules = $modulesQuery->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $_SESSION['error'] = "Failed to load modules: " . $mysqli->error;
}

require "./templates/admin_header.php";
require "./templates/admin_sidebar.php";
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Modules Management</h1>
            <p class="text-gray-600 mt-2">Create and manage modules for organizing questions</p>
        </div>
        <button onclick="openModal('createModuleModal')"
                class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition-colors shadow-sm flex items-center">
            <i class="fas fa-plus mr-2"></i> Create Module
        </button>
    </div>

    <!-- Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg shadow-sm">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2 text-green-500"></i>
                <p><?= htmlspecialchars($_SESSION['message'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <?php unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-sm">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2 text-red-500"></i>
                <p><?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Modules Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <?php if (empty($modules)): ?>
            <div class="p-8 text-center text-gray-500">
                <i class="fas fa-box-open text-4xl mb-3 text-gray-300"></i>
                <p class="text-lg">No modules found</p>
                <p class="text-sm mt-1">Create your first module to start organizing questions</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Module Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Questions</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($modules as $module): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($module['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?= $module['question_count'] ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?= date('M j, Y', strtotime($module['updated_at'])) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button onclick="openEditModal(<?= $module['id'] ?>, '<?= htmlspecialchars($module['name'], ENT_QUOTES, 'UTF-8') ?>')"
                                            class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</button>
                                    <button onclick="confirmDeleteModule(<?= $module['id'] ?>)"
                                            class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Module Modal -->
<div id="createModuleModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4 transition-opacity">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md transform transition-all">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Create New Module</h2>
                <button onclick="closeModal('createModuleModal')" class="text-gray-400 hover:text-gray-500 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST">
                <div class="mb-4">
                    <label for="moduleName" class="block text-sm font-medium text-gray-700 mb-1">Module Name</label>
                    <input type="text" name="name" id="moduleName" 
                           class="w-full border border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" 
                           placeholder="Enter module name" required>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeModal('createModuleModal')"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" name="create_module"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center">
                        <i class="fas fa-plus mr-2"></i> Create
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Module Modal -->
<div id="editModuleModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4 transition-opacity">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md transform transition-all">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Edit Module</h2>
                <button onclick="closeModal('editModuleModal')" class="text-gray-400 hover:text-gray-500 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="module_id" id="editModuleId">
                <div class="mb-4">
                    <label for="editModuleName" class="block text-sm font-medium text-gray-700 mb-1">Module Name</label>
                    <input type="text" name="name" id="editModuleName" 
                           class="w-full border border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" 
                           placeholder="Enter module name" required>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeModal('editModuleModal')"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" name="update_module"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center">
                        <i class="fas fa-save mr-2"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Module Modal -->
<div id="deleteModuleModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4 transition-opacity">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md transform transition-all">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Confirm Deletion</h2>
                <button onclick="closeModal('deleteModuleModal')" class="text-gray-400 hover:text-gray-500 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mb-6">
                <div class="flex items-center justify-center text-red-500 mb-4">
                    <i class="fas fa-exclamation-triangle text-4xl"></i>
                </div>
                <p class="text-gray-700 text-center">Are you sure you want to delete this module? This action cannot be undone.</p>
            </div>
            <div class="flex justify-end gap-3">
                <button onclick="closeModal('deleteModuleModal')"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <a id="deleteModuleLink" href="#"
                   class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors flex items-center">
                    <i class="fas fa-trash-alt mr-2"></i> Delete
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Modal functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.querySelector('div').classList.add('scale-100');
        modal.querySelector('div').classList.remove('scale-95');
    }, 10);
    document.body.classList.add('overflow-hidden');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.querySelector('div').classList.remove('scale-100');
    modal.querySelector('div').classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }, 150);
}

function openEditModal(id, name) {
    document.getElementById('editModuleId').value = id;
    document.getElementById('editModuleName').value = name;
    openModal('editModuleModal');
}

function confirmDeleteModule(id) {
    document.getElementById('deleteModuleLink').href = `module.php?delete_module=${id}`;
    openModal('deleteModuleModal');
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('fixed')) {
        closeModal(event.target.id);
    }
});

// Prevent propagation when clicking inside modal content
document.querySelectorAll('.modal-content').forEach(modal => {
    modal.addEventListener('click', function(event) {
        event.stopPropagation();
    });
});
</script>

<?php require "./templates/admin_footer.php"; ?>