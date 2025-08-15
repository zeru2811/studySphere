<?php
session_start();
$type = "Learning Path Details";
require '../requires/connect.php';
$basePath = '/studysphere/frontend';

// Get path ID from URL
$pathId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch learning path details
$pathQuery = $mysqli->prepare("SELECT * FROM learning_path WHERE id = ?");
$pathQuery->bind_param("i", $pathId);
$pathQuery->execute();
$pathResult = $pathQuery->get_result();
$path = $pathResult->fetch_assoc();

if (!$path) {
    header("Location: {$basePath}/learning_paths.php");
    exit();
}

// Fetch courses in this learning path
$coursesQuery = $mysqli->prepare("
    SELECT c.* 
    FROM learning_path_courseId lpc
    JOIN courses c ON lpc.courseId = c.id
    WHERE lpc.learning_pathId = ?
    ORDER BY lpc.sequence
");
$coursesQuery->bind_param("i", $pathId);
$coursesQuery->execute();
$coursesResult = $coursesQuery->get_result();
$courses = $coursesResult->fetch_all(MYSQLI_ASSOC);

// Fetch teacher details for each course
$teacherIds = array_column($courses, 'teacherId');
$teachers = [];
if (!empty($teacherIds)) {
    $placeholders = implode(',', array_fill(0, count($teacherIds), '?'));
    $types = str_repeat('i', count($teacherIds));
    
    $teachersQuery = $mysqli->prepare("
        SELECT id, name, profile_photo, description 
        FROM users 
        WHERE id IN ($placeholders)
    ");
    $teachersQuery->bind_param($types, ...$teacherIds);
    $teachersQuery->execute();
    $teachersResult = $teachersQuery->get_result();
    while ($teacher = $teachersResult->fetch_assoc()) {
        $teachers[$teacher['id']] = $teacher;
    }
}

// Calculate total duration and progress
$totalDuration = 0;
$completedLessons = 0;
$totalLessons = 0;

foreach ($courses as $course) {
    $lessonsQuery = $mysqli->prepare("
        SELECT COUNT(*) as total 
        FROM course_subject cs
        JOIN lessons l ON l.course_subject_id = cs.id
        WHERE cs.courseId = ?
    ");
    $lessonsQuery->bind_param("i", $course['id']);
    $lessonsQuery->execute();
    $lessonsResult = $lessonsQuery->get_result();
    $totalLessons += $lessonsResult->fetch_assoc()['total'];
    
    $totalDuration += $course['totalHours'];
}

$progressPercentage = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($path['title']) ?> | TechPath</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                        secondary: {
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            700: '#6d28d9',
                        },
                        accent: {
                            500: '#ec4899',
                            600: '#db2777',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                    },
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        .step-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .progress-bar {
            height: 8px;
            background-color: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            transition: width 0.5s ease;
        }

        .checkmark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background-color: #10b981;
            color: white;
            font-size: 12px;
        }

        .current-step {
            border-left: 4px solid #3b82f6;
            background-color: #f8fafc;
        }

        .subject-toggle {
            transition: all 0.3s ease;
        }

        .subject-toggle.active {
            transform: rotate(90deg);
        }

        .lessons-container {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .lessons-container.show {
            max-height: 1000px; /* Adjust based on your content */
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <?php require '../templates/template_nav.php'; ?>
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Path Header -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
            <div class="flex flex-col md:flex-row gap-6 items-start md:items-center">
                <div class="w-16 h-16 rounded-xl bg-indigo-100 flex items-center justify-center text-indigo-600">
                    <img src="<?= htmlspecialchars($path['thumbnail_url']) ?>" alt="<?= htmlspecialchars($path['title']) ?>" class="w-full h-full object-cover ">
                </div>

                <div class="flex-1">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 mb-1"><?= htmlspecialchars($path['title']) ?></h1>
                            <p class="text-gray-600"><?= htmlspecialchars($path['description']) ?></p>
                        </div>
                        <div class="flex gap-3">
                            <button class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-share-alt mr-2"></i> Share
                            </button>
                            <!-- <button class="px-4 py-2 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700">
                                Start Learning
                            </button> -->
                        </div>
                    </div>

                    <div class="mt-6">
                        <div class="flex flex-wrap gap-4 mb-4">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500"><i class="fas fa-clock"></i></span>
                                <span class="text-sm font-medium"><?= $totalDuration ?> hours</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500"><i class="fas fa-layer-group"></i></span>
                                <span class="text-sm font-medium"><?= count($courses) ?> Courses</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500"><i class="fas fa-user-graduate"></i></span>
                                <span class="text-sm font-medium"><?= $path['difficulty'] ?> Level</span>
                            </div>
                            <?php if ($path['is_certificate']): ?>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500"><i class="fas fa-certificate"></i></span>
                                <span class="text-sm font-medium">Certificate</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Path Content -->
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Main Content -->
            <div class="flex-1">
                <!-- Courses -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900">Curriculum</h2>
                        <p class="text-gray-600 mt-1">Follow the courses in order to complete this learning path</p>
                    </div>

                    <?php foreach ($courses as $index => $course): 
                        $isCompleted = false;
                        $isCurrent = $index === 0;
        
                        // Check if user is enrolled in this course
                        $isEnrolled = false;
                        if (isset($_SESSION['id'])) {
                            $enrollQuery = $mysqli->prepare("
                                SELECT id FROM enroll_course 
                                WHERE userId = ? AND courseId = ? AND payment_status = 'paid'
                            ");
                            $enrollQuery->bind_param("ii", $_SESSION['id'], $course['id']);
                            $enrollQuery->execute();
                            $enrollResult = $enrollQuery->get_result();
                            $isEnrolled = $enrollResult->num_rows > 0;
                        }
        
                        // Get subjects with their lessons for this course
                        $subjectsQuery = $mysqli->prepare("
                            SELECT s.id as subject_id, s.name, cs.id as course_subject_id
                            FROM course_subject cs
                            JOIN subject s ON cs.subjectId = s.id
                            WHERE cs.courseId = ?
                            ORDER BY cs.display_order
                        ");
                        $subjectsQuery->bind_param("i", $course['id']);
                        $subjectsQuery->execute();
                        $subjectsResult = $subjectsQuery->get_result();
                        $subjects = $subjectsResult->fetch_all(MYSQLI_ASSOC);
                    ?>
                    <div class="step-card p-6 border-b border-gray-100 <?= $isCurrent ? 'current-step' : '' ?> hover:bg-gray-50 transition-all">
                        <div class="flex items-start gap-4">
                            <?php if ($isCompleted): ?>
                                <div class="checkmark">
                                    <i class="fas fa-check"></i>
                                </div>
                            <?php elseif ($isCurrent): ?>
                                <div class="w-6 h-6 rounded-full bg-primary-100 border-2 border-primary-500 flex items-center justify-center">
                                    <div class="w-2 h-2 rounded-full bg-primary-600"></div>
                                </div>
                            <?php else: ?>
                                <div class="w-6 h-6 rounded-full bg-gray-100 border-2 border-gray-300 flex items-center justify-center text-gray-400">
                                    <?= $index + 1 ?>
                                </div>
                            <?php endif; ?>
            
                            <div class="flex-1">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                                    <h3 class="text-lg font-bold text-gray-900"><?= $course['name'] ?></h3>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full"><?= $course['totalHours'] ?> hours</span>
                                        <?php if ($isEnrolled): ?>
                                            <a href="subject.php?id=<?= $course['id'] ?>" 
                                               class="px-3 py-1 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                                                Continue
                                            </a>
                                        <?php else: ?>
                                            <a href="enroll.php?id=<?= $course['id'] ?>" 
                                               class="px-3 py-1 text-sm font-medium rounded-lg bg-secondary-600 text-white hover:bg-secondary-700 transition-colors">
                                                Enroll Now
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-gray-600 mb-4 flex items-center gap-2">
                                    <h3 class="font-bold text-gray-900"><?= htmlspecialchars($course['title']) ?></h3>
                                </div>
                                <p class="text-gray-600 mb-4"><?= htmlspecialchars(mb_substr($course['description'], 0, 150)) ?><?= mb_strlen($course['description']) > 150 ? '...' : '' ?></p>

                                <div class="space-y-3">
                                    <?php foreach ($subjects as $subject): 
                                        // Get lessons for this subject
                                        $lessonsQuery = $mysqli->prepare("
                                            SELECT id, title, duration 
                                            FROM lessons 
                                            WHERE course_subject_id = ?
                                            ORDER BY id
                                        ");
                                        $lessonsQuery->bind_param("i", $subject['course_subject_id']);
                                        $lessonsQuery->execute();
                                        $lessonsResult = $lessonsQuery->get_result();
                                        $lessons = $lessonsResult->fetch_all(MYSQLI_ASSOC);
                                    ?>
                                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                                        <button class="subject-toggle-btn w-full flex items-center justify-between p-4 hover:bg-gray-50 transition-colors" 
                                                data-target="lessons-<?= $subject['course_subject_id'] ?>">
                                            <div class="flex items-center gap-3">
                                                <i class="fas fa-chevron-right text-gray-500 subject-toggle"></i>
                                                <h4 class="font-medium text-gray-900"><?= htmlspecialchars($subject['name']) ?></h4>
                                            </div>
                                            <span class="text-sm text-gray-500"><?= count($lessons) ?> lessons</span>
                                        </button>
                        
                                        <div id="lessons-<?= $subject['course_subject_id'] ?>" class="lessons-container bg-gray-50">
                                            <ul class="divide-y divide-gray-200">
                                                <?php foreach ($lessons as $lesson): ?>
                                                <li class="p-4 hover:bg-gray-100 transition-colors">
                                                    <div class="flex items-center gap-3">
                                                        <i class="fas fa-play-circle text-primary-600"></i>
                                                        <div>
                                                            <h5 class="font-medium text-gray-900"><?= htmlspecialchars($lesson['title']) ?></h5>
                                                            <p class="text-xs text-gray-500"><?= $lesson['duration'] ?></p>
                                                        </div>
                                                    </div>
                                                </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Instructor -->
                <?php if (!empty($teachers)): ?>
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">About the Instructors</h2>
                    <?php foreach ($teachers as $teacher): ?>
                    <div class="flex flex-col sm:flex-row gap-6 mb-6">
                        <img src="<?= htmlspecialchars($teacher['profile_picture'] ?? 'https://randomuser.me/api/portraits/men/32.jpg') ?>" 
                             alt="<?= htmlspecialchars($teacher['name']) ?>" 
                             class="w-20 h-20 rounded-full">
                        <div>
                            <h3 class="font-bold text-gray-900 text-lg mb-1"><?= htmlspecialchars($teacher['name']) ?></h3>
                            <p class="text-gray-600 mb-3">Instructor</p>
                            <p class="text-gray-600 mb-4"><?= htmlspecialchars($teacher['bio'] ?? 'Experienced instructor with years of practical knowledge.') ?></p>
                            <div class="flex gap-3">
                                <a href="#" class="text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1">
                                    <i class="fab fa-github"></i> GitHub
                                </a>
                                <a href="#" class="text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1">
                                    <i class="fab fa-twitter"></i> Twitter
                                </a>
                                <a href="#" class="text-primary-600 hover:text-primary-700 font-medium flex items-center gap-1">
                                    <i class="fas fa-globe"></i> Website
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar remains the same -->
            <aside class="w-full lg:w-80 space-y-6">
                <!-- Progress Summary -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Your Progress</h2>
                    <div class="mb-2 text-sm text-gray-600 font-medium flex justify-between">
                        <span><?= $progressPercentage ?>% completed</span>
                        <span><?= $completedLessons ?> / <?= $totalLessons ?> lessons</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $progressPercentage ?>%"></div>
                    </div>
                    <button class="mt-4 w-full py-2 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                        Resume Learning
                    </button>
                </div>

                <!-- Quick Links -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Quick Links</h2>
                    <ul class="space-y-3 text-sm text-gray-700">
                        <li>
                            <a href="#" class="flex items-center gap-2 hover:text-primary-600 transition-colors">
                                <i class="fas fa-lightbulb"></i> Learning Tips
                            </a>
                        </li>
                        <li>
                            <a href="#" class="flex items-center gap-2 hover:text-primary-600 transition-colors">
                                <i class="fas fa-calendar-alt"></i> Set Study Schedule
                            </a>
                        </li>
                        <li>
                            <a href="#" class="flex items-center gap-2 hover:text-primary-600 transition-colors">
                                <i class="fas fa-question-circle"></i> FAQs
                            </a>
                        </li>
                        <li>
                            <a href="#" class="flex items-center gap-2 hover:text-primary-600 transition-colors">
                                <i class="fas fa-headset"></i> Contact Support
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Community CTA -->
                <div class="bg-gradient-to-br from-primary-500 to-secondary-600 text-white rounded-xl p-6 shadow-md">
                    <h3 class="text-lg font-bold mb-2">Join the Community</h3>
                    <p class="text-sm mb-4">Connect with learners, share your projects, and get feedback from mentors.</p>
                    <button class="w-full py-2 text-sm font-medium bg-white text-primary-700 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-users mr-2"></i> Go to Community
                    </button>
                </div>
            </aside>
        </div>
    </div>
    <?php require '../templates/template_footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle subject lessons
            const toggleButtons = document.querySelectorAll('.subject-toggle-btn');
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const target = document.getElementById(targetId);
                    const icon = this.querySelector('.subject-toggle');
                    
                    // Toggle the show class
                    target.classList.toggle('show');
                    // Toggle the active class for the icon
                    icon.classList.toggle('active');
                });
            });
        });
    </script>
</body>
</html>