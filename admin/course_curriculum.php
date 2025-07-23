<?php
session_start();
require "../requires/common.php";  
require "../requires/title.php";  
require "../requires/connect.php";  
$currentPage = basename($_SERVER['PHP_SELF']);  
$pagetitle = "Currisulum";
require './templates/admin_header.php';  
require './templates/admin_sidebar.php';  

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = intval($_POST['courseId']);
    $subjectId = intval($_POST['subjectId']);
    $lessonIds = isset($_POST['lessonIds']) ? $_POST['lessonIds'] : [];

    if ($courseId > 0 && $subjectId > 0 && count($lessonIds) > 0) {
        foreach ($lessonIds as $lessonId) {
            $lessonId = intval($lessonId);
            $checkQuery = $mysqli->query("SELECT id FROM course_subject WHERE courseId = $courseId AND subjectId = $subjectId AND lessonId = $lessonId");
            if ($checkQuery->num_rows == 0) {
                $mysqli->query("INSERT INTO course_subject (courseId, subjectId, lessonId) VALUES ($courseId, $subjectId, $lessonId)");
            }
        }
        $success = "Curriculum connection added successfully!";
    } else {
        $error = "Please select all required fields.";
    }
}

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

                <!-- Form Card -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-200 max-w-3xl mx-auto">
                    <!-- Card Header -->
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                            <!-- <svg class="w-5 h-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg> -->
                            Create New Curriculum Connection
                        </h2>
                    </div>

                    <!-- Flash Messages -->
                    <?php if (!empty($success)): ?>
                        <div class="fixed top-6 right-6 z-50 animate-fade-in">
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 shadow-lg flex items-start max-w-md">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3 flex-1">
                                    <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($success) ?></p>
                                </div>
                                <button class="ml-4 text-green-500 hover:text-green-700" onclick="this.parentElement.parentElement.remove()">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    <?php elseif (!empty($error)): ?>
                        <div class="fixed top-6 right-6 z-50 animate-fade-in">
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 shadow-lg flex items-start max-w-md">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3 flex-1">
                                    <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($error) ?></p>
                                </div>
                                <button class="ml-4 text-red-500 hover:text-red-700" onclick="this.parentElement.parentElement.remove()">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Form Content -->
                    <form method="POST" class="p-6 space-y-6">
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

                        <!-- Form Actions -->
                        <div class="flex justify-end pt-4">
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
    </div>

    <script src="../assets/js/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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
    </script>
</body>
</html>