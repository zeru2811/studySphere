<?php
session_start();
require "../requires/common.php";  
require "../requires/title.php";  
require "../requires/connect.php";  
$currentPage = basename($_SERVER['PHP_SELF']);  
$pagetitle = "Curriculum";
require './templates/admin_header.php';  

$success = isset($_SESSION['message']) ? $_SESSION['message'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['message']);
unset($_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_id'])) {
        // Handle Edit
        $id = intval($_POST['edit_id']);
        $courseId = intval($_POST['edit_courseId']);
        $subjectId = intval($_POST['edit_subjectId']);
        $lessonId = intval($_POST['edit_lessonId']);
        $displayOrder = intval($_POST['edit_display_order']);
        $displayOrder = max(0, $displayOrder);

        if ($courseId > 0 && $subjectId > 0 && $lessonId > 0) {
            $stmt = $mysqli->prepare("UPDATE course_subject SET courseId = ?, subjectId = ?, lessonId = ?, display_order = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("iiiii", $courseId, $subjectId, $lessonId, $displayOrder, $id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = "Curriculum connection updated successfully!";
                } else {
                    $_SESSION['error'] = "Update failed: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $_SESSION['error'] = "Failed to prepare update statement.";
            }
        } else {
            $_SESSION['error'] = "Please select all required fields.";
        }
        header("Location: course_curriculum.php");
        exit;
    } else {
        // Handle Add
        $courseId = intval($_POST['courseId']);
        $subjectId = intval($_POST['subjectId']);
        $lessonIds = isset($_POST['lessonIds']) ? $_POST['lessonIds'] : [];
        $displayOrder = intval($_POST['display_order']);
        $displayOrder = max(0, $displayOrder);

        if ($courseId > 0 && $subjectId > 0 && count($lessonIds) > 0) {
            foreach ($lessonIds as $lessonId) {
                $lessonId = intval($lessonId);
                $checkQuery = $mysqli->query("SELECT id FROM course_subject WHERE courseId = $courseId AND subjectId = $subjectId AND lessonId = $lessonId");
                if ($checkQuery->num_rows == 0) {
                    $mysqli->query("INSERT INTO course_subject (courseId, subjectId, lessonId, display_order) VALUES ($courseId, $subjectId, $lessonId, $displayOrder)");
                }
            }
            $_SESSION['message'] = "Curriculum connection added successfully!";
        } else {
            $_SESSION['error'] = "Please select all required fields.";
        }
        header("Location: course_curriculum.php");
        exit;
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $mysqli->prepare("DELETE FROM course_subject WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Curriculum connection deleted successfully.";
        } else {
            $_SESSION['error'] = "Delete failed: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Failed to prepare delete statement.";
    }
    header("Location: course_curriculum.php");
    exit;
}

// Fetch existing curriculum connections
$curriculum = [];
$query = "SELECT cs.id, cs.courseId, cs.subjectId, cs.lessonId, c.name AS course_name, s.name AS subject_name, l.title AS lesson_title, cs.display_order 
          FROM course_subject cs
          JOIN courses c ON cs.courseId = c.id
          JOIN subject s ON cs.subjectId = s.id
          JOIN lessons l ON cs.lessonId = l.id
          ORDER BY cs.display_order, cs.id DESC";
$result = $mysqli->query($query);
while ($row = $result->fetch_assoc()) {
    $curriculum[] = $row;
}

// Fetch dropdown options
$courseResult = $mysqli->query("SELECT id, name FROM courses ORDER BY name");
$subjectResult = $mysqli->query("SELECT id, name FROM subject ORDER BY name");
$lessonResult = $mysqli->query("SELECT id, title FROM lessons ORDER BY title");
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container--default .select2-selection--single,
    .select2-container--default .select2-selection--multiple {
        min-height: 46px;
        border: 1px solid #e2e8f0 !important;
        border-radius: 8px !important;
        padding: 8px 12px;
        transition: all 0.2s ease;
    }
    .select2-container--default .select2-selection--single:hover,
    .select2-container--default .select2-selection--multiple:hover {
        border-color: #a0aec0 !important;
    }
    .select2-container--default .select2-selection--single:focus,
    .select2-container--default .select2-selection--multiple:focus {
        border-color: #667eea !important;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 44px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 30px;
        color: #4a5568;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #ebf4ff;
        border-color: #c3dafe;
        color: #3c366b;
        border-radius: 4px;
        padding: 2px 8px;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #667eea;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__display {
        margin-left: 1rem;
    }

    element.style {
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        top: 2px;
    }
