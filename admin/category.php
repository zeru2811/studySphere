<?php
session_start();
require "../requires/common.php";
require "../requires/title.php";
require "../requires/connect.php";
$currentPage = basename($_SERVER['PHP_SELF']);
$pagetitle = "Categories";

// Handle Add/Edit Category Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['category_name'])) {
        $name = trim($_POST['category_name']);

        if ($name !== '') {
            $stmt = $mysqli->prepare("INSERT INTO category (name) VALUES (?)");
            if ($stmt) {
                $stmt->bind_param("s", $name);
                $stmt->execute();
                $stmt->close();
                $_SESSION['message'] = "Category added successfully.";
                header("Location: category.php");
                exit;
            } else {
                $_SESSION['error'] = "Failed to prepare statement.";
            }
        } else {
            $_SESSION['error'] = "Category name is required.";
            header("Location: category.php");
            exit;
        }
    } elseif (isset($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        $name = trim($_POST['edit_name']);

        if ($name !== '') {
            $stmt = $mysqli->prepare("UPDATE category SET name = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("si", $name, $id);
                $stmt->execute();
                $stmt->close();
                $_SESSION['message'] = "Category updated successfully.";
            } else {
                $_SESSION['error'] = "Failed to prepare statement.";
            }
        } else {
            $_SESSION['error'] = "Category name is required.";
        }
    }
    header("Location: category.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $mysqli->prepare("DELETE FROM category WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Category deleted successfully.";
        } else {
            if ($stmt->errno == 1451) {
                $_SESSION['error'] = "Cannot delete this category because it is linked to other data.";
            } else {
                $_SESSION['error'] = "Delete failed: " . $stmt->error;
            }
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Failed to prepare delete statement.";
    }
    header("Location: category.php");
    exit;
}

// Fetch Categories
$categories = [];
$result = $mysqli->query("SELECT * FROM category ORDER BY id DESC");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pagetitle?> | StudySphere Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
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
    <link rel="stylesheet" href="./css/main.css">
</head>
<?php require './templates/admin_sidebar.php'; ?>
<body class="bg-gray-50 w-full min-h-screen p-4 md:p-6">

<div class="max-w-6xl w-full pt-10 mx-auto">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="fixed top-4 right-4 z-50">
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg shadow-lg" role="alert">
                <div class="flex items-center">
                    <div class="flex-shrink-0"><i class="fas fa-check-circle text-green-500"></i></div>
                    <div class="ml-3"><p class="text-sm font-medium"><?= $_SESSION['message']; unset($_SESSION['message']); ?></p></div>
                    <button class="ml-auto -mx-1.5 -my-1.5 text-green-500 hover:text-green-700" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="fixed top-4 right-4 z-50">
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-lg" role="alert">
                <div class="flex items-center">
                    <div class="flex-shrink-0"><i class="fas fa-exclamation-circle text-red-500"></i></div>
                    <div class="ml-3"><p class="text-sm font-medium"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p></div>
                    <button class="ml-auto -mx-1.5 -my-1.5 text-red-500 hover:text-red-700" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Category Management</h1>
            <p class="text-gray-600">Manage all categories in the system</p>
        </div>
        <button onclick="openModal('addModal')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
            <i class="fas fa-plus"></i><span>Add Category</span>
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($categories) > 0): ?>
                        <?php foreach ($categories as $category): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm"><?= $category['id'] ?></td>
                                <td class="px-6 py-4 text-sm"><?= htmlspecialchars($category['name']) ?></td>
                                <td class="px-6 py-4 text-sm"><?= date('M j, Y h:i A', strtotime($category['created_at'])) ?></td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <div class="flex justify-end gap-2">
                                        <button onclick='openEditModal(
                                            <?= json_encode($category['id']) ?>,
                                            <?= json_encode($category['name']) ?>
                                        )' class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="confirmDelete(<?= $category['id'] ?>)" class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No categories found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Add New Category</h2>
                <button onclick="closeModal('addModal')" class="text-gray-400 hover:text-gray-500"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" action="category.php">
                <input type="text" name="category_name" placeholder="Category Name" class="w-full mb-3 p-2 border rounded" required>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeModal('addModal')" class="px-4 py-2 border rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Edit Category</h2>
                <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-500"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" action="category.php">
                <input type="hidden" name="edit_id" id="edit_id">
                <input type="text" name="edit_name" id="edit_name" placeholder="Category Name" class="w-full mb-3 p-2 border rounded" required>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeModal('editModal')" class="px-4 py-2 border rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Confirm Deletion</h2>
                <button onclick="closeModal('deleteModal')" class="text-gray-400 hover:text-gray-500"><i class="fas fa-times"></i></button>
            </div>
            <p class="mb-6">Are you sure you want to delete this category?</p>
            <div class="flex justify-end gap-2">
                <button onclick="closeModal('deleteModal')" class="px-4 py-2 border rounded">Cancel</button>
                <a id="deleteLink" href="#" class="px-4 py-2 bg-red-600 text-white rounded">Delete Category</a>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script>
    function openModal(modalId) { document.getElementById(modalId).classList.remove('hidden'); }
    function closeModal(modalId) { document.getElementById(modalId).classList.add('hidden'); }
    function openEditModal(id, name) {
        $('#edit_id').val(id);
        $('#edit_name').val(name);
        openModal('editModal');
    }
    function confirmDelete(id) {
        document.getElementById('deleteLink').href = `category.php?delete=${id}`;
        openModal('deleteModal');
    }
</script>
</body>
</html>