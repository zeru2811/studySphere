<?php
session_start();
require "../requires/common.php";  
require "../requires/title.php";  
require "../requires/connect.php";  
$currentPage = basename($_SERVER['PHP_SELF']);  
$pagetitle = "Learning Paths";  

// File upload configuration
$uploadDir = '../uploads/learning_paths/';
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxFileSize = 2 * 1024 * 1024; // 2MB

// Create upload directory if it doesn't exist
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Handle Add Learning Path Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['title'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description'] ?? '');
        $difficulty = $_POST['difficulty'] ?? 'Beginner';
        $category = $_POST['category'] ?? 'Programming';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_certificate = isset($_POST['is_certificate']) ? 1 : 0;
        $thumbnail_path = '';
        
        // Handle file upload
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $fileType = mime_content_type($_FILES['thumbnail']['tmp_name']);
            $fileSize = $_FILES['thumbnail']['size'];
            
            if (!in_array($fileType, $allowedTypes)) {
                $_SESSION['error'] = "Only JPG, PNG, GIF, and WEBP images are allowed.";
                header("Location: learning_path.php");
                exit;
            }
            
            if ($fileSize > $maxFileSize) {
                $_SESSION['error'] = "File size must be less than 2MB.";
                header("Location: learning_path.php");
                exit;
            }
            
            $extension = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $destination = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $destination)) {
                $thumbnail_path = '../uploads/learning_paths/' . $filename;
            } else {
                $_SESSION['error'] = "Failed to upload thumbnail.";
                header("Location: learning_path.php");
                exit;
            }
        }
        
        if ($title !== '') {
            $stmt = $mysqli->prepare("INSERT INTO learning_path (title, description, thumbnail_url, difficulty, category, is_active, is_featured, is_certificate) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssssiii", $title, $description, $thumbnail_path, $difficulty, $category, $is_active, $is_featured, $is_certificate);
                $stmt->execute();
                $stmt->close();
                $_SESSION['message'] = "Learning path added successfully.";
            } else {
                $_SESSION['error'] = "Failed to prepare statement.";
            }
        } else {
            $_SESSION['error'] = "Title cannot be empty.";
        }
    } elseif (isset($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        $title = trim($_POST['edit_title']);
        $description = trim($_POST['edit_description'] ?? '');
        $difficulty = $_POST['edit_difficulty'] ?? 'Beginner';
        $category = $_POST['edit_category'] ?? 'Programming';
        $is_active = isset($_POST['edit_is_active']) ? 1 : 0;
        $is_featured = isset($_POST['edit_is_featured']) ? 1 : 0;
        $is_certificate = isset($_POST['edit_is_certificate']) ? 1 : 0;
        $thumbnail_path = $_POST['existing_thumbnail'] ?? '';
        
        // Handle file upload for edit
        if (isset($_FILES['edit_thumbnail']) && $_FILES['edit_thumbnail']['error'] === UPLOAD_ERR_OK) {
            $fileType = mime_content_type($_FILES['edit_thumbnail']['tmp_name']);
            $fileSize = $_FILES['edit_thumbnail']['size'];
            
            if (!in_array($fileType, $allowedTypes)) {
                $_SESSION['error'] = "Only JPG, PNG, GIF, and WEBP images are allowed.";
                header("Location: learning_path.php");
                exit;
            }
            
            if ($fileSize > $maxFileSize) {
                $_SESSION['error'] = "File size must be less than 2MB.";
                header("Location: learning_path.php");
                exit;
            }
            
            $extension = pathinfo($_FILES['edit_thumbnail']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $destination = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['edit_thumbnail']['tmp_name'], $destination)) {
                // Delete old thumbnail if it exists
                if (!empty($thumbnail_path)){
                    $oldFile = '../' . $thumbnail_path;
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }
                $thumbnail_path = 'uploads/learning_paths/' . $filename;
            } else {
                $_SESSION['error'] = "Failed to upload thumbnail.";
                header("Location: learning_path.php");
                exit;
            }
        }
        
        if ($title !== '') {
            $stmt = $mysqli->prepare("UPDATE learning_path SET title = ?, description = ?, thumbnail_url = ?, difficulty = ?, category = ?, is_active = ?, is_featured = ?, is_certificate = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("sssssiiii", $title, $description, $thumbnail_path, $difficulty, $category, $is_active, $is_featured, $is_certificate, $id);
                $stmt->execute();
                $stmt->close();
                $_SESSION['message'] = "Learning path updated successfully.";
            } else {
                $_SESSION['error'] = "Failed to prepare statement.";
            }
        } else {
            $_SESSION['error'] = "Title cannot be empty.";
        }
    }
    header("Location: learning_path.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // First get the thumbnail path to delete the file
    $result = $mysqli->query("SELECT thumbnail_url FROM learning_path WHERE id = $id");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!empty($row['thumbnail_url'])) {
            $filePath = '../' . $row['thumbnail_url'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
    
    $stmt = $mysqli->prepare("DELETE FROM learning_path WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Learning path deleted successfully.";
        } else {
            if ($stmt->errno == 1451) {
                $_SESSION['error'] = "Cannot delete this learning path because it is linked to other data.";
            } else {
                $_SESSION['error'] = "Delete failed: " . $stmt->error;
            }
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Failed to prepare delete statement.";
    }

    header("Location: learning_path.php");
    exit;
}

// Fetch Learning Paths
$learning_paths = [];
$result = $mysqli->query("SELECT * FROM learning_path ORDER BY id DESC");
while ($row = $result->fetch_assoc()) {
    $learning_paths[] = $row;
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
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Learning Path Management</h1>
            <p class="text-gray-600">Manage all learning paths in the system</p>
        </div>
        <button onclick="openModal('addModal')" 
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
            <i class="fas fa-plus"></i>
            <span>Add Learning Path</span>
        </button>
    </div>

    <!-- Learning Paths Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Difficulty</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Featured</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Certificate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Updated At</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($learning_paths) > 0): ?>
                        <?php foreach ($learning_paths as $path): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $path['id'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <div class="flex items-center gap-3">
                                        <?php if (!empty($path['thumbnail_url'])): ?>
                                            <img src="<?= htmlspecialchars($path['thumbnail_url']) ?>" alt="Thumbnail" class="w-10 h-10 rounded-md object-cover">
                                        <?php endif; ?>
                                        <div>
                                            <div><?= htmlspecialchars($path['title']) ?></div>
                                            <?php if (!empty($path['description'])): ?>
                                                <div class="text-xs text-gray-500 truncate max-w-xs"><?= htmlspecialchars($path['description']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?= htmlspecialchars($path['difficulty']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        <?= htmlspecialchars($path['category']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $path['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $path['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $path['is_featured'] ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' ?>">
                                        <?= $path['is_featured'] ? 'Yes' : 'No' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $path['is_certificate'] ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' ?>">
                                        <?= $path['is_certificate'] ? 'Yes' : 'No' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('M j, Y h:i A', strtotime($path['created_at'])) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('M j, Y h:i A', strtotime($path['updated_at'])) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <button onclick="openEditModal(
                                            <?= $path['id'] ?>, 
                                            '<?= htmlspecialchars(addslashes($path['title'])) ?>',
                                            '<?= htmlspecialchars(addslashes($path['description'])) ?>',
                                            '<?= htmlspecialchars(addslashes($path['thumbnail_url'])) ?>',
                                            '<?= htmlspecialchars(addslashes($path['difficulty'])) ?>',
                                            '<?= htmlspecialchars(addslashes($path['category'])) ?>',
                                            <?= $path['is_active'] ?>,
                                            <?= $path['is_featured'] ?>,
                                            <?= $path['is_certificate'] ?>
                                        )"
                                                class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="confirmDelete(<?= $path['id'] ?>)"
                                                class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="px-6 py-4 text-center text-sm text-gray-500">No learning paths found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Learning Path Modal -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md transform transition-all">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Add New Learning Path</h2>
                <button onclick="closeModal('addModal')" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="learning_path.php" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title*</label>
                    <input type="text" name="title" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Difficulty</label>
                    <select name="difficulty" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="Beginner">Beginner</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Advanced">Advanced</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="Programming">Programming</option>
                        <option value="Design">Design</option>
                        <option value="Business">Business</option>
                        <option value="Data Science">Data Science</option>
                        <option value="Marketing">Marketing</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Thumbnail Image</label>
                    <div class="mt-1 flex items-center">
                        <label for="thumbnail" class="cursor-pointer bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <span>Choose File</span>
                            <input id="thumbnail" name="thumbnail" type="file" class="sr-only" accept="image/*">
                        </label>
                        <span id="file-name" class="ml-3 text-sm text-gray-500">No file chosen</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">JPEG, PNG, GIF or WEBP (Max. 2MB)</p>
                </div>
                <div class="mb-4 space-y-2">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" checked>
                        <label for="is_active" class="ml-2 block text-sm text-gray-700">Active</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_featured" id="is_featured" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="is_featured" class="ml-2 block text-sm text-gray-700">Featured</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="is_certificate" id="is_certificate" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="is_certificate" class="ml-2 block text-sm text-gray-700">Certificate Available</label>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('addModal')"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Save Learning Path
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Edit Learning Path Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md transform transition-all">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Edit Learning Path</h2>
                <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="learning_path.php" enctype="multipart/form-data">
                <input type="hidden" name="edit_id" id="edit_id">
                <input type="hidden" name="existing_thumbnail" id="existing_thumbnail">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title*</label>
                    <input type="text" name="edit_title" id="edit_title" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="edit_description" id="edit_description"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Difficulty</label>
                    <select name="edit_difficulty" id="edit_difficulty" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="Beginner">Beginner</option>
                        <option value="Intermediate">Intermediate</option>
                        <option value="Advanced">Advanced</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="edit_category" id="edit_category" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="Programming">Programming</option>
                        <option value="Design">Design</option>
                        <option value="Business">Business</option>
                        <option value="Data Science">Data Science</option>
                        <option value="Marketing">Marketing</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Thumbnail</label>
                    <img id="current-thumbnail" src="" class="h-20 w-20 object-cover rounded-md mb-2" onerror="this.style.display='none'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Change Thumbnail</label>
                    <div class="mt-1 flex items-center">
                        <label for="edit_thumbnail" class="cursor-pointer bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <span>Choose File</span>
                            <input id="edit_thumbnail" name="edit_thumbnail" type="file" class="sr-only" accept="image/*">
                        </label>
                        <span id="edit-file-name" class="ml-3 text-sm text-gray-500">No file chosen</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">JPEG, PNG, GIF or WEBP (Max. 2MB)</p>
                </div>
                <div class="mb-4 space-y-2">
                    <div class="flex items-center">
                        <input type="checkbox" name="edit_is_active" id="edit_is_active" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="edit_is_active" class="ml-2 block text-sm text-gray-700">Active</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="edit_is_featured" id="edit_is_featured" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="edit_is_featured" class="ml-2 block text-sm text-gray-700">Featured</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="edit_is_certificate" id="edit_is_certificate" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="edit_is_certificate" class="ml-2 block text-sm text-gray-700">Certificate Available</label>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('editModal')"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Update Learning Path
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
                <p class="text-gray-700">Are you sure you want to delete this learning path? This action cannot be undone.</p>
            </div>
            <div class="flex justify-end gap-2">
                <button onclick="closeModal('deleteModal')"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <a id="deleteLink" href="#"
                   class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Delete Learning Path
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

        // File input change handlers
        document.getElementById('thumbnail').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'No file chosen';
            document.getElementById('file-name').textContent = fileName;
        });

        document.getElementById('edit_thumbnail').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'No file chosen';
            document.getElementById('edit-file-name').textContent = fileName;
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

        function openEditModal(id, title, description, thumbnail_url, difficulty, category, is_active, is_featured, is_certificate) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_description').value = description;
            document.getElementById('existing_thumbnail').value = thumbnail_url;
            document.getElementById('edit_difficulty').value = difficulty;
            document.getElementById('edit_category').value = category;
            document.getElementById('edit_is_active').checked = is_active == 1;
            document.getElementById('edit_is_featured').checked = is_featured == 1;
            document.getElementById('edit_is_certificate').checked = is_certificate == 1;

            // Set current thumbnail preview
            const thumbnailImg = document.getElementById('current-thumbnail');
            if (thumbnail_url) {
                thumbnailImg.src = thumbnail_url;
                thumbnailImg.style.display = 'block';
            } else {
                thumbnailImg.style.display = 'none';
            }
        
            openModal('editModal');
        }

        function confirmDelete(id) {
            document.getElementById('deleteLink').href = `learning_path.php?delete=${id}`;
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
<?php
require './templates/admin_footer.php';
?>


