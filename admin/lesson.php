<?php
session_start();
require "../requires/common.php";
require "../requires/title.php";
require "../requires/connect.php";
$currentPage = basename($_SERVER['PHP_SELF']);
$pagetitle = "Lessons";


// Handle Add/Edit Lesson Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['lesson_title'])) {
        $title = trim($_POST['lesson_title']);
        $url = trim($_POST['lesson_url']);
        $description = trim($_POST['lesson_description']);
        $hours = intval($_POST['hours']);
        $minutes = intval($_POST['minutes']);
        $duration = sprintf('%02d:%02d:00', $hours, $minutes);

        if ($title !== '' && $url !== '' && $duration !== '') {
            $stmt = $mysqli->prepare("INSERT INTO lessons (lessonUrl, title, description, duration) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssss", $url, $title, $description, $duration);
                $stmt->execute();
                $stmt->close();
                $_SESSION['message'] = "Lesson added successfully.";
                header("Location: lesson.php");
                exit;
            } else {
                $_SESSION['error'] = "Failed to prepare statement.";
            }
        } else {
            $_SESSION['error'] = "Title, URL, and Duration are required.";
            header("Location: lesson.php");
            exit;
        }
    } elseif (isset($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        $title = trim($_POST['edit_title']);
        $url = trim($_POST['edit_url']);
        $description = trim($_POST['edit_description']);
        $hours = intval($_POST['hours']);
        $minutes = intval($_POST['minutes']);
        $duration = sprintf('%02d:%02d:00', $hours, $minutes);

        if ($title !== '' && $url !== '' && $duration !== '') {
            $stmt = $mysqli->prepare("UPDATE lessons SET lessonUrl = ?, title = ?, description = ?, duration = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ssssi", $url, $title, $description, $duration, $id);
                $stmt->execute();
                $stmt->close();
                $_SESSION['message'] = "Lesson updated successfully.";
            } else {
                $_SESSION['error'] = "Failed to prepare statement.";
            }
        } else {
            $_SESSION['error'] = "Title, URL, and Duration are required.";
        }
    }
    header("Location: lesson.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $mysqli->prepare("DELETE FROM lessons WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Lesson deleted successfully.";
        } else {
            if ($stmt->errno == 1451) {
                $_SESSION['error'] = "Cannot delete this lesson because it is linked to other data.";
            } else {
                $_SESSION['error'] = "Delete failed: " . $stmt->error;
            }
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Failed to prepare delete statement.";
    }
    header("Location: lesson.php");
    exit;
}

// Fetch Lessons
$lessons = [];
$result = $mysqli->query("SELECT * FROM lessons ORDER BY id DESC");
while ($row = $result->fetch_assoc()) {
    $lessons[] = $row;
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
            <h1 class="text-3xl font-bold text-gray-800">Lesson Management</h1>
            <p class="text-gray-600">Manage all lessons in the system</p>
        </div>
        <button onclick="openModal('addModal')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
            <i class="fas fa-plus"></i><span>Add Lesson</span>
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">URL</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($lessons) > 0): ?>
                        <?php foreach ($lessons as $lesson): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm"><?= $lesson['id'] ?></td>
                                <td class="px-6 py-4 text-sm"><?= htmlspecialchars($lesson['title']) ?></td>
                                <td class="px-6 py-4 text-sm"><?= htmlspecialchars($lesson['lessonUrl']) ?></td>
                                <td class="px-6 py-4 text-sm"><?php $timeParts = explode(":", $lesson['duration']); echo $timeParts[0] . ":" . $timeParts[1]; ?></td>
                                <td class="px-6 py-4 text-sm"><?= date('M j, Y h:i A', strtotime($lesson['created_at'])) ?></td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <div class="flex justify-end gap-2">
                                        <button onclick='openEditModal(
                                            <?= json_encode($lesson['id']) ?>,
                                            <?= json_encode($lesson['lessonUrl']) ?>,
                                            <?= json_encode($lesson['title']) ?>,
                                            <?= json_encode($lesson['description']) ?>,
                                            <?= json_encode($lesson['duration']) ?>
                                        )' class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="confirmDelete(<?= $lesson['id'] ?>)" class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No lessons found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Lesson Modal -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Add New Lesson</h2>
                <button onclick="closeModal('addModal')" class="text-gray-400 hover:text-gray-500"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" action="lesson.php">
                <input type="text" name="lesson_title" placeholder="Title" class="w-full mb-3 p-2 border rounded" required>
                <input type="text" name="lesson_url" placeholder="Lesson URL" class="w-full mb-3 p-2 border rounded" required>
                <textarea name="lesson_description" placeholder="Description" class="w-full mb-3 p-2 border rounded"></textarea>
                <div class="flex gap-2 mb-3">
                    <input type="number" name="hours" min="0" placeholder="Hours" class="w-1/2 p-2 border rounded">
                    <input type="number" name="minutes" min="0" max="59" placeholder="Minutes" class="w-1/2 p-2 border rounded" required>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeModal('addModal')" class="px-4 py-2 border rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Save Lesson</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Lesson Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Edit Lesson</h2>
                <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-500"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" action="lesson.php">
                <input type="hidden" name="edit_id" id="edit_id">
                <input type="text" name="edit_title" id="edit_title" placeholder="Title" class="w-full mb-3 p-2 border rounded" required>
                <input type="text" name="edit_url" id="edit_url" placeholder="Lesson URL" class="w-full mb-3 p-2 border rounded" required>
                <textarea name="edit_description" id="edit_description" placeholder="Description" class="w-full mb-3 p-2 border rounded"></textarea>
                <div class="flex gap-2 mb-3">
                    <input type="number" name="hours" id="edit_hours" min="0" placeholder="Hours" class="w-1/2 p-2 border rounded">
                    <input type="number" name="minutes" id="edit_minutes" min="0" max="59" placeholder="Minutes" class="w-1/2 p-2 border rounded" required>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeModal('editModal')" class="px-4 py-2 border rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Update Lesson</button>
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
            <p class="mb-6">Are you sure you want to delete this lesson?</p>
            <div class="flex justify-end gap-2">
                <button onclick="closeModal('deleteModal')" class="px-4 py-2 border rounded">Cancel</button>
                <a id="deleteLink" href="#" class="px-4 py-2 bg-red-600 text-white rounded">Delete Lesson</a>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script>
    function openModal(modalId) { document.getElementById(modalId).classList.remove('hidden'); }
    function closeModal(modalId) { document.getElementById(modalId).classList.add('hidden'); }
    function openEditModal(id, url, title, description, duration) {
        $('#edit_id').val(id);
        $('#edit_url').val(url);
        $('#edit_title').val(title);
        $('#edit_description').val(description);
        const parts = duration.split(':');
        const hours = parseInt(parts[0]) || 0;
        const minutes = parseInt(parts[1]) || 0;
        $('#edit_hours').val(hours);
        $('#edit_minutes').val(minutes);
        openModal('editModal');
    }
    function confirmDelete(id) {
        document.getElementById('deleteLink').href = `lesson.php?delete=${id}`;
        openModal('deleteModal');
    }
</script>
</body>
</html>
