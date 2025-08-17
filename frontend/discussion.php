<?php 
session_start(); 
$type = "Discussion";
require '../requires/connect.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['id'];
$user_role = $_SESSION['role_id'];

// Handle new question submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_question'])) {
    $question = $mysqli->real_escape_string($_POST['question']);
    $title = $mysqli->real_escape_string($_POST['title']);
    $moduleId = intval($_POST['moduleId']);
    $userId = $_SESSION['id'];
    
    if($userId){
        
        $stmt = $mysqli->prepare("INSERT INTO question (question, title, moduleId, userId) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssii", $question, $title, $moduleId, $userId);
            
            if ($stmt->execute()) {
                $questionId = $stmt->insert_id;
                // Insert notification for the module owner
                $notificationStmt = $mysqli->prepare("INSERT INTO question_notifications (question_id, user_id, is_read) VALUES (?, ?, FALSE)");
                
                if ($notificationStmt) {

                    $notificationStmt->bind_param("ii", $questionId, $userId);
                  
                    $notificationStmt->execute();
                    $notificationStmt->close();
                }
                
                $_SESSION['message'] = "Question posted successfully!";
                header("Location: discussion.php");
                exit();
            } else {
                $error = "Error posting question: " . $mysqli->error;
            }
            $stmt->close();
        } else {
            $error = "Error preparing statement: " . $mysqli->error;
        }
    }
}

// Handle new module creation (if allowed)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_module'])) {
    $moduleName = $mysqli->real_escape_string($_POST['module_name']);
    
    $sql = "INSERT INTO module (name) VALUES ('$moduleName')";
    
    if ($mysqli->query($sql)) {
        $_SESSION['message'] = "Module created successfully!";
        header("Location: discussion.php");
        exit();
    } else {
        $error = "Error creating module: " . $mysqli->error;
    }
}

// Handle like action with POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_question'])) {
    $questionId = intval($_POST['like_question']);
    
    // Check if user already liked this question
    $checkLike = $mysqli->query("SELECT id FROM question_likes 
                               WHERE questionId = $questionId AND userId = $user_id");
    
    if ($checkLike->num_rows > 0) {
        // Unlike the question
        $mysqli->query("DELETE FROM question_likes WHERE questionId = $questionId AND userId = $user_id");
        $mysqli->query("UPDATE question SET likeCount = likeCount - 1 WHERE id = $questionId");
        $isLiked = false;
    } else {
        // Like the question
        $mysqli->query("INSERT INTO question_likes (questionId, userId) VALUES ($questionId, $user_id)");
        $mysqli->query("UPDATE question SET likeCount = likeCount + 1 WHERE id = $questionId");
        $isLiked = true;
    }
    
    // Get updated like count
    $result = $mysqli->query("SELECT likeCount FROM question WHERE id = $questionId");
    $likeCount = $result->fetch_assoc()['likeCount'];
    
    echo json_encode([
        'success' => true,
        'newCount' => $likeCount,
        'isLiked' => $isLiked
    ]);
    exit();
}

