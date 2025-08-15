<?php
session_start();

// Check if user is logged in and has teacher/admin role
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role_id'] > 2) { // Only allow teachers/admins (role_id 1 or 2)
    header("Location: ../error.php ");
    exit();
}

// Database connection and common functions
require "../requires/common.php";
require "../requires/connect.php";
require "../requires/common_function.php";

$pagetitle = "Comments Dashboard";

// Handle Reply Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $commentId = intval($_POST['comment_id']);
    $reply = trim($_POST['reply']);
    
    if (empty($reply)) {
        $_SESSION['error'] = "Reply cannot be empty";
    } else {
        $stmt = $mysqli->prepare("INSERT INTO comment_reply (commentId, userId, reply) VALUES (?, ?, ?)");
        if (!$stmt) {
            $_SESSION['error'] = "Database error: " . $mysqli->error;
        } else {
            $stmt->bind_param("iis", $commentId, $_SESSION['id'], $reply);
            if ($stmt->execute()) {
                // Create notification for the comment author
                $notifyStmt = $mysqli->prepare("
                    INSERT INTO comment_notifications (comment_id, user_id)
                    SELECT ?, userId FROM comment WHERE id = ? AND userId != ?
                ");
                $notifyStmt->bind_param("iii", $commentId, $commentId, $_SESSION['id']);
                $notifyStmt->execute();
                
                $_SESSION['message'] = "Reply posted successfully!";
            } else {
                $_SESSION['error'] = "Failed to post reply: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    header('Location: '.$_SERVER['PHP_SELF'].(isset($_GET['comment_id']) ? '?comment_id='.$_GET['comment_id'] : ''));
    exit();
}

// Handle Delete Comment
if (isset($_GET['delete_comment'])) {
    $id = intval($_GET['delete_comment']);
    
    // First delete replies to prevent foreign key constraint violation
    $deleteReplies = $mysqli->prepare("DELETE FROM comment_reply WHERE commentId = ?");
    if ($deleteReplies) {
        $deleteReplies->bind_param("i", $id);
        $deleteReplies->execute();
        $deleteReplies->close();
    }
    
    // Then delete the comment
    $stmt = $mysqli->prepare("DELETE FROM comment WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Comment and its replies deleted successfully.";
        } else {
            $_SESSION['error'] = "Delete failed: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Database error: " . $mysqli->error;
    }
    header("Location: comment.php");
    exit();
}

// Handle Delete Reply
if (isset($_GET['delete_reply'])) {
    $id = intval($_GET['delete_reply']);
    $stmt = $mysqli->prepare("DELETE FROM comment_reply WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Reply deleted successfully.";
        } else {
            $_SESSION['error'] = "Delete failed: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Database error: " . $mysqli->error;
    }
    header("Location: comment.php".(isset($_GET['comment_id']) ? '?comment_id='.$_GET['comment_id'] : ''));
    exit();
}

// Check if we're viewing a specific comment
$specificComment = null;
if (isset($_GET['comment_id']) && is_numeric($_GET['comment_id'])) {
    $commentId = intval($_GET['comment_id']);
    
    // Get the specific comment with all details
    $commentQuery = $mysqli->prepare("
        SELECT c.*, 
               u.name as user_name, 
               u.profile_photo as user_photo,
               u.role_id as user_role,
               l.title as lesson_title,
               s.name as subject_name,
               co.name as course_name
        FROM comment c
        JOIN users u ON c.userId = u.id
        JOIN lessons l ON c.lessonId = l.id
        JOIN course_subject cs ON l.course_subject_id = cs.id
        JOIN subject s ON cs.subjectId = s.id
        JOIN courses co ON cs.courseId = co.id
        WHERE c.id = ?
        ORDER BY c.created_at DESC
    ");
    $commentQuery->bind_param("i", $commentId);
    $commentQuery->execute();
    $specificComment = $commentQuery->get_result()->fetch_assoc();
    
    if ($specificComment) {
        // Get replies for this comment
        $repliesQuery = $mysqli->prepare("
            SELECT r.*, u.name, u.profile_photo, u.role_id
            FROM comment_reply r
            JOIN users u ON r.userId = u.id
            WHERE r.commentId = ?
            ORDER BY r.created_at ASC
        ");
        $repliesQuery->bind_param("i", $commentId);
        $repliesQuery->execute();
        $specificComment['replies'] = $repliesQuery->get_result()->fetch_all(MYSQLI_ASSOC);
        

        $markRead = $mysqli->prepare("
            UPDATE comment_notifications 
            SET is_read = TRUE 
            WHERE comment_id = ? AND user_id = ?
        ");
        $markRead->bind_param("ii", $commentId, $_SESSION['id']);
        $markRead->execute();
        $markRead->close();
    }
}

// Get all comments with user and lesson details (only if not viewing a specific comment)
$allComments = [];
if (!$specificComment) {
    $commentsQuery = $mysqli->prepare("
        SELECT c.*, 
               u.name as user_name, 
               u.profile_photo as user_photo,
               u.role_id as user_role,
               l.title as lesson_title,
               s.name as subject_name,
               co.name as course_name
        FROM comment c
        JOIN users u ON c.userId = u.id
        JOIN lessons l ON c.lessonId = l.id
        JOIN course_subject cs ON l.course_subject_id = cs.id
        JOIN subject s ON cs.subjectId = s.id
        JOIN courses co ON cs.courseId = co.id
        ORDER BY c.created_at DESC
    ");

    if ($commentsQuery && $commentsQuery->execute()) {
        $allComments = $commentsQuery->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get replies for each comment
        foreach ($allComments as &$comment) {
            $repliesQuery = $mysqli->prepare("
                SELECT r.*, u.name, u.profile_photo, u.role_id
                FROM comment_reply r
                JOIN users u ON r.userId = u.id
                WHERE r.commentId = ?
                ORDER BY r.created_at ASC
            ");
            if ($repliesQuery) {
                $repliesQuery->bind_param("i", $comment['id']);
                if ($repliesQuery->execute()) {
                    $comment['replies'] = $repliesQuery->get_result()->fetch_all(MYSQLI_ASSOC);
                }
                $repliesQuery->close();
            }
        }
        unset($comment);
    } else {
        $_SESSION['error'] = "Failed to load comments: " . $mysqli->error;
    }
}

require "./templates/admin_header.php";
require "./templates/admin_sidebar.php";
?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">
                <?= $specificComment ? 'Comment Details' : 'Comments Dashboard' ?>
            </h1>
            <p class="text-gray-600 mt-2">
                <?= $specificComment ? 'View and respond to this comment' : 'Manage all student and teacher comments' ?>
            </p>
        </div>
        <?php if ($specificComment): ?>
            <a href="comment.php" class="flex items-center text-indigo-600 hover:text-indigo-800 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Back to all comments
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

    <?php if ($specificComment): ?>
        <!-- Single Comment View -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="p-6">
                <!-- Comment Header -->
                <div class="flex items-start justify-between mb-6">
                    <div class="flex items-start space-x-4">
                        <div class="relative">
                            <img src="<?= htmlspecialchars($specificComment['user_photo'] ?? 'img/default-profile.png', ENT_QUOTES, 'UTF-8') ?>" 
                                 class="w-14 h-14 rounded-full object-cover border-2 border-white shadow">
                            <?php if ($specificComment['user_role'] <= 2): ?>
                                <span class="absolute -bottom-1 -right-1 bg-blue-500 text-white text-xs font-bold px-2 py-0.5 rounded-full border-2 border-white">T</span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="flex items-center space-x-3">
                                <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($specificComment['user_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            </div>
                            <div class="text-sm text-gray-500 mt-1">
                                <i class="far fa-clock mr-1"></i> <?= date('M j, Y g:i a', strtotime($specificComment['created_at'])) ?>
                            </div>
                            <div class="text-sm text-gray-500 mt-1 flex items-center">
                                <i class="far fa-folder-open mr-1"></i>
                                <?= htmlspecialchars($specificComment['course_name'], ENT_QUOTES, 'UTF-8') ?> 
                                <i class="fas fa-chevron-right mx-1 text-xs"></i>
                                <?= htmlspecialchars($specificComment['subject_name'], ENT_QUOTES, 'UTF-8') ?> 
                                <i class="fas fa-chevron-right mx-1 text-xs"></i>
                                <?= htmlspecialchars($specificComment['lesson_title'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                    </div>
                    <?php if ($_SESSION['role_id'] <= 2): ?>
                        <button onclick="confirmDeleteComment(<?= $specificComment['id'] ?>)"
                                class="text-gray-400 hover:text-red-500 transition-colors">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    <?php endif; ?>
                </div>
                
                <!-- Comment Content -->
                <div class="pl-18">
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                        <p class="text-gray-800"><?= htmlspecialchars($specificComment['comment'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
                
                <!-- Replies Section -->
                <?php if (!empty($specificComment['replies'])): ?>
                    <div class="mt-8 pl-18">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 flex items-center">
                            <span class="bg-gray-200 h-px flex-1 mr-3"></span>
                            <?= count($specificComment['replies']) ?> <?= count($specificComment['replies']) === 1 ? 'Reply' : 'Replies' ?>
                            <span class="bg-gray-200 h-px flex-1 ml-3"></span>
                        </h3>
                        <div class="space-y-4">
                            <?php foreach ($specificComment['replies'] as $reply): ?>
                                <div class="bg-white p-4 rounded-lg border border-gray-100 shadow-xs">
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-start space-x-3">
                                            <div class="relative">
                                                <img src="<?= htmlspecialchars($reply['profile_photo'] ?? 'img/default-profile.png', ENT_QUOTES, 'UTF-8') ?>" 
                                                     class="w-10 h-10 rounded-full object-cover border-2 border-white shadow">
                                                <?php if ($reply['role_id'] <= 2): ?>
                                                    <span class="absolute -bottom-1 -right-1 bg-blue-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full border-2 border-white">T</span>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div class="flex items-center space-x-2">
                                                    <span class="font-medium text-gray-900"><?= htmlspecialchars($reply['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                                </div>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    <i class="far fa-clock mr-1"></i> <?= date('M j, Y g:i a', strtotime($reply['created_at'])) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if ($_SESSION['role_id'] <= 2): ?>
                                            <button onclick="confirmDeleteReply(<?= $reply['id'] ?>)"
                                                    class="text-gray-400 hover:text-red-500 transition-colors text-sm">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-3 pl-13">
                                        <p class="text-gray-700"><?= htmlspecialchars($reply['reply'], ENT_QUOTES, 'UTF-8') ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Reply Form -->
                <div class="mt-8 pl-18">
                    <form method="POST">
                        <input type="hidden" name="comment_id" value="<?= $specificComment['id'] ?>">
                        <div class="flex items-start space-x-4">
                            <img src="<?= htmlspecialchars($_SESSION['profile_photo'] ?? 'img/default-profile.png', ENT_QUOTES, 'UTF-8') ?>" 
                                 class="w-10 h-10 rounded-full object-cover border-2 border-white shadow">
                            <div class="flex-1">
                                <label for="reply" class="block text-sm font-medium text-gray-700 mb-1">Your Reply</label>
                                <textarea name="reply" id="reply"
                                      class="w-full border border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" 
                                      rows="4" 
                                      placeholder="Type your reply here..."></textarea>
                                <div class="mt-3 flex justify-end">
                                    <button type="submit" 
                                            class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition-colors shadow-sm flex items-center">
                                        <i class="fas fa-paper-plane mr-2"></i> Post Reply
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- All Comments View -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <?php if (empty($allComments)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="far fa-comment-dots text-4xl mb-3 text-gray-300"></i>
                    <p class="text-lg">No comments found</p>
                    <p class="text-sm mt-1">When students or teachers post comments, they'll appear here</p>
                </div>
            <?php else: ?>
                <?php
                // Group comments by user
                $users = [];
                foreach ($allComments as $comment) {
                    $userId = $comment['userId'];
                    if (!isset($users[$userId])) {
                        $users[$userId] = [
                            'user_name' => $comment['user_name'],
                            'user_photo' => $comment['user_photo'],
                            'user_role' => $comment['user_role'],
                            'comments' => [],
                            'total_replies' => 0,
                            'last_activity' => $comment['created_at']
                        ];
                    }
                    $users[$userId]['comments'][] = $comment;
                    $users[$userId]['total_replies'] += count($comment['replies'] ?? []);
                    
                    // Update last activity if this comment is newer
                    if (strtotime($comment['created_at']) > strtotime($users[$userId]['last_activity'])) {
                        $users[$userId]['last_activity'] = $comment['created_at'];
                    }
                }
                ?>
        
                <div class="divide-y divide-gray-100">
                    <?php foreach ($users as $userId => $user): ?>
                        <div class="p-6 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start justify-between cursor-pointer " onclick="toggleUserComments(this)">
                                <div class="flex items-start w-full relative">
                                    <?php 
                                    // Get unread notifications count for the current user in view
                                    $notiCount = 0;
                                    $notiStmt = $mysqli->prepare("
                                        SELECT COUNT(*) 
                                        FROM comment_notifications 
                                        WHERE user_id = ? AND is_read = 0
                                        AND comment_id IN (
                                            SELECT id FROM comment WHERE userId = ?
                                        )
                                    ");
                                    if ($notiStmt) {
                                        $notiStmt->bind_param("ii", $_SESSION['id'], $userId);
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
                                        <img src="<?= htmlspecialchars($user['user_photo'] ?? 'img/default-profile.png', ENT_QUOTES, 'UTF-8') ?>" 
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
                                                <i class="far fa-comment mr-1"></i> <?= count($user['comments']) ?> comments
                                            </span>
                                            <span>
                                                <i class="far fa-comments mr-1"></i> <?= $user['total_replies'] ?> replies
                                            </span>
                                        </div>
                                        
                                        <!-- User's comments (collapsible) -->
                                        <div class="mt-4 pl-2 border-l-2 border-gray-200 user-comments" style="display: none;">
                                            <?php foreach ($user['comments'] as $comment): ?>
                                                <div class="mb-5 pb-5 border-b border-gray-100 last:border-0 last:mb-0 last:pb-0">
                                                    <div class="text-xs text-gray-500 mb-2 flex items-center">
                                                        <i class="far fa-clock mr-1"></i> <?= date('M j, Y g:i a', strtotime($comment['created_at'])) ?>
                                                        <span class="mx-2">•</span>
                                                        <i class="far fa-folder-open mr-1"></i>
                                                        <?= htmlspecialchars($comment['course_name'], ENT_QUOTES, 'UTF-8') ?> 
                                                        <i class="fas fa-chevron-right mx-1 text-xs"></i>
                                                        <?= htmlspecialchars($comment['subject_name'], ENT_QUOTES, 'UTF-8') ?> 
                                                        <i class="fas fa-chevron-right mx-1 text-xs"></i>
                                                        <?= htmlspecialchars($comment['lesson_title'], ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                    <p class="text-gray-700 mb-3"><?= htmlspecialchars($comment['comment'], ENT_QUOTES, 'UTF-8') ?></p>
                                            
                                                    <!-- View link -->
                                                    <div class="flex justify-between items-center">
                                                        <a href="?comment_id=<?= $comment['id'] ?>" 
                                                           class="text-indigo-600 hover:text-indigo-800 text-sm flex items-center transition-colors">
                                                            <i class="fas fa-eye mr-1"></i> View Conversation
                                                        </a>
                                                        <?php if ($_SESSION['role_id'] <= 2): ?>
                                                            <button onclick="event.stopPropagation(); confirmDeleteComment(<?= $comment['id'] ?>)"
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
        function toggleUserComments(element) {
            const container = element.closest('.flex.items-start.justify-between');
            const commentsSection = container.querySelector('.user-comments');
            const icon = container.querySelector('i');
            
            if (commentsSection.style.display === 'none') {
                commentsSection.style.display = 'block';
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            } else {
                commentsSection.style.display = 'none';
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
        }
        </script>
    <?php endif; ?>
</div>

<!-- Delete Comment Modal -->
<div id="deleteCommentModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4 transition-opacity">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md transform transition-all">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Confirm Deletion</h2>
                <button onclick="closeModal('deleteCommentModal')" class="text-gray-400 hover:text-gray-500 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mb-6">
                <div class="flex items-center justify-center text-red-500 mb-4">
                    <i class="fas fa-exclamation-triangle text-4xl"></i>
                </div>
                <p class="text-gray-700 text-center">Are you sure you want to delete this comment? All replies will also be permanently deleted.</p>
            </div>
            <div class="flex justify-end gap-3">
                <button onclick="closeModal('deleteCommentModal')"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <a id="deleteCommentLink" href="#"
                   class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors flex items-center">
                    <i class="fas fa-trash-alt mr-2"></i> Delete
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Delete Reply Modal -->
<div id="deleteReplyModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden z-50 p-4 transition-opacity">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md transform transition-all">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Confirm Deletion</h2>
                <button onclick="closeModal('deleteReplyModal')" class="text-gray-400 hover:text-gray-500 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mb-6">
                <div class="flex items-center justify-center text-red-500 mb-4">
                    <i class="fas fa-exclamation-triangle text-4xl"></i>
                </div>
                <p class="text-gray-700 text-center">Are you sure you want to delete this reply? This action cannot be undone.</p>
            </div>
            <div class="flex justify-end gap-3">
                <button onclick="closeModal('deleteReplyModal')"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <a id="deleteReplyLink" href="#"
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

function confirmDeleteComment(id) {
    document.getElementById('deleteCommentLink').href = `comment.php?delete_comment=${id}`;
    openModal('deleteCommentModal');
}

function confirmDeleteReply(id) {
    document.getElementById('deleteReplyLink').href = `comment.php?delete_reply=${id}`;
    openModal('deleteReplyModal');
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('fixed')) {
        closeModal(event.target.id);
    }
});

// Prevent propagation when clicking inside modal content
document.querySelectorAll('#deleteCommentModal > div, #deleteReplyModal > div').forEach(modal => {
    modal.addEventListener('click', function(event) {
        event.stopPropagation();
    });
});

</script>

<?php require "./templates/admin_footer.php"; ?>