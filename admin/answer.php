<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

// Database connection and common functions
require "../requires/common.php";
require "../requires/connect.php";
require "../requires/common_function.php";
$currentPage = basename($_SERVER['PHP_SELF']);
$pagetitle = "Q&A Dashboard";

// Handle Answer Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answer'])) {
    $questionId = intval($_POST['question_id']);
    $answer = trim($_POST['answer']);
    
    if (empty($answer)) {
        $_SESSION['error'] = "Answer cannot be empty";
    } else {
        $stmt = $mysqli->prepare("INSERT INTO answer (questionId, userId, answer) VALUES (?, ?, ?)");
        if (!$stmt) {
            $_SESSION['error'] = "Database error: " . $mysqli->error;
        } else {
            $stmt->bind_param("iis", $questionId, $_SESSION['id'], $answer);
            if ($stmt->execute()) {
                if ($_SESSION['role_id'] <= 2) {
                    $approveStmt = $mysqli->prepare("UPDATE question SET is_approve = TRUE WHERE id = ?");
                    $approveStmt->bind_param("i", $questionId);
                    $approveStmt->execute();
                    $approveStmt->close();
                }
                // Insert notification for the question owner
                $notificationStmt = $mysqli->prepare("INSERT INTO question_notifications (question_id, user_id, is_read) VALUES (?, ?, FALSE)");
                if ($notificationStmt) {
                    $notificationStmt->bind_param("ii", $questionId, $_SESSION['id']);
                    $notificationStmt->execute();
                    $notificationStmt->close();
                }
                

                
                $_SESSION['message'] = "Answer posted successfully!";
            } else {
                $_SESSION['error'] = "Failed to post answer: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    header('Location: '.$_SERVER['PHP_SELF'].(isset($_GET['question_id']) ? '?question_id='.$_GET['question_id'] : ''));
    exit();
}

// Handle Delete Question
if (isset($_GET['delete_question'])) {
    $id = intval($_GET['delete_question']);
    
    // First delete question likes to prevent foreign key constraint violation
    $deleteLikes = $mysqli->prepare("DELETE FROM question_likes WHERE questionId = ?");
    if ($deleteLikes) {
        $deleteLikes->bind_param("i", $id);
        $deleteLikes->execute();
        $deleteLikes->close();
    }
    
    // Then delete answers
    $deleteAnswers = $mysqli->prepare("DELETE FROM answer WHERE questionId = ?");
    if ($deleteAnswers) {
        $deleteAnswers->bind_param("i", $id);
        $deleteAnswers->execute();
        $deleteAnswers->close();
    }
    
    // Finally delete the question
    $stmt = $mysqli->prepare("DELETE FROM question WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Question and all related data deleted successfully.";
        } else {
            $_SESSION['error'] = "Delete failed: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Database error: " . $mysqli->error;
    }
    header("Location: answer.php");
    exit();
}

// Handle Delete Answer
if (isset($_GET['delete_answer'])) {
    $id = intval($_GET['delete_answer']);
    $stmt = $mysqli->prepare("DELETE FROM answer WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Answer deleted successfully.";
        } else {
            $_SESSION['error'] = "Delete failed: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Database error: " . $mysqli->error;
    }
    header("Location: answer.php".(isset($_GET['question_id']) ? '?question_id='.$_GET['question_id'] : ''));
    exit();
}

// Check if we're viewing a specific question
$specificQuestion = null;
if (isset($_GET['question_id']) && is_numeric($_GET['question_id'])) {
    $questionId = intval($_GET['question_id']);
    
    // Get the specific question with all details
    $questionQuery = $mysqli->prepare("
        SELECT q.*, 
               u.name as user_name, 
               u.profile_photo as user_photo,
               u.role_id as user_role,
               m.name as module_name
        FROM question q
        JOIN users u ON q.userId = u.id
        JOIN module m ON q.moduleId = m.id
        WHERE q.id = ?
    ");
    $questionQuery->bind_param("i", $questionId);
    $questionQuery->execute();
    $specificQuestion = $questionQuery->get_result()->fetch_assoc();
    
    if ($specificQuestion) {
        // Get answers for this question
        $answersQuery = $mysqli->prepare("
            SELECT a.*, u.name, u.profile_photo, u.role_id
            FROM answer a
            JOIN users u ON a.userId = u.id
            WHERE a.questionId = ?
            ORDER BY a.created_at ASC
        ");
        $answersQuery->bind_param("i", $questionId);
        $answersQuery->execute();
        $specificQuestion['answers'] = $answersQuery->get_result()->fetch_all(MYSQLI_ASSOC);

        $markRead = $mysqli->prepare("
            UPDATE question_notifications 
            SET is_read = TRUE 
            WHERE question_id = ? AND user_id IN (
            SELECT userId FROM question where id = ? )");
        $markRead->bind_param("ii", $questionId, $questionId);
        $markRead->execute();
        $markRead->close();
    }
}

// Get all questions with user and module details (only if not viewing a specific question)
$allQuestions = [];
if (!$specificQuestion) {

    // Build base query
    $baseQuery = "
        SELECT q.*, 
               u.name as user_name, 
               u.profile_photo as user_photo,
               u.role_id as user_role,
               m.name as module_name
        FROM question q
        JOIN users u ON q.userId = u.id
        JOIN module m ON q.moduleId = m.id
    ";
    
    // Add filters based on user role
    if ($_SESSION['role_id'] > 2) { // Student - only see approved questions and their own
        $baseQuery .= " WHERE (q.is_approve = TRUE OR q.userId = ?)";
        $params = [$_SESSION['id']];
    } else { // Teacher/Admin - see all questions
        $baseQuery .= " WHERE 1=1";
        $params = [];
    }

    
    
    // Add search filter if provided
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $searchTerm = "%".trim($_GET['search'])."%";
        $baseQuery .= " AND (q.title LIKE ? OR q.question LIKE ? OR m.name LIKE ?)";
        array_push($params, $searchTerm, $searchTerm, $searchTerm);
    }
    
    // Add module filter if provided
    if (isset($_GET['module']) && is_numeric($_GET['module'])) {
        $moduleId = intval($_GET['module']);
        $baseQuery .= " AND q.moduleId = ?";
        array_push($params, $moduleId);
    }
    
    $baseQuery .= " ORDER BY q.created_at DESC";
    
    $questionsQuery = $mysqli->prepare($baseQuery);
    
    // Bind parameters if needed
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $questionsQuery->bind_param($types, ...$params);
    }
    
    if ($questionsQuery && $questionsQuery->execute()) {
        $allQuestions = $questionsQuery->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get answer counts for each question
        foreach ($allQuestions as &$question) {
            $answersQuery = $mysqli->prepare("
                SELECT COUNT(*) as count
                FROM answer
                WHERE questionId = ?
            ");
            if ($answersQuery) {
                $answersQuery->bind_param("i", $question['id']);
                if ($answersQuery->execute()) {
                    $result = $answersQuery->get_result()->fetch_assoc();
                    $question['answer_count'] = $result['count'];
                }
                $answersQuery->close();
            }
        }
        unset($question);
    } else {
        $_SESSION['error'] = "Failed to load questions: " . $mysqli->error;
    }
}

// Get all modules for filter dropdown
$modules = [];
$modulesQuery = $mysqli->query("SELECT id, name FROM module ORDER BY name");
if ($modulesQuery) {
    $modules = $modulesQuery->fetch_all(MYSQLI_ASSOC);
}

require "./templates/admin_header.php";
require "./templates/admin_sidebar.php";
?>

<div class="container mx-auto px-4 py-8 w-full max-w-6xl">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">
                <?= $specificQuestion ? 'Question Details' : 'Q&A Dashboard' ?>
            </h1>
            <p class="text-gray-600 mt-2">
                <?= $specificQuestion ? 'View and answer this question' : 'Browse and answer student questions' ?>
            </p>
        </div>
        <?php if ($specificQuestion): ?>
            <a href="answer.php" class="flex items-center text-indigo-600 hover:text-indigo-800 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Back to all questions
            </a>
        <?php else: ?>
            <a href="javascript:void(0)" onclick="document.getElementById('askQuestionModal').classList.remove('hidden')" 
               class="flex items-center bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                <i class="fas fa-plus mr-2"></i> Ask Question
            </a>
        <?php endif; ?>
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

    <?php if (!$specificQuestion): ?>
        <!-- Search and Filter Bar -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
            <form method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <div class="relative">
                        <input type="text" name="search" placeholder="Search questions..." 
                               value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search'], ENT_QUOTES, 'UTF-8') : '' ?>"
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
                <div class="w-full md:w-48 relative">
                    <select name="module" class="w-full pl-3 pr-8 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent appearance-none bg-white">
                        <option value="">All Modules</option>
                        <?php foreach ($modules as $module): ?>
                            <option value="<?= $module['id'] ?>" <?= isset($_GET['module']) && $_GET['module'] == $module['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($module['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-chevron-down absolute right-0 top-1 text-gray-400 pointer-events-none" style="margin-top: 8px; margin-right: 12px;"></i>
                </div>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                    <i class="fas fa-filter mr-2"></i> Filter
                </button>
                <a href="answer.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition-colors shadow-sm flex items-center justify-center">
                    <i class="fas fa-sync-alt mr-2"></i> Reset
                </a>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($specificQuestion): ?>
        <!-- Single Question View -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="p-6">
                <!-- Question Header -->
                <div class="flex items-start justify-between mb-6">
                    <div class="flex items-start space-x-4">
                        <div class="relative">
                            <img src="<?= htmlspecialchars(!empty($specificQuestion['user_photo']) ? '../uploads/profiles/' . $specificQuestion['user_photo'] : '../img/image.png', ENT_QUOTES, 'UTF-8') ?>" 
                                 class="w-14 h-14 rounded-full object-cover border-2 border-white shadow">
             
                        </div>
                        <div>
                            <div class="flex items-center space-x-3">
                                <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($specificQuestion['user_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <?php if (!$specificQuestion['is_approve']): ?>
                                    <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-0.5 rounded-full">Pending Approval</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-sm text-gray-500 mt-1">
                                <i class="far fa-clock mr-1"></i> <?= date('M j, Y g:i a', strtotime($specificQuestion['created_at'])) ?>
                            </div>
                            <div class="text-sm text-gray-500 mt-1 flex items-center">
                                <i class="far fa-folder-open mr-1"></i>
                                <?= htmlspecialchars($specificQuestion['module_name'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                    </div>
                    <?php if ($_SESSION['role_id'] <= 2 || $_SESSION['id'] == $specificQuestion['userId']): ?>
                        <button onclick="confirmDeleteQuestion(<?= $specificQuestion['id'] ?>)"
                                class="text-gray-400 hover:text-red-500 transition-colors">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    <?php endif; ?>
                </div>
                
                <!-- Question Content -->
                <div class="pl-18 mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-3 break-words"><?= htmlspecialchars($specificQuestion['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                        <p class="text-gray-800 break-words w-100">
                            <?= htmlspecialchars($specificQuestion['question'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                </div>
                
                <!-- Answers Section -->
                <?php if (!empty($specificQuestion['answers'])): ?>
                    <div class="mt-8 pl-18">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 flex items-center">
                            <span class="bg-gray-200 h-px flex-1 mr-3"></span>
                            <?= count($specificQuestion['answers']) ?> <?= count($specificQuestion['answers']) === 1 ? 'Answer' : 'Answers' ?>
                            <span class="bg-gray-200 h-px flex-1 ml-3"></span>
                        </h3>
                        <div class="space-y-4">
                            <?php foreach ($specificQuestion['answers'] as $answer): ?>
                                <div class="bg-white p-4 rounded-lg border border-gray-100 shadow-xs">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-start space-x-3">
                                            <div class="relative">
                                                <img src="<?= htmlspecialchars(!empty($answer['profile_photo']) ? '../uploads/profiles/' . $answer['profile_photo'] : '../img/image.png', ENT_QUOTES, 'UTF-8') ?>" 
                                                     class="w-10 h-10 rounded-full object-cover border-2 border-white shadow">
                                                <?php if ($answer['role_id'] <= 2): ?>
                                                    <span class="absolute -bottom-1 -right-1 bg-blue-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full border-2 border-white">T</span>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div class="flex items-center space-x-2">
                                                    <span class="font-medium text-gray-900"><?= htmlspecialchars($answer['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                                </div>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    <i class="far fa-clock mr-1"></i> <?= date('M j, Y g:i a', strtotime($answer['created_at'])) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if ($_SESSION['role_id'] <= 2 || $_SESSION['id'] == $answer['userId']): ?>
                                            <button onclick="confirmDeleteAnswer(<?= $answer['id'] ?>)"
                                                    class="text-gray-400 hover:text-red-500 transition-colors text-sm">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-3 pl-13">
                                        <p class="text-gray-700 break-words"><?= htmlspecialchars($answer['answer'], ENT_QUOTES, 'UTF-8') ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Answer Form -->
                <div class="mt-8 pl-18">
                    <form method="POST">
                        <input type="hidden" name="question_id" value="<?= $specificQuestion['id'] ?>">
                        <div class="flex items-start space-x-4">
                            <?php
                                $userId = $_SESSION['id'];
                                $userQuery = $mysqli->prepare("SELECT profile_photo FROM users WHERE id = ?");
                                $userQuery->bind_param("i", $userId);
                                $userQuery->execute();
                                $userResult = $userQuery->get_result();
                                $userPhoto = $userResult->fetch_assoc()['profile_photo'];
                                $userQuery->close();
                            ?>
                            <img src="<?= htmlspecialchars(!empty($userPhoto) ? '../uploads/profiles/' . $userPhoto : '../img/image.png', ENT_QUOTES, 'UTF-8') ?>"
                                 class="w-10 h-10 rounded-full object-cover border-2 border-white shadow">
                            <div class="flex-1">
                                <label for="answer" class="block text-sm font-medium text-gray-700 mb-1">Your Answer</label>
                                <textarea name="answer" id="answer"
                                      class="w-full border border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" 
                                      rows="4" 
                                      placeholder="Type your answer here..."></textarea>
                                <div class="mt-3 flex justify-end">
                                    <button type="submit" 
                                            class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition-colors shadow-sm flex items-center">
                                        <i class="fas fa-paper-plane mr-2"></i> Post Answer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- All Questions View - Grouped by User -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <?php if (empty($allQuestions)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="far fa-question-circle text-4xl mb-3 text-gray-300"></i>
                    <p class="text-lg">No questions found</p>
                    <p class="text-sm mt-1">When students post questions, they'll appear here</p>
                </div>
            <?php else: ?>
                <?php
                // Group questions by user
                $users = [];
                foreach ($allQuestions as $question) {
                    $userId = $question['userId'];
                    if (!isset($users[$userId])) {
                        $users[$userId] = [
                            'user_name' => $question['user_name'],
                            'user_photo' => $question['user_photo'],
                            'user_role' => $question['user_role'],
                            'questions' => [],
                            'total_answers' => 0,
                            'last_activity' => $question['created_at']
                        ];
                    }
                    $users[$userId]['questions'][] = $question;
                    $users[$userId]['total_answers'] += $question['answer_count'];
                    
                    // Update last activity if this question is newer
                    if (strtotime($question['created_at']) > strtotime($users[$userId]['last_activity'])) {
                        $users[$userId]['last_activity'] = $question['created_at'];
                    }
                }
                ?>
        
                <div class="divide-y divide-gray-100">
                    <?php foreach ($users as $userId => $user): ?>
                        <div class="p-6 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start justify-between cursor-pointer" onclick="toggleUserQuestions(this)">
                                <div class="flex items-start w-full relative">
                                    <?php 
                                    
                                    $notiCount = 0;
                                    $notiStmt = $mysqli->prepare("
                                        SELECT COUNT(*) 
                                        FROM question_notifications 
                                        WHERE user_id = ? AND is_read = 0       
                                    ");
                                    if ($notiStmt) {
                                        $notiStmt->bind_param("i", $userId);
                                        if ($notiStmt->execute()) {
                                            $notiStmt->bind_result($notiCount);
                                            $notiStmt->fetch();
                                        }
                                        $notiStmt->close();
                                    }
                                    ?>

                                    <?php if ($notiCount > 0): ?>
                                        <span class="absolute -top-4 left-14 z-100 bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full border-2 border-white">
                                            <?= $notiCount ?>
                                        </span>
                                    <?php endif; ?>
                                    <div class="relative">
                                        <img src="<?= htmlspecialchars(!empty($user['user_photo']) ? '../uploads/profiles/' . $user['user_photo'] : '../img/image.png', ENT_QUOTES, 'UTF-8') ?>"
                                             class="w-12 h-12 rounded-full mr-8 object-cover border-2 border-white shadow">
                                        <?php if ($user['user_role'] <= 2): ?>
                                            <span class="absolute -bottom-1 -right-1 bg-blue-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full border-2 border-white">T</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($user['user_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                                            <span class="text-xs text-gray-500">
                                                <?= date('M j, Y', strtotime($user['last_activity'])) ?>
                                            </span>
                                        </div>
                                        
                                        <div class="flex items-center mt-1 text-sm text-gray-500">
                                            <span class="mr-4">
                                                <i class="far fa-question-circle mr-1"></i> <?= count($user['questions']) ?> questions
                                            </span>
                                            <span>
                                                <i class="far fa-comment mr-1"></i> <?= $user['total_answers'] ?> answers
                                            </span>
                                        </div>
                                        
                                        <!-- User's questions (collapsible) -->
                                        <div class="mt-4 pl-2 border-l-2 border-gray-200 user-questions" style="display: none;">
                                            <?php foreach ($user['questions'] as $question): ?>
                                                <div class="mb-5 pb-5 border-b border-gray-100 last:border-0 last:mb-0 last:pb-0">
                                                    <div class="text-xs text-gray-500 mb-2 flex items-center">
                                                        <i class="far fa-clock mr-1"></i> <?= date('M j, Y g:i a', strtotime($question['created_at'])) ?>
                                                        <span class="mx-2">•</span>
                                                        <i class="far fa-folder-open mr-1"></i>
                                                        <?= htmlspecialchars($question['module_name'], ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                    <h4 class="text-lg font-medium text-gray-900 mb-2"><?= htmlspecialchars($question['title'], ENT_QUOTES, 'UTF-8') ?></h4>
                                                    
                                            
                                                    <!-- View link -->
                                                    <div class="flex justify-between items-center">
                                                        <a href="?question_id=<?= $question['id'] ?>" 
                                                           class="text-indigo-600 hover:text-indigo-800 text-sm flex items-center transition-colors">
                                                            <i class="fas fa-eye mr-1"></i> View Conversation
                                                        </a>
                                                        <?php if ($_SESSION['role_id'] <= 2 || $_SESSION['id'] == $question['userId']): ?>
                                                            <button onclick="event.stopPropagation(); confirmDeleteQuestion(<?= $question['id'] ?>)"
                                                                    class="text-red-500 hover:text-red-700 text-sm flex items-center transition-colors">
                                                                <i class="fas fa-trash-alt mr-1"></i> Delete
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <script>
        function toggleUserQuestions(element) {
            const container = element.closest('.flex.items-start.justify-between');
            const questionsSection = container.querySelector('.user-questions');
            const icon = container.querySelector('i');
            
            if (questionsSection.style.display === 'none') {
                questionsSection.style.display = 'block';
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                questionsSection.style.display = 'none';
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        }
        </script>
    <?php endif; ?>
</div>

<!-- Ask Question Modal -->
<div id="askQuestionModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4 transition-opacity">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl transform transition-all">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Ask a Question</h2>
                <button onclick="closeModal('askQuestionModal')" class="text-gray-400 hover:text-gray-500 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="submit_question.php">
                <div class="mb-4">
                    <label for="question_title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" name="title" id="question_title" required
                           class="w-full border border-gray-200 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" 
                           placeholder="What's your question about?">
                </div>
                <div class="mb-4">
                    <label for="question_module" class="block text-sm font-medium text-gray-700 mb-1">Module</label>
                    <select name="moduleId" id="question_module" required
                            class="w-full border border-gray-200 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">Select a module</option>
                        <?php foreach ($modules as $module): ?>
                            <option value="<?= $module['id'] ?>"><?= htmlspecialchars($module['name'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="question_content" class="block text-sm font-medium text-gray-700 mb-1">Details</label>
                    <textarea name="question" id="question_content" required rows="6"
                              class="w-full border border-gray-200 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" 
                              placeholder="Provide details about your question..."></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeModal('askQuestionModal')"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                           class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center">
                        <i class="fas fa-paper-plane mr-2"></i> Submit Question
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Question Modal -->
<div id="deleteQuestionModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4 transition-opacity">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md transform transition-all">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Confirm Deletion</h2>
                <button onclick="closeModal('deleteQuestionModal')" class="text-gray-400 hover:text-gray-500 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mb-6">
                <div class="flex items-center justify-center text-red-500 mb-4">
                    <i class="fas fa-exclamation-triangle text-4xl"></i>
                </div>
                <p class="text-gray-700 text-center">Are you sure you want to delete this question? All answers will also be permanently deleted.</p>
            </div>
            <div class="flex justify-end gap-3">
                <button onclick="closeModal('deleteQuestionModal')"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <a id="deleteQuestionLink" href="#"
                   class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors flex items-center">
                    <i class="fas fa-trash-alt mr-2"></i> Delete
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Delete Answer Modal -->
<div id="deleteAnswerModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4 transition-opacity">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md transform transition-all">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Confirm Deletion</h2>
                <button onclick="closeModal('deleteAnswerModal')" class="text-gray-400 hover:text-gray-500 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mb-6">
                <div class="flex items-center justify-center text-red-500 mb-4">
                    <i class="fas fa-exclamation-triangle text-4xl"></i>
                </div>
                <p class="text-gray-700 text-center">Are you sure you want to delete this answer? This action cannot be undone.</p>
            </div>
            <div class="flex justify-end gap-3">
                <button onclick="closeModal('deleteAnswerModal')"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <a id="deleteAnswerLink" href="#"
                   class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors flex items-center">
                    <i class="fas fa-trash-alt mr-2"></i> Delete
                </a>
            </div>
        </div>
    </div>
</div>
<!-- <script src="../assets/js/jquery-3.7.1.min.js"></script> -->
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

function confirmDeleteQuestion(id) {
    document.getElementById('deleteQuestionLink').href = `answer.php?delete_question=${id}`;
    openModal('deleteQuestionModal');
}

function confirmDeleteAnswer(id) {
    document.getElementById('deleteAnswerLink').href = `answer.php?delete_answer=${id}`;
    openModal('deleteAnswerModal');
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('fixed')) {
        closeModal(event.target.id);
    }
});

// Prevent propagation when clicking inside modal content
document.querySelectorAll('#askQuestionModal > div, #deleteQuestionModal > div, #deleteAnswerModal > div').forEach(modal => {
    modal.addEventListener('click', function(event) {
        event.stopPropagation();
    });
});

// Auto-resize textareas
// function autoResize(textarea) {
//     textarea.style.height = 'auto';
//     textarea.style.height = (textarea.scrollHeight) + 'px';
// }

// document.querySelectorAll('textarea').forEach(textarea => {
//     textarea.addEventListener('input', function() {
//         autoResize(this);
//     });
//     // Initialize
//     autoResize(textarea);
// });
</script>

<?php require "./templates/admin_footer.php"; ?>