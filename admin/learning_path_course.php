<?php
session_start();
require "../requires/common.php";  
require "../requires/title.php";  
require "../requires/connect.php";  
$currentPage = basename($_SERVER['PHP_SELF']);  
$pagetitle = "Learning Path Courses";  

// Handle Add Course to Learning Path
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['learning_pathId']) && isset($_POST['courseId'])) {
    $learning_pathId = intval($_POST['learning_pathId']);
    $courseId = intval($_POST['courseId']);
    
    // Get the next sequence number
    $result = $mysqli->query("SELECT MAX(sequence) as max_seq FROM learning_path_courseId WHERE learning_pathId = $learning_pathId");
    $nextSequence = ($result && $row = $result->fetch_assoc()) ? $row['max_seq'] + 1 : 1;
    
    $stmt = $mysqli->prepare("INSERT INTO learning_path_courseId (learning_pathId, courseId, sequence) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iii", $learning_pathId, $courseId, $nextSequence);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Course added to learning path successfully.";
        } else {
            $_SESSION['error'] = "Failed to add course: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Failed to prepare statement.";
    }
    header("Location: learning_path_course.php?path_id=" . $learning_pathId);
    exit;
}

// Handle Remove Course from Learning Path
if (isset($_GET['remove'])) {
    $id = intval($_GET['remove']);
    $learning_pathId = intval($_GET['path_id']);
    
    $stmt = $mysqli->prepare("DELETE FROM learning_path_courseId WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Course removed from learning path successfully.";
            
            // Re-sequence the remaining courses
            $result = $mysqli->query("SELECT id FROM learning_path_courseId WHERE learning_pathId = $learning_pathId ORDER BY sequence");
            if ($result) {
                $sequence = 1;
                while ($row = $result->fetch_assoc()) {
                    $updateStmt = $mysqli->prepare("UPDATE learning_path_courseId SET sequence = ? WHERE id = ?");
                    $updateStmt->bind_param("ii", $sequence, $row['id']);
                    $updateStmt->execute();
                    $updateStmt->close();
                    $sequence++;
                }
            }
        } else {
            $_SESSION['error'] = "Failed to remove course: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Failed to prepare delete statement.";
    }
    header("Location: learning_path_course.php?path_id=" . $learning_pathId);
    exit;
}

// Handle Sequence Update
if (isset($_POST['update_sequence'])) {
    $learning_pathId = intval($_POST['learning_pathId']);
    $sequences = $_POST['sequence'];
    
    foreach ($sequences as $id => $sequence) {
        $id = intval($id);
        $sequence = intval($sequence);
        $stmt = $mysqli->prepare("UPDATE learning_path_courseId SET sequence = ? WHERE id = ? AND learning_pathId = ?");
        $stmt->bind_param("iii", $sequence, $id, $learning_pathId);
        $stmt->execute();
        $stmt->close();
    }
    
    $_SESSION['message'] = "Course sequence updated successfully.";
    header("Location: learning_path_course.php?path_id=" . $learning_pathId);
    exit;
}

// Get current learning path details
$learning_path = null;
$learning_pathId = isset($_GET['path_id']) ? intval($_GET['path_id']) : 0;
if ($learning_pathId > 0) {
    $result = $mysqli->query("SELECT * FROM learning_path WHERE id = $learning_pathId");
    $learning_path = $result->fetch_assoc();
}

// Get all learning paths for dropdown
$learning_paths = [];
$result = $mysqli->query("SELECT id, title, thumbnail_url FROM learning_path ORDER BY title");
while ($row = $result->fetch_assoc()) {
    $learning_paths[] = $row;
}

// Get courses in current learning path
$path_courses = [];
if ($learning_pathId > 0) {
    $query = "SELECT lpc.id, lpc.sequence, c.id as course_id, c.name, c.title, c.thumbnail 
              FROM learning_path_courseId lpc
              JOIN courses c ON lpc.courseId = c.id
              WHERE lpc.learning_pathId = $learning_pathId
              ORDER BY lpc.sequence";
    $result = $mysqli->query($query);
    while ($row = $result->fetch_assoc()) {
        $path_courses[] = $row;
    }
}

// Get all available courses (not already in this learning path)
$available_courses = [];
if ($learning_pathId > 0) {
    $query = "SELECT c.id, c.name, c.title 
              FROM courses c
              WHERE c.id NOT IN (
                  SELECT courseId FROM learning_path_courseId WHERE learning_pathId = $learning_pathId
              )
              ORDER BY c.name";
    $result = $mysqli->query($query);
    while ($row = $result->fetch_assoc()) {
        $available_courses[] = $row;
    }
}
?>

<?php
require './templates/admin_header.php';
require './templates/admin_sidebar.php';
?>

<body class="bg-gray-50 w-full min-h-screen p-4 md:p-6">

<div class="max-w-6xl back w-full pt-10 mx-auto">
    <!-- Notification Messages -->
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
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Learning Path Courses</h1>
            <p class="text-gray-600">Manage courses in learning paths</p>
        </div>
        
        <!-- Learning Path Selector -->
        <div class="flex items-center gap-4">
            <form method="GET" action="learning_path_course.php" class="flex items-center gap-2">
                <select name="path_id" onchange="this.form.submit()" class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Select Learning Path</option>
                    <?php foreach ($learning_paths as $path): ?>
                        <option value="<?= $path['id'] ?>" <?= ($learning_pathId == $path['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($path['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            
            <?php if ($learning_pathId > 0): ?>
                <button onclick="openModal('addCourseModal')" 
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                    <i class="fas fa-plus"></i>
                    <span>Add Course</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($learning_path): ?>
        <!-- Learning Path Info -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200 mb-6 p-4 flex">
            <!-- <div class="w-24 h-14 mr-4 flex-shrink-0">
                <img src="<?= htmlspecialchars($learning_path['thumbnail_url']) ?> " alt="<?= htmlspecialchars($learning_path['title']) ?>"  class="object-cover">
            </div> -->
            <div class="">
                <h2 class="text-xl font-semibold text-gray-800 mb-2"><?= htmlspecialchars($learning_path['title']) ?></h2>
                <?php if (!empty($learning_path['description'])): ?>
                    <p class="text-gray-600"><?= htmlspecialchars($learning_path['description']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Courses in Learning Path -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <form method="POST" action="learning_path_course.php">
                    <input type="hidden" name="learning_pathId" value="<?= $learning_pathId ?>">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sequence</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="sortable">
                            <?php if (count($path_courses) > 0): ?>
                                <?php foreach ($path_courses as $course): ?>
                                    <tr class="hover:bg-gray-50 transition-colors" data-id="<?= $course['id'] ?>">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="hidden" name="sequence[<?= $course['id'] ?>]" value="<?= $course['sequence'] ?>">
                                            <span class="text-sm text-gray-500"><?= $course['sequence'] ?></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <?php if (!empty($course['thumbnail'])): ?>
                                                    <img src="../uploads/thumbnails/<?= htmlspecialchars($course['thumbnail']) ?>" alt="Course thumbnail" class="w-10 h-10 rounded-md object-cover">
                                                <?php endif; ?>
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($course['name']) ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($course['title']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex justify-end gap-2">
                                                <button type="button" onclick="confirmRemove(<?= $course['id'] ?>, <?= $learning_pathId ?>)" 
                                                        class="text-red-600 hover:text-red-900">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No courses in this learning path yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <?php if (count($path_courses) > 0): ?>
                        <div class="p-4 border-t border-gray-200 flex justify-end">
                            <button type="submit" name="update_sequence" 
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Update Sequence
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200 p-6 text-center">
            <p class="text-gray-500">Please select a learning path to view or manage its courses.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Add Course Modal -->
<?php if ($learning_pathId > 0): ?>
<div id="addCourseModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md transform transition-all">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Add Course to Learning Path</h2>
                <button onclick="closeModal('addCourseModal')" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="learning_path_course.php">
                <input type="hidden" name="learning_pathId" value="<?= $learning_pathId ?>">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Course</label>
                    <select name="courseId" required class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">-- Select Course --</option>
                        <?php foreach ($available_courses as $course): ?>
                            <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['name']) ?> - <?= htmlspecialchars($course['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeModal('addCourseModal')"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Add Course
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Remove Confirmation Modal -->
<div id="removeModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md transform transition-all">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Confirm Removal</h2>
                <button onclick="closeModal('removeModal')" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mb-6">
                <p class="text-gray-700">Are you sure you want to remove this course from the learning path?</p>
            </div>
            <div class="flex justify-end gap-2">
                <button onclick="closeModal('removeModal')"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <a id="removeLink" href="#"
                   class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Remove Course
                </a>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
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

        // Initialize sortable
        if (document.getElementById('sortable')) {
            new Sortable(document.getElementById('sortable'), {
                animation: 150,
                onEnd: function() {
                    // Update sequence numbers after sorting
                    $('#sortable tr').each(function(index) {
                        var id = $(this).data('id');
                        $(this).find('input[name^="sequence"]').val(index + 1);
                        $(this).find('span').text(index + 1);
                    });
                }
            });
        }
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

    function confirmRemove(id, pathId) {
        document.getElementById('removeLink').href = `learning_path_course.php?remove=${id}&path_id=${pathId}`;
        openModal('removeModal');
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