// Get search term and category filter if they exist
$search_term = isset($_GET['search']) ? $mysqli->real_escape_string($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;

// Base SQL query for questions
$sql = "SELECT q.id, q.title, q.question, q.likeCount, q.created_at, 
               m.name as module_name, u.name as asker, u.profile_photo as asker_photo,
               (SELECT COUNT(*) FROM answer a WHERE a.questionId = q.id) as answer_count
        FROM question q
        JOIN module m ON q.moduleId = m.id
        JOIN users u ON q.userId = u.id
        WHERE q.is_approve = TRUE";

// Add search term filter if provided
if (!empty($search_term)) {
    $sql .= " AND (q.title LIKE '%$search_term%' OR q.question LIKE '%$search_term%')";
}

// Add category filter if provided
if ($category_filter > 0) {
    $sql .= " AND q.moduleId = $category_filter";
}

// Complete the query with ordering
$sql .= " ORDER BY q.likeCount DESC, q.created_at DESC";

$questions = $mysqli->query($sql);

// Get all modules for dropdown and category list
$modules = $mysqli->query("SELECT * FROM module ORDER BY name");

// Get top questions (most liked) - respect filters if they exist
$topQuestionsQuery = "SELECT q.id, q.title, q.likeCount, m.name as module_name 
                      FROM question q
                      JOIN module m ON q.moduleId = m.id
                      WHERE q.is_approve = TRUE";

if (!empty($search_term)) {
    $topQuestionsQuery .= " AND (q.title LIKE '%$search_term%' OR q.question LIKE '%$search_term%')";
}

if ($category_filter > 0) {
    $topQuestionsQuery .= " AND q.moduleId = $category_filter";
}

$topQuestionsQuery .= " ORDER BY q.likeCount DESC LIMIT 3";
$topQuestions = $mysqli->query($topQuestionsQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Forum | TechPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .question-card {
            transition: all 0.2s ease;
        }
        .question-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .top-question {
            border-left: 4px solid #3b82f6;
            background-color: #f8fafc;
        }
        .vote-active {
            color: #3b82f6;
        }
        .answered-badge {
            background-color: #d1fae5;
            color: #065f46;
        }
        .pending-badge {
            background-color: #fef3c7;
            color: #92400e;
        }
        .active-category {
            background-color: rgb(37 99 235 / var(--tw-bg-opacity, 1)) !important;
            color: white !important;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php require '../templates/template_nav.php'; ?>
    <div class="container mx-auto px-4 py-8 w-full max-w-6xl">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
                <?= $_SESSION['message'] ?>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <button onclick="this.parentElement.parentElement.remove()">&times;</button>
                </span>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
                <?= $error ?>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <button onclick="this.parentElement.parentElement.remove()">&times;</button>
                </span>
            </div>
        <?php endif; ?>
        
        <!-- Main Content -->
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Questions List -->
            <div class="flex-1">
                <!-- Search Box -->
                <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
                    <form id="searchForm" method="GET" action="discussion.php">
                        <div class="relative">
                            <input type="text" name="search" placeholder="Search questions..." 
                                   value="<?= htmlspecialchars($search_term) ?>" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <button type="submit" class="absolute right-3 top-2 text-gray-500 hover:text-blue-500">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <input type="hidden" name="category" value="<?= $category_filter ?>">
                    </form>
                </div>
                
                <!-- Ask Question Button -->
                <button id="askQuestionBtn" class="w-full mb-6 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm transition-colors">
                    <i class="fas fa-plus-circle mr-2"></i> Ask a Question
                </button>
                
                <!-- Questions Section -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-question-circle text-blue-500"></i> 
                            <?php if ($category_filter > 0): ?>
                                <?php 
                                    $category_name = $mysqli->query("SELECT name FROM module WHERE id = $category_filter")->fetch_assoc()['name'];
                                    echo htmlspecialchars($category_name) . " Questions";
                                ?>
                            <?php else: ?>
                                All Questions
                            <?php endif; ?>
                        </h2>
                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <span><?= $questions->num_rows ?> questions</span>
                            <span class="text-gray-300">|</span>
                            <span>Sorted by: Most Popular</span>
                        </div>
                    </div>
    
                    <?php if ($questions->num_rows > 0): ?>
                        <?php while ($question = $questions->fetch_assoc()): 
                            // Check if current user has liked this question
                            $hasLiked = false;
                            if (isset($_SESSION['id'])) {
                                $checkLike = $mysqli->query("SELECT 1 FROM question_likes 
                                                           WHERE questionId = {$question['id']} AND userId = {$_SESSION['id']}");
                                $hasLiked = $checkLike->num_rows > 0;
                            }
                        ?>
                        <div class="w-full max-w-4xl question-card bg-white rounded-xl shadow-sm p-6 mb-4 <?= $question['likeCount'] > 10 ? 'top-question' : '' ?>">
                            <div class="flex items-start gap-4">
                                <!-- Vote Buttons -->
                                
                
                                <!-- Question Content -->
                                <div class="flex-1">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                                        <h3 class="font-bold text-gray-900">
                                            <a href="question_detail.php?id=<?= $question['id'] ?>" class="hover:text-blue-600">
                                                <?= htmlspecialchars($question['title']) ?>
                                            </a>
                                        </h3>
                                        <div class="flex gap-2">
                                            <span class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">
                                                <?= htmlspecialchars($question['module_name']) ?>
                                            </span>
                                            <span class="text-xs px-2 py-1 <?= $question['answer_count'] > 0 ? 'answered-badge' : 'pending-badge' ?> rounded-full">
                                                <?= $question['answer_count'] > 0 ? 'Answered' : 'Pending' ?>
                                            </span>
                                        </div>
                                    </div>
                    
                                    <p class="text-gray-600 mb-4">
                                        <?= nl2br(htmlspecialchars(substr($question['question'], 0, 20))) ?>
                                        <?= strlen($question['question']) > 20 ? '...' : '' ?>
                                    </p>
                    
                                    <div class="flex flex-wrap items-center justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center">
                                                <img src="<?= htmlspecialchars(!empty($question['asker_photo']) ? '../uploads/profiles/' . $question['asker_photo'] : '../img/image.png', ENT_QUOTES, 'UTF-8') ?>" 
                                                 alt="<?= htmlspecialchars($question['asker']) ?>" 
                                                        class="rounded-full object-cover w-full h-full">
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($question['asker']) ?></p>
                                                <p class="text-xs text-gray-500">Asked <?= date('M d, Y', strtotime($question['created_at'])) ?></p>
                                            </div>
                                        </div>
                        
                                        <div class="flex items-center gap-4">
                                            <div class="flex flex-col items-center">
                                                <button onclick="toggleLike(<?= $question['id'] ?>)" 
                                                        class="vote-btn <?= $hasLiked ? 'vote-active' : '' ?> text-gray-500 hover:text-blue-500 transition-colors">
                                                    <i class="fas fa-thumbs-up <?= $hasLiked ? 'text-blue-500' : 'text-gray-400' ?> me-1"></i>
                                                    <span class="text-gray-900 my-1" id="votes-<?= $question['id'] ?>">
                                                        <?= $question['likeCount'] ?>
                                                    </span>
                                                </button>
                                            </div>
                                            <a href="question_detail.php?id=<?= $question['id'] ?>" class="text-blue-600 hover:text-blue-800 text-sm">
                                                <?= $question['answer_count'] ?> <?= $question['answer_count'] == 1 ? 'answer' : 'answers' ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="bg-white rounded-xl shadow-sm p-6 text-center">
                            <p class="text-gray-500">
                                <?php if (!empty($search_term)): ?>
                                    No questions found for "<?= htmlspecialchars($search_term) ?>"
                                <?php elseif ($category_filter > 0): ?>
                                    No questions in this category yet.
                                <?php else: ?>
                                    No questions yet. Be the first to ask!
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <script>
                // Toggle like function with AJAX
                function toggleLike(questionId) {
                    if (!<?= isset($_SESSION['id']) ? 'true' : 'false' ?>) {
                        window.location.href = 'login.php';
                        return;
                    }

                    fetch('discussion.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `like_question=${questionId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const likeBtn = document.querySelector(`.vote-btn[onclick="toggleLike(${questionId})"]`);
                            const thumbsUpIcon = likeBtn.querySelector('i.fas');
                            const likeCount = document.getElementById(`like-count-${questionId}`);

                            // Toggle active state
                            likeBtn.classList.toggle('vote-active');
                            thumbsUpIcon.classList.toggle('text-blue-500');
                            thumbsUpIcon.classList.toggle('text-gray-400');

                            // Update count everywhere
                            document.querySelectorAll(`[id^='like-count-${questionId}'], [id^='votes-${questionId}']`).forEach(el => {
                                el.textContent = data.newCount;
                            });

                            location.reload();
                        } else {
                            alert('Error processing your like');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while processing your like');
                    });
                }
                </script>
            </div>
            
            <!-- Sidebar -->
            <div class="lg:w-80 space-y-6">
                <!-- Categories -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Categories</h2>
                    <ul class="space-y-2">
                        <li>
                            <a href="discussion.php?category=0<?= !empty($search_term) ? '&search=' . urlencode($search_term) : '' ?>" 
                               class="flex justify-between items-center px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors <?= $category_filter == 0 ? 'active-category' : '' ?>">
                                <span class="text-gray-700 <?= $category_filter == 0 ? 'active-category' : '' ?>">All Categories</span>
                                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full ">
                                    <?php 
                                        $all_count = $mysqli->query("SELECT COUNT(*) as count FROM question WHERE is_approve = TRUE")->fetch_assoc()['count'];
                                        echo $all_count;
                                    ?>
                                </span>
                            </a>
                        </li>
                        <?php 
                        $moduleCounts = $mysqli->query("
                            SELECT m.id, m.name, COUNT(q.id) as question_count 
                            FROM module m
                            LEFT JOIN question q ON m.id = q.moduleId AND q.is_approve = TRUE
                            GROUP BY m.id
                            ORDER BY m.name
                        ");
                        
                        while ($module = $moduleCounts->fetch_assoc()): 
                        ?>
                        <li>
                            <a href="discussion.php?category=<?= $module['id'] ?><?= !empty($search_term) ? '&search=' . urlencode($search_term) : '' ?>" 
                               class="flex justify-between items-center px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors <?= $category_filter == $module['id'] ? 'active-category' : '' ?>">
                                <span class="text-gray-700 <?= $category_filter == $module['id'] ? 'active-category' : '' ?>"><?= htmlspecialchars($module['name']) ?></span>
                                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full"><?= $module['question_count'] ?></span>
                            </a>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
                
                <!-- Guidelines -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Forum Guidelines</h2>
                    <ul class="space-y-3 text-sm text-gray-600">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span>Be specific with your questions</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span>Search before asking to avoid duplicates</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span>Upvote helpful answers</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check-circle text-green-500 mt-1"></i>
                            <span>Be respectful to others</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Ask Question Modal -->
        <div id="askQuestionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-2xl">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Ask a Question</h3>
                    <button id="closeModalBtn" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form method="POST" action="discussion.php">
                    <div class="mb-4">
                        <label for="questionTitle" class="block text-sm font-medium text-gray-700 mb-1">Question Title</label>
                        <input type="text" id="questionTitle" name="title" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="What's your question? Be specific." required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="questionCategory" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select id="questionCategory" name="moduleId" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            <option value="">Select a category</option>
                            <?php while ($module = $modules->fetch_assoc()): ?>
                                <option value="<?= $module['id'] ?>"><?= htmlspecialchars($module['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="questionDetails" class="block text-sm font-medium text-gray-700 mb-1">Details</label>
                        <textarea id="questionDetails" name="question" rows="5" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Provide details about your question..." required></textarea>
                    </div>
                    
                    <div class="flex justify-end gap-3">
                        <button type="button" id="cancelQuestionBtn" class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" name="submit_question" class="px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                            Post Question
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script>
        // Modal handling
        const askQuestionBtn = document.getElementById('askQuestionBtn');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const cancelQuestionBtn = document.getElementById('cancelQuestionBtn');
        const askQuestionModal = document.getElementById('askQuestionModal');
        
        askQuestionBtn.addEventListener('click', () => {
            askQuestionModal.classList.remove('hidden');
        });
        
        closeModalBtn.addEventListener('click', () => {
            askQuestionModal.classList.add('hidden');
        });
        
        cancelQuestionBtn.addEventListener('click', () => {
            askQuestionModal.classList.add('hidden');
        });
        
        // Prevent form submission if we're just showing the modal
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (this.id !== 'questionForm') {
                    return true; // Allow normal form submission
                }
                e.preventDefault();
            });
        });
    </script>
<?php require '../templates/template_backtotop.php'  ?>
    <?php require '../templates/template_footer.php'  ?>
</body>
</html>