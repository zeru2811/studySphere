<?php
session_start();
$type = "Subject";
require '../requires/connect.php';
require '../requires/common_function.php';
$basePath = '/studysphere/frontend';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header("Location: courses.php");
  exit();
}

$courseId = $_GET['id'];

// Fetch course details
$courseQuery = $mysqli->prepare("SELECT * FROM courses WHERE id = ?");
$courseQuery->bind_param("i", $courseId);
$courseQuery->execute();
$course = $courseQuery->get_result()->fetch_assoc();

if (!$course) {
  header("Location: courses.php");
  exit();
}

// Get unique modules (subjects)
$modulesQuery = $mysqli->prepare("
    SELECT DISTINCT s.id, s.name, cs.display_order
    FROM course_subject cs
    JOIN subject s ON cs.subjectId = s.id
    WHERE cs.courseId = ?
    ORDER BY cs.display_order ASC
");
$modulesQuery->bind_param("i", $courseId);
$modulesQuery->execute();
$modules = $modulesQuery->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch lessons for each module
foreach ($modules as &$module) {
  $lessonsQuery = $mysqli->prepare("
        SELECT l.*, 
               EXISTS(SELECT 1 FROM lesson_completions 
                      WHERE lesson_id = l.id AND user_id = ?) as is_complete
        FROM lessons l
        JOIN course_subject cs ON l.course_subject_id = cs.id
        WHERE cs.subjectId = ? AND cs.courseId = ?
        ORDER BY cs.display_order, l.id
    ");
  $lessonsQuery->bind_param("iii", $_SESSION['id'], $module['id'], $courseId);
  $lessonsQuery->execute();
  $module['lessons'] = $lessonsQuery->get_result()->fetch_all(MYSQLI_ASSOC);
}
unset($module);

// Default lesson
$currentLesson = null;
if (!empty($modules) && !empty($modules[0]['lessons'])) {
  $currentLesson = $modules[0]['lessons'][0];
}

// Check for specific lesson
if (isset($_GET['lesson_id']) && is_numeric($_GET['lesson_id'])) {
  $lessonId = $_GET['lesson_id'];
  $lessonQuery = $mysqli->prepare("SELECT * FROM lessons WHERE id = ?");
  $lessonQuery->bind_param("i", $lessonId);
  $lessonQuery->execute();
  $currentLesson = $lessonQuery->get_result()->fetch_assoc();
}

if (!$currentLesson) {
  $noLessons = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $note = trim($_POST['note']);
  $lessonId = (int)$_POST['lesson_id'];

  if (!empty($note) && $lessonId > 0) {
    $userId = $_SESSION['id'] ?? null;
    if ($userId) {
      $stmt = $mysqli->prepare("INSERT INTO comment (userId, lessonId, comment) VALUES (?, ?, ?)");
      $stmt->bind_param("iis", $userId, $lessonId, $note);
      if ($stmt->execute()) {
        $commentId = $stmt->insert_id;
        $notifyStmt = $mysqli->prepare("
                    INSERT INTO comment_notifications (comment_id, user_id)
                    SELECT ?, id FROM users WHERE role_id <= 2 AND id != ?
                ");
        $notifyStmt->bind_param("ii", $commentId, $userId);
        $notifyStmt->execute();

        $_SESSION['message'] = "Note saved successfully!";
        header("Location: subject.php?id=" . $courseId . "&lesson_id=" . $currentLesson['id']);
        exit();
      } else {
        $_SESSION['error'] = "Failed to save note.";
      }
    } else {
      $_SESSION['error'] = "You must be logged in to save notes.";
    }
  } else {
    $_SESSION['error'] = "Note cannot be empty.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($course['name']) ?> | Sphere</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .video-wrapper {
      position: relative;
      padding-bottom: 56.25%;
      height: 0;
      overflow: hidden;
    }

    .video-wrapper iframe {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
    }

    .progress-ring__circle {
      transition: stroke-dashoffset 0.35s;
      transform: rotate(-90deg);
      transform-origin: 50% 50%;
    }

    .active-lesson {
      background-color: #f5f3ff;
    }
  </style>
</head>
<?php require '../templates/template_nav.php'; ?>

<body class="bg-gray-50">
  <div class="container mx-auto px-4 py-6">
    <div class="flex flex-col lg:flex-row gap-6">

      <!-- Main Content -->
      <div class="lg:w-3/4">
        <?php if (isset($_SESSION['message'])): ?>
          <div id="message" class="bg-green-100 text-green-800 p-4 rounded-lg mb-4">
            <?= htmlspecialchars($_SESSION['message']) ?>
          </div>
          <?php unset($_SESSION['message']); ?>
        <?php elseif (isset($_SESSION['error'])): ?>
          <div id="error" class="bg-red-100 text-red-800 p-4 rounded-lg mb-4">
            <?= htmlspecialchars($_SESSION['error']) ?>
          </div>
          <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Course Header -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
          <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
              <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($course['name']) ?></h1>
              <p class="text-gray-600"><?= htmlspecialchars($course['title']) ?></p>
            </div>

            <?php if ($isEnrolled): ?>
              <div class="flex items-center space-x-4">
                <div class="relative w-12 h-12">
                  <svg class="w-full h-full" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="16" fill="none" class="stroke-gray-200" stroke-width="3"></circle>
                    <circle cx="18" cy="18" r="16" fill="none" class="stroke-indigo-600 progress-ring__circle" stroke-width="3" stroke-dasharray="100" stroke-dashoffset="<?= 100 - $progress ?>"></circle>
                  </svg>
                  <span class="absolute inset-0 flex items-center justify-center text-xs font-bold"><?= $progress ?>%</span>
                </div>
                <a href="?id=<?= $courseId ?>&lesson_id=<?= $currentLesson['id'] ?? '' ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                  Continue
                </a>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Lesson Content -->
        <?php if (isset($noLessons)): ?>
          <div class="bg-white rounded-xl shadow-sm p-6 text-center">
            <p class="text-gray-600">No lessons available for this course yet.</p>
          </div>
        <?php elseif ($currentLesson): ?>
          <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="video-wrapper bg-black">
              <?php if ($currentLesson['lessonUrl']): ?>
                <iframe src="<?= htmlspecialchars($currentLesson['lessonUrl']) ?>" title="<?= htmlspecialchars($currentLesson['title']) ?>" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
              <?php else: ?>
                <div class="absolute inset-0 flex items-center justify-center text-white">Video content coming soon</div>
              <?php endif; ?>
            </div>

            <div class="p-6">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h2 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($currentLesson['title']) ?></h2>
                  <?php
                  $lessonCount = 0;
                  foreach ($modules as $mod) {
                    $lessonCount += count($mod['lessons']);
                  }
                  $lessonNumber = 1;
                  foreach ($modules as $mod) {
                    foreach ($mod['lessons'] as $l) {
                      if ($l['id'] == $currentLesson['id']) break 2;
                      $lessonNumber++;
                    }
                  }
                  ?>
                  <p class="text-gray-500 text-sm">Lesson <?= $lessonNumber ?> of <?= $lessonCount ?> • <?= $currentLesson['duration'] ?? 'N/A' ?></p>
                </div>
              </div>
              <div class="prose max-w-none text-gray-600 mb-6">
                <p><?= htmlspecialchars($currentLesson['description']) ?></p>
              </div>
            </div>
          </div>

          <!-- Notes Section -->
          <div class="bg-white rounded-xl shadow-sm p-6 mb-10">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Notes & Discussions</h3>

            <!-- Note Form -->
            <form id="myForm" class="mb-6" method="POST">
              <input type="hidden" name="lesson_id" value="<?= $currentLesson['id'] ?>">
              <textarea name="note" class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-500" rows="4" placeholder="Write a note or question..."></textarea>
              <div class="mt-2 flex justify-end">
                <button type="submit" id="submitBtn" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">Post Note</button>
              </div>
            </form>

            <script>
              document.getElementById("myForm").addEventListener("submit", function() {
                document.getElementById("submitBtn").disabled = true;
              });
            </script>

            <?php if (isset($_SESSION['id'])): ?>
              <?php
              $userId = $_SESSION['id'];
              $notesQuery = $mysqli->prepare("
                    SELECT c.*, u.name, u.profile_photo as thumbnail, u.role_id
                    FROM comment c
                    JOIN users u ON c.userId = u.id
                    WHERE c.lessonId = ?
                    AND c.userId = $userId
                    ORDER BY c.created_at DESC
                ");
              $notesQuery->bind_param("i", $currentLesson['id']);
              $notesQuery->execute();
              $notes = $notesQuery->get_result()->fetch_all(MYSQLI_ASSOC);

              foreach ($notes as $note):
                $repliesQuery = $mysqli->prepare("
                        SELECT r.*, u.name, u.profile_photo as thumbnail, u.role_id
                        FROM comment_reply r
                        JOIN users u ON r.userId = u.id
                        WHERE r.commentId = ?
                        ORDER BY r.created_at ASC
                    ");
                $repliesQuery->bind_param("i", $note['id']);
                $repliesQuery->execute();
                $replies = $repliesQuery->get_result()->fetch_all(MYSQLI_ASSOC);
              ?>
                <div class="bg-gray-50 rounded-lg p-4 mb-4 border border-gray-200">
                  <!-- Main Note -->
                  <div class="flex items-start">
                    <img src="<?= htmlspecialchars($note['thumbnail'] ?? 'img/default-profile.png') ?>"
                      alt="Profile"
                      class="w-10 h-10 rounded-full mr-3">
                    <div class="flex-1">
                      <div class="flex items-center justify-between">
                        <div>
                          <span class="font-medium"><?= htmlspecialchars($note['name']) ?></span>
                          <?php if ($note['role_id'] <= 2): ?>
                            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded ml-2">Teacher</span>
                          <?php endif; ?>
                        </div>
                        <?php if ($_SESSION['role_id'] <= 2): ?>
                          <button class="text-xs text-gray-500 hover:text-gray-700 delete-note"
                            data-id="<?= $note['id'] ?>">Delete</button>
                        <?php endif; ?>
                      </div>
                      <div class="text-sm text-gray-700 mt-1"><?= htmlspecialchars($note['comment']) ?></div>
                      <div class="text-xs text-gray-500 mt-1"><?= date('M j, Y g:i a', strtotime($note['created_at'])) ?></div>
                    </div>
                  </div>

                  <!-- Replies Section -->
                  <?php if (!empty($replies)): ?>
                    <div class="ml-12 mt-3 space-y-3 border-l-2 border-gray-300 pl-4">
                      <?php foreach ($replies as $reply): ?>
                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                          <div class="flex items-start">
                            <img src="<?= htmlspecialchars($reply['thumbnail'] ?? 'img/default-profile.png') ?>"
                              alt="Profile"
                              class="w-8 h-8 rounded-full mr-2">
                            <div class="flex-1">
                              <div class="flex items-center justify-between">
                                <div>
                                  <span class="text-sm font-medium">
                                    <?= htmlspecialchars($reply['name']) ?>
                                    <?php if ($reply['role_id'] <= 2): ?>
                                      <span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded ml-1">Teacher</span>
                                    <?php endif; ?>
                                  </span>
                                </div>
                              </div>
                              <div class="text-xs text-gray-700 mt-1"><?= htmlspecialchars($reply['reply']) ?></div>
                              <div class="text-xs text-gray-500 mt-1"><?= date('M j, Y g:i a', strtotime($reply['created_at'])) ?></div>
                            </div>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>

                  <!-- Reply Form (for teachers/admins) -->
                  <?php if ($_SESSION['role_id'] <= 2): ?>
                    <form class="ml-12 mt-3 reply-form" data-comment-id="<?= $note['id'] ?>">
                      <div class="flex items-start">
                        <img src="<?= htmlspecialchars($_SESSION['profile_photo'] ?? 'img/default-profile.png') ?>"
                          alt="Your Profile"
                          class="w-8 h-8 rounded-full mr-2">
                        <div class="flex-1">
                          <textarea name="reply"
                            class="w-full text-sm border border-gray-300 rounded p-2"
                            rows="2"
                            placeholder="Write a reply as teacher..."></textarea>
                          <button type="submit"
                            class="mt-1 bg-indigo-600 text-white text-xs px-3 py-1 rounded hover:bg-indigo-700">
                            Post Reply
                          </button>
                        </div>
                      </div>
                    </form>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

        <?php endif; ?>
      </div>

      <!-- Sidebar -->
      <div class="lg:w-1/4">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
          <div class="p-4 border-b border-gray-200">
            <h3 class="font-bold text-gray-900">Course Content</h3>
            <p class="text-sm text-gray-500">
              <?= count($modules) ?> modules •
              <?php
              $totalLessons = 0;
              foreach ($modules as $module) {
                $totalLessons += count($module['lessons']);
              }
              echo $totalLessons;
              ?> lessons
            </p>
          </div>

          <div class="divide-y divide-gray-200/70 max-h-[calc(100vh-200px)] overflow-y-auto" id="module-accordion">
            <?php
              $allModulesComplete = true;
              foreach ($modules as $moduleIndex => $module):
                $isOpen = false;
                foreach ($module['lessons'] as $lesson) {
                  if (($currentLesson['id'] ?? null) == $lesson['id']) {
                    $isOpen = true;
                    break;
                  }
                }

                // Check if module is complete (all lessons completed)
                $moduleComplete = true;
                foreach ($module['lessons'] as $lesson) {
                  if (!$lesson['is_complete']) {
                    $moduleComplete = false;
                    $allModulesComplete = false;
                    break;
                  }
                }
            ?>
              <div data-index="<?= $moduleIndex ?>" class="module-section group">
                <button type="button"
                  class="module-toggle w-full px-5 py-4 text-left font-medium hover:bg-gray-50/50 transition-colors duration-150 flex justify-between items-center rounded-lg"
                  data-target="content-<?= $moduleIndex ?>">
                  <div class="flex items-center space-x-3">
                    <div class="w-6 h-6 flex items-center justify-center rounded-full bg-gray-100 group-hover:bg-gray-200 transition-colors">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500 transform transition-transform duration-200 <?= $isOpen ? 'rotate-180' : '' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                      </svg>
                    </div>
                    <span class="text-gray-800 <?= $moduleComplete ? 'font-semibold' : '' ?>">
                      <?= htmlspecialchars($module['name']) ?>
                    </span>
                    <?php if ($moduleComplete): ?>
                      <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-700">Completed</span>
                    <?php endif; ?>
                  </div>
                  <span class="text-sm text-gray-500">
                    <?= count($module['lessons']) ?> <?= count($module['lessons']) === 1 ? 'lesson' : 'lessons' ?>
                  </span>
                </button>
                <div id="content-<?= $moduleIndex ?>" class="pl-11 pr-5 transition-all duration-200 ease-in-out overflow-hidden <?= $isOpen ? 'pb-2' : 'hidden' ?>">
                  <div class="space-y-1">
                    <?php foreach ($module['lessons'] as $lesson): ?>
                      <div class="flex items-center group relative">
                        <a href="?id=<?= $courseId ?>&lesson_id=<?= $lesson['id'] ?>" class="lesson-item w-full flex items-center px-3 py-2.5 rounded-md cursor-pointer transition-colors duration-150 <?= ($currentLesson['id'] ?? null) == $lesson['id'] ? 'bg-indigo-50 text-indigo-700 font-medium' : 'hover:bg-gray-50' ?>">
                          <div class="flex-shrink-0 w-5 h-5 flex items-center justify-center mr-3">
                            <?php if ($lesson['is_complete']): ?>
                              <div class="w-4 h-4 rounded-full bg-green-500 flex items-center justify-center">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                              </div>
                            <?php else: ?>
                              <div class="w-4 h-4 rounded-full border-2 border-gray-300 group-hover:border-gray-400 transition-colors"></div>
                            <?php endif; ?>
                          </div>
                          <span class="truncate"><?= htmlspecialchars($lesson['title']) ?></span>
                          <span class="ml-auto text-xs px-2 py-1 rounded <?= ($currentLesson['id'] ?? null) == $lesson['id'] ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 bg-gray-100' ?>">
                            <?= isset($lesson['duration']) ? preg_replace('/:00$/', '', $lesson['duration']) : 'N/A' ?>
                          </span>
                        </a>
                        <?php if (!$lesson['is_complete']): ?>
                          <button class="mark-complete-btn absolute left-3 opacity-0 group-hover:opacity-100 p-1 text-gray-400 hover:text-green-500 transition-all duration-150"
                            data-lesson-id="<?= $lesson['id'] ?>"
                            title="Mark as complete">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                          </button>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>


                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <?php if ($allModulesComplete): ?>
          <div class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
              <div class="text-center">
                  <h3 class="text-lg font-medium text-gray-900 mb-2">Congratulations! 🎉</h3>
                  <p class="text-gray-600 mb-4">You've completed all subject in this course.</p>
                  <a href="generate_certificate.php?course_id=<?= $courseId ?>" 
                     class="inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-150">
                      <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                      Download Course Certificate
                  </a>
              </div>
          </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>
  <script src="../assets/js/jquery-3.7.1.min.js"></script>
  <script>
    document.querySelectorAll('.module-toggle').forEach(button => {
      button.addEventListener('click', () => {
        const targetId = button.getAttribute('data-target');
        const content = document.getElementById(targetId);
        const svg = button.querySelector('svg');
        const moduleSection = button.closest('.module-section');

        if (content.classList.contains('hidden')) {
          content.classList.remove('hidden');
          svg.classList.add('rotate-180');
          moduleSection.classList.add('active-module');
        } else {
          content.classList.add('hidden');
          svg.classList.remove('rotate-180');
          moduleSection.classList.remove('active-module');
        }
      });
    });

    $(document).ready(function() {
      setTimeout(() => {
        const successMessage = document.getElementById('message');
        const userError = document.getElementById('error');

        if (successMessage) {
          successMessage.classList.add('opacity-0');
          setTimeout(() => successMessage.remove(), 500);
        }

        if (userError) {
          userError.classList.add('opacity-0');
          setTimeout(() => userError.remove(), 500);
        }

        //remove session with post
        fetch('unset_session_msg.php', {
          method: 'POST'
        });
      }, 3000);
    });

    // Update the mark complete handler to check all modules
    document.querySelectorAll('.mark-complete-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
  
            const lessonId = this.dataset.lessonId;
            const btn = this;
            const lessonItem = btn.closest('.flex.items-center');
            const moduleSection = btn.closest('.module-section');
  
            fetch('./components/mark_lesson_complete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ lesson_id: lessonId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    btn.remove();
          
                    // Replace empty circle with checkmark
                    const circle = lessonItem.querySelector('.flex-shrink-0 div');
                    circle.innerHTML = `
                        <div class="w-4 h-4 rounded-full bg-green-500 flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    `;
            
                    // Check if all modules are now complete
                    const incompleteLessons = document.querySelectorAll('.mark-complete-btn');
                    if (incompleteLessons.length === 0) {
                        // Show the certificate section if not already present
                        if (!document.querySelector('.certificate-section')) {
                            const accordion = document.getElementById('module-accordion');
                            accordion.insertAdjacentHTML('afterend', `
                                <div class="mt-6 p-4 bg-gray-50 rounded-lg border border-gray-200 certificate-section">
                                    <div class="text-center">
                                        <h3 class="text-lg font-medium text-gray-900 mb-2">Congratulations! 🎉</h3>
                                        <p class="text-gray-600 mb-4">You've completed all modules in this course.</p>
                                        <a href="generate_certificate.php?course_id=<?= $courseId ?>" 
                                           class="inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-150">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Download Course Certificate
                                        </a>
                                    </div>
                                </div>
                            `);
                        }
                    }
          
                    // Update progress if needed
                    if (typeof updateProgress !== 'undefined') {
                        updateProgress();
                    }

                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });
    });
  </script>
</body>

</html>