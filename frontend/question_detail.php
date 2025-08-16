<?php
session_start();
require '../requires/connect.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['id'];
$user_role = $_SESSION['role_id'];

// Get question ID from URL
$question_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($question_id === 0) {
    header("Location: discussion.php");
    exit();
}



// Handle like action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_question'])) {
    // Check if user already liked this question
    $checkLike = $mysqli->query("SELECT id FROM question_likes 
                               WHERE questionId = $question_id AND userId = $user_id");
    
    if ($checkLike->num_rows > 0) {
        // Unlike the question
        $mysqli->query("DELETE FROM question_likes WHERE questionId = $question_id AND userId = $user_id");
        $mysqli->query("UPDATE question SET likeCount = likeCount - 1 WHERE id = $question_id");
        $isLiked = false;
    } else {
        // Like the question
        $mysqli->query("INSERT INTO question_likes (questionId, userId) VALUES ($question_id, $user_id)");
        $mysqli->query("UPDATE question SET likeCount = likeCount + 1 WHERE id = $question_id");
        $isLiked = true;
    }
    
    // Get updated like count
    $result = $mysqli->query("SELECT likeCount FROM question WHERE id = $question_id");
    $likeCount = $result->fetch_assoc()['likeCount'];
    
    echo json_encode([
        'success' => true,
        'newCount' => $likeCount,
        'isLiked' => $isLiked
    ]);
    exit();
}

// Get the question details
$question = $mysqli->query("
    SELECT q.*, u.name as asker, u.profile_photo as asker_photo, m.name as module_name,
           (SELECT COUNT(*) FROM answer a WHERE a.questionId = q.id) as answer_count
    FROM question q
    JOIN users u ON q.userId = u.id
    JOIN module m ON q.moduleId = m.id
    WHERE q.id = $question_id AND q.is_approve = TRUE
")->fetch_assoc();

if (!$question) {
    header("Location: discussion.php");
    exit();
}

// Check if current user has liked this question
$hasLiked = false;
$checkLike = $mysqli->query("SELECT 1 FROM question_likes 
                           WHERE questionId = $question_id AND userId = $user_id");
$hasLiked = $checkLike->num_rows > 0;

// Get all answers for this question
$answers = $mysqli->query("
    SELECT a.*, u.name as responder, u.profile_photo as responder_photo,
           u.role_id as responder_role
    FROM answer a
    JOIN users u ON a.userId = u.id
    WHERE a.questionId = $question_id
    ORDER BY a.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($question['title']) ?> | TechPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .vote-active {
            color: #3b82f6;
        }
        .answer-card {
            transition: all 0.2s ease;
        }
        .answer-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .teacher-badge {
            position: absolute;
            bottom: -2px;
            right: -2px;
            background-color: #3b82f6;
            color: white;
            font-size: 10px;
            font-weight: bold;
            padding: 2px 4px;
            border-radius: 9999px;
            border: 2px solid white;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php require '../templates/template_nav.php'; ?>
    
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
                <?= $_SESSION['message'] ?>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <button onclick="this.parentElement.parentElement.remove()">&times;</button>
                </span>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
                <?= $_SESSION['error'] ?>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <button onclick="this.parentElement.parentElement.remove()">&times;</button>
                </span>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <!-- Back button -->
        <div class="flex justify-between items-center mb-4">
            <a href="discussion.php" class="text-blue-600 hover:text-blue-800">
                <i class="fas fa-arrow-left"></i> Back to Discussion
            </a>
        </div>

        <!-- Question Section -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6 w-full max-w-4xl">
            <div class="flex items-start gap-4">

                
                
                <!-- Question Content -->
                <div class="flex-1">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                        <h1 class="text-2xl font-bold text-gray-900">
                            <?= htmlspecialchars($question['title']) ?>
                        </h1>
                        <div class="flex gap-2">
                            <span class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">
                                <?= htmlspecialchars($question['module_name']) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="prose max-w-none text-gray-700 mb-6">
                        <p class="break-words w-full max-w-3xl"><?= nl2br(htmlspecialchars($question['question'])) ?></p>
                    </div>
                    
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <img src="../img/<?= htmlspecialchars($question['asker_photo']) ?>" 
                                     class="w-8 h-8 rounded-full object-cover">
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($question['asker']) ?></p>
                                <p class="text-xs text-gray-500">Asked <?= date('M d, Y', strtotime($question['created_at'])) ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-4">
                            <div class="flex flex-col items-center">
                                <button onclick="toggleLike(<?= $question_id ?>)" 
                                        class="vote-btn <?= $hasLiked ? 'vote-active' : '' ?> text-gray-500 hover:text-blue-500 transition-colors">
                                    <i class="fas fa-thumbs-up <?= $hasLiked ? 'text-blue-500' : 'text-gray-400' ?>"></i>
                                     <span class="text-gray-900 my-1" id="votes-count">
                                        <?= $question['likeCount'] ?>
                                    </span>
                                </button>
                        
                            </div>
                            <span class="text-sm text-gray-600">
                                <i class="fas fa-comment mr-1 text-gray-400"></i>
                                <?= $question['answer_count'] ?> <?= $question['answer_count'] == 1 ? 'answer' : 'answers' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Answers Section -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                <?= $question['answer_count'] ?> <?= $question['answer_count'] == 1 ? 'Answer' : 'Answers' ?>
            </h2>
            
             <?php if ($answers->num_rows > 0): ?>
                <div class="space-y-4">
                    <?php while ($answer = $answers->fetch_assoc()): ?>
                        <div class="bg-white rounded-xl shadow-sm p-6 answer-card">
                            <div class="flex items-start gap-2">
                                <!-- Answerer Info -->
                                <div class="flex flex-col items-center" style="width: 40px; height: 40px;" >
                                    <img src="../img/<?= htmlspecialchars($answer['responder_photo']) ?>" 
                                             class=" rounded-full object-cover" style="width: 100%; height: 100%;" >
                                </div>
                                
                                <!-- Answer Content -->
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-2">
                                        <div>
                                            <span class="font-medium text-gray-900"><?= htmlspecialchars($answer['responder']) ?></span>
                                            <?php if ($answer['responder_role'] <= 2): ?>
                                                <span class="ml-2 text-xs px-2 py-0.5 bg-blue-100 text-blue-800 rounded-full">Teacher</span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-xs text-gray-500">
                                            <?= date('M d, Y \a\t g:i a', strtotime($answer['created_at'])) ?>
                                        </span>
                                    </div>
                                    
                                    <div class="prose max-w-none text-gray-700">
                                        <p class="break-words w-full max-w-3xl"><?= nl2br(htmlspecialchars($answer['answer'])) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-sm p-6 text-center text-gray-500">
                    <i class="fas fa-comment-slash text-4xl mb-3 text-gray-300"></i>
                    <p>No answers yet. Be the first to answer!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    

    
    <script>
    // Toggle like function with AJAX
    function toggleLike(questionId) {
        if (!<?= isset($_SESSION['id']) ? 'true' : 'false' ?>) {
            window.location.href = 'login.php';
            return;
        }

        fetch('question_detail.php?id=<?= $question_id ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `like_question=${questionId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const likeBtn = document.querySelector('.vote-btn');
                const thumbsUpIcon = likeBtn.querySelector('i.fas');
                const likeCountElements = document.querySelectorAll('#votes-count, #like-count');

                // Toggle active state
                likeBtn.classList.toggle('vote-active');
                thumbsUpIcon.classList.toggle('text-blue-500');
                thumbsUpIcon.classList.toggle('text-gray-400');

                // Update all like count elements
                likeCountElements.forEach(el => {
                    el.textContent = data.newCount;
                });
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
</body>
</html>