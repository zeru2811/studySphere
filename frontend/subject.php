<?php
session_start();
$type = "Subject";
require '../templates/template_nav.php';
require '../requires/connect.php';
require '../requires/common_function.php';
$basePath = '/studysphere/frontend';
$_SESSION['role_id'] = 3;

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

// ✅ Get unique modules (subjects)
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

// ✅ Fetch lessons for each module
foreach ($modules as &$module) {
    $lessonsQuery = $mysqli->prepare("
        SELECT l.*
        FROM lessons l
        JOIN course_subject cs ON l.id = cs.lessonId
        WHERE cs.subjectId = ? AND cs.courseId = ?
        ORDER BY l.id
    ");
    $lessonsQuery->bind_param("ii", $module['id'], $courseId);
    $lessonsQuery->execute();
    $module['lessons'] = $lessonsQuery->get_result()->fetch_all(MYSQLI_ASSOC);
}
unset($module);

// Enrollment check
// $isEnrolled = false;
// $progress = 0;
// if (isset($_SESSION['user_id'])) {
//     $userId = $_SESSION['user_id'];
//     $enrollmentQuery = $mysqli->prepare("
//         SELECT * FROM enroll_course 
//         WHERE userId = ? AND courseId = ? AND status = TRUE
//     ");
//     $enrollmentQuery->bind_param("ii", $userId, $courseId);
//     $enrollmentQuery->execute();
//     $isEnrolled = $enrollmentQuery->get_result()->num_rows > 0;

//     if ($isEnrolled) {
//         $totalLessons = 0;
//         foreach ($modules as $module) {
//             $totalLessons += count($module['lessons']);
//         }
//         $completedLessons = 3; // TODO: Fetch actual completed lessons from DB
// $progress = $totalLessons > 0 ? round($completedLessons / $totalLessons * 100) : 0;

//     }
// }

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
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
<body class="bg-gray-50">
<div class="container mx-auto px-4 py-6">
  <div class="flex flex-col lg:flex-row gap-6">

    <!-- Main Content -->
    <div class="lg:w-3/4">

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

        <!-- Notes -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-10">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Your Notes & Questions</h3>

          <form class="mb-6" method="POST" action="save_note.php">
            <input type="hidden" name="lesson_id" value="<?= $currentLesson['id'] ?>">
            <textarea name="note" class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-500" rows="4" placeholder="Write a note or question..."></textarea>
            <div class="mt-2 flex justify-end">
              <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">Save Note</button>
            </div>
          </form>

          <?php if (isset($_SESSION['user_id'])): ?>
            <?php
              $notesQuery = $mysqli->prepare("
                SELECT c.*, u.name, u.thumbnail 
                FROM comment c
                JOIN users u ON c.userId = u.id
                WHERE c.lessonId = ? AND c.userId = ?
                ORDER BY c.created_at DESC
              ");
              $notesQuery->bind_param("ii", $currentLesson['id'], $_SESSION['user_id']);
              $notesQuery->execute();
              $notes = $notesQuery->get_result()->fetch_all(MYSQLI_ASSOC);
            ?>
            <div class="space-y-4">
              <?php foreach ($notes as $note): ?>
                <div class="bg-gray-100 rounded-lg p-4 flex items-start">
                  <img src="<?= htmlspecialchars($note['thumbnail'] ?? 'img/default-profile.png') ?>" alt="Profile" class="w-10 h-10 rounded-full mr-3">
                  <div class="flex-1">
                    <div class="text-sm text-gray-700"><?= htmlspecialchars($note['comment']) ?></div>
                    <div class="text-xs text-gray-500 mt-1 text-right"><?= date('M j, Y', strtotime($note['created_at'])) ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
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

        <div class="divide-y divide-gray-200 max-h-[calc(100vh-200px)] overflow-y-auto" id="module-accordion">
          <?php foreach ($modules as $moduleIndex => $module): 
              $isOpen = false;
              foreach ($module['lessons'] as $lesson) {
                  if (($currentLesson['id'] ?? null) == $lesson['id']) {
                      $isOpen = true;
                      break;
                  }
              }
          ?>
          <div data-index="<?= $moduleIndex ?>" class="module-section">
            <button type="button"
              class="module-toggle w-full px-4 py-3 text-left font-medium bg-gray-50 hover:bg-gray-100 flex justify-between items-center"
              data-target="content-<?= $moduleIndex ?>">
              <span><?= htmlspecialchars($module['name']) ?></span>
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform transition-transform duration-200 <?= $isOpen ? 'rotate-180' : '' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>
            <div id="content-<?= $moduleIndex ?>" class="pl-4 transition-all duration-200 ease-in-out overflow-hidden <?= $isOpen ? '' : 'hidden' ?>">
              <?php foreach ($module['lessons'] as $lesson): ?>
              <a href="?id=<?= $courseId ?>&lesson_id=<?= $lesson['id'] ?>" class="lesson-item flex items-center px-4 py-3 cursor-pointer <?= ($currentLesson['id'] ?? null) == $lesson['id'] ? 'active-lesson scroll-target' : '' ?>">
                <!-- <span class="text-gray-400 text-sm w-6"><?= $index + 1 ?></span> -->
                <span class="ml-2 <?= ($currentLesson['id'] ?? null) == $lesson['id'] ? 'font-medium text-indigo-600' : '' ?>"><?= htmlspecialchars($lesson['title']) ?></span>
                <span class="ml-auto text-xs <?= ($currentLesson['id'] ?? null) == $lesson['id'] ? 'text-indigo-600' : 'text-gray-500' ?>"><?= isset($lesson['duration']) ? preg_replace('/:00$/', '', $lesson['duration']) : 'N/A' ?></span>
              </a>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.module-toggle').forEach(button => {
  button.addEventListener('click', () => {
    const targetId = button.getAttribute('data-target');
    const content = document.getElementById(targetId);
    const svg = button.querySelector('svg');

    if (content.classList.contains('hidden')) {
      content.classList.remove('hidden');
      svg.classList.add('rotate-180');
    } else {
      content.classList.add('hidden');
      svg.classList.remove('rotate-180');
    }
  });
});
</script>
</body>
</html>