</style>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <?php  require './templates/admin_sidebar.php';  ?>
    <div class="flex flex-col min-h-screen w-full">
        <!-- Main Content -->
        <div class="flex-1 w-full">
            <div class="container mx-auto px-4 py-8">
                <!-- Page Header -->
                <div class="mb-8 text-center">
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">
                        <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                            Course Curriculum Builder
                        </span>
                    </h1>
                    <p class="text-gray-600 max-w-2xl mx-auto">
                        Connect courses with subjects and lessons to create comprehensive learning paths
                    </p>
                </div>

                <!-- Flash Messages -->
                <?php if (!empty($success)): ?>
                    <div class="fixed top-4 right-4 z-50">
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg shadow-lg" role="alert">
                            <div class="flex items-center">
                                <div class="flex-shrink-0"><i class="fas fa-check-circle text-green-500"></i></div>
                                <div class="ml-3"><p class="text-sm font-medium"><?= htmlspecialchars($success) ?></p></div>
                                <button class="ml-auto -mx-1.5 -my-1.5 text-green-500 hover:text-green-700" onclick="this.parentElement.parentElement.remove()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php elseif (!empty($error)): ?>
                    <div class="fixed top-4 right-4 z-50">
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-lg" role="alert">
                            <div class="flex items-center">
                                <div class="flex-shrink-0"><i class="fas fa-exclamation-circle text-red-500"></i></div>
                                <div class="ml-3"><p class="text-sm font-medium"><?= htmlspecialchars($error) ?></p></div>
                                <button class="ml-auto -mx-1.5 -my-1.5 text-red-500 hover:text-red-700" onclick="this.parentElement.parentElement.remove()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Add Button -->
                <div class="flex justify-end mb-6">
                    <button onclick="openModal('addModal')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                        <i class="fas fa-plus"></i><span>Add Curriculum Connection</span>
                    </button>
                </div>

                <!-- Curriculum List -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Course</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lesson</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Display Order</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (count($curriculum) > 0): ?>
                                    <?php foreach ($curriculum as $item): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 text-sm"><?= $item['id'] ?></td>
                                            <td class="px-6 py-4 text-sm"><?= htmlspecialchars($item['course_name']) ?></td>
                                            <td class="px-6 py-4 text-sm"><?= htmlspecialchars($item['subject_name']) ?></td>
                                            <td class="px-6 py-4 text-sm"><?= htmlspecialchars($item['lesson_title']) ?></td>
                                            <td class="px-6 py-4 text-sm"><?= $item['display_order'] ?></td>
                                            <td class="px-6 py-4 text-right text-sm">
                                                <div class="flex justify-end gap-2">
                                                    <button onclick="openEditModal(
                                                        <?= $item['id'] ?>,
                                                        <?= $item['courseId'] ?>,
                                                        <?= $item['subjectId'] ?>,
                                                        <?= $item['lessonId'] ?>,
                                                        <?= $item['display_order'] ?>
                                                    )" class="text-indigo-600 hover:text-indigo-900"><i class="fas fa-edit"></i></button>
                                                    <button onclick="confirmDelete(<?= $item['id'] ?>)" class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No curriculum connections found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Curriculum Modal -->
    <div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-3xl">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Create New Curriculum Connection</h2>
                    <button onclick="closeModal('addModal')" class="text-gray-400 hover:text-gray-500"><i class="fas fa-times"></i></button>
                </div>
                <form method="POST" action="course_curriculum.php" class="space-y-6">
                    <!-- Course Selection -->
                    <div class="space-y-2">
                        <label for="courseId" class="block text-sm font-medium text-gray-700">
                            Course <span class="text-red-500">*</span>
                        </label>
                        <select name="courseId" id="courseId" required class="select2 w-full">
                            <option value="">Select a course</option>
                            <?php while ($course = $courseResult->fetch_assoc()): ?>
                                <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Subject Selection -->
                    <div class="space-y-2">
                        <label for="subjectId" class="block text-sm font-medium text-gray-700">
                            Subject <span class="text-red-500">*</span>
                        </label>
                        <select name="subjectId" id="subjectId" required class="select2 w-full">
                            <option value="">Select a subject</option>
                            <?php while ($subject = $subjectResult->fetch_assoc()): ?>
                                <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Lessons Selection -->
                    <div class="space-y-2">
                        <label for="lessonIds" class="block text-sm font-medium text-gray-700">
                            Lessons <span class="text-red-500">*</span>
                            <span class="text-xs text-gray-500 ml-1">(Select multiple)</span>
                        </label>
                        <select name="lessonIds[]" id="lessonIds" multiple required class="select2 w-full">
                            <?php while ($lesson = $lessonResult->fetch_assoc()): ?>
                                <option value="<?= $lesson['id'] ?>"><?= htmlspecialchars($lesson['title']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Display Order -->
                    <div class="space-y-2">
                        <label for="display_order" class="block text-sm font-medium text-gray-700">
                            Display Order <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="display_order" id="display_order" value="0" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                        <p class="text-xs text-gray-500">Set the order in which this connection will be displayed in the curriculum. Default is 0.</p>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end pt-4 gap-2">
                        <button type="button" onclick="closeModal('addModal')" class="px-4 py-2 border rounded">Cancel</button>
                        <button type="submit" class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-150">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Create Connection
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Curriculum Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-3xl">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Edit Curriculum Connection</h2>
                    <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-500"><i class="fas fa-times"></i></button>
                </div>
                <form method="POST" action="course_curriculum.php" class="space-y-6">
                    <input type="hidden" name="edit_id" id="edit_id">
                    
                    <!-- Course Selection -->
                    <div class="space-y-2">
                        <label for="edit_courseId" class="block text-sm font-medium text-gray-700">
                            Course <span class="text-red-500">*</span>
                        </label>
                        <select name="edit_courseId" id="edit_courseId" required class="select2 w-full">
                            <option value="">Select a course</option>
                            <?php 
                            $courseResult->data_seek(0); // Reset pointer to beginning
                            while ($course = $courseResult->fetch_assoc()): ?>
                                <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Subject Selection -->
                    <div class="space-y-2">
                        <label for="edit_subjectId" class="block text-sm font-medium text-gray-700">
                            Subject <span class="text-red-500">*</span>
                        </label>
                        <select name="edit_subjectId" id="edit_subjectId" required class="select2 w-full">
                            <option value="">Select a subject</option>
                            <?php 
                            $subjectResult->data_seek(0); // Reset pointer to beginning
                            while ($subject = $subjectResult->fetch_assoc()): ?>
                                <option value="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Lesson Selection -->
                    <div class="space-y-2">
                        <label for="edit_lessonId" class="block text-sm font-medium text-gray-700">
                            Lesson <span class="text-red-500">*</span>
                        </label>
                        <select name="edit_lessonId" id="edit_lessonId" required class="select2 w-full">
                            <option value="">Select a lesson</option>
                            <?php 
                            $lessonResult->data_seek(0); // Reset pointer to beginning
                            while ($lesson = $lessonResult->fetch_assoc()): ?>
                                <option value="<?= $lesson['id'] ?>"><?= htmlspecialchars($lesson['title']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Display Order -->
                    <div class="space-y-2">
                        <label for="edit_display_order" class="block text-sm font-medium text-gray-700">
                            Display Order <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="edit_display_order" id="edit_display_order" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                        <p class="text-xs text-gray-500">Set the order in which this connection will be displayed in the curriculum.</p>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end pt-4 gap-2">
                        <button type="button" onclick="closeModal('editModal')" class="px-4 py-2 border rounded">Cancel</button>
                        <button type="submit" class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-150">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Update Connection
                        </button>
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
                <p class="mb-6">Are you sure you want to delete this curriculum connection?</p>
                <div class="flex justify-end gap-2">
                    <button onclick="closeModal('deleteModal')" class="px-4 py-2 border rounded">Cancel</button>
                    <a id="deleteLink" href="#" class="px-4 py-2 bg-red-600 text-white rounded">Delete Connection</a>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%',
            placeholder: function() {
                return $(this).data('placeholder') || 'Select an option';
            },
            allowClear: true,
            closeOnSelect: $(this).is('[multiple]') ? false : true
        });

        // Auto-dismiss flash messages after 5 seconds
        setTimeout(function() {
            $('[role="alert"]').fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    });

    function openModal(modalId) { 
        document.getElementById(modalId).classList.remove('hidden'); 
    }
    
    function closeModal(modalId) { 
        document.getElementById(modalId).classList.add('hidden'); 
    }
    
    function confirmDelete(id) {
        document.getElementById('deleteLink').href = `course_curriculum.php?delete=${id}`;
        openModal('deleteModal');
    }
    
    function openEditModal(id, courseId, subjectId, lessonId, displayOrder) {
        $('#edit_id').val(id);
        $('#edit_courseId').val(courseId).trigger('change');
        $('#edit_subjectId').val(subjectId).trigger('change');
        $('#edit_lessonId').val(lessonId).trigger('change');
        $('#edit_display_order').val(displayOrder);
        openModal('editModal');
    }
    </script>
</body>
</html>