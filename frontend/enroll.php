
<?php 
    session_start(); 
    $type = "Enroll";
    require '../templates/template_nav.php';
    require '../requires/connect.php';
    require '../requires/common_function.php';
    $basePath = '/studysphere/frontend';

    $courseId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($courseId <= 0) {
        die('Invalid Course ID');
    }

    // Get Course
    $where = "id = $courseId";
    $showResult = selectData('courses', $mysqli, $where);
    if ($showResult->num_rows > 0) {
        $data = $showResult->fetch_assoc();
    } else {
        die("Course not found");
    }

    // Get Rating
    $ratingSql = "SELECT AVG(ratingCount) AS avgRating, COUNT(*) AS total FROM course_feedback WHERE courseId = $courseId";
    $ratingResult = $mysqli->query($ratingSql);
    $average = $total = 0;
    if ($ratingResult && $ratingResult->num_rows > 0) {
        $ratingData = $ratingResult->fetch_assoc();
        $average = round($ratingData['avgRating'], 1);
        $total = $ratingData['total'];
    }

    $teacherName = '';
    $teacherQuery = $mysqli->query("
        SELECT users.name AS teacherName
        FROM courses
        JOIN users ON courses.teacherId = users.id
        WHERE courses.id = $courseId
    ");
    if ($teacherRow = $teacherQuery->fetch_assoc()) {
        $teacherName = $teacherRow['teacherName'];
    }

    // Get Modules + Lessons
    $modules = [];
    $moduleQuery = $mysqli->query("
        SELECT subject.id AS subjectId, subject.name AS subjectName
        FROM course_subject 
        JOIN subject ON course_subject.subjectId = subject.id
        WHERE course_subject.courseId = $courseId
        GROUP BY subject.id, subject.name
    ");

    while ($module = $moduleQuery->fetch_assoc()) {
        $subjectId = intval($module['subjectId']);
        $lessonData = [];

        // Lessons for the subject
        $lessonQuery = $mysqli->query("
            SELECT lessons.title, lessons.duration
            FROM course_subject
            JOIN lessons ON course_subject.lessonId = lessons.id
            WHERE course_subject.subjectId = $subjectId AND course_subject.courseId = $courseId
        ");

        while ($lesson = $lessonQuery->fetch_assoc()) {
            $lessonData[] = $lesson;
        }

        // Count and total duration
        $countQuery = $mysqli->query("
            SELECT COUNT(*) AS totalLessons, SEC_TO_TIME(SUM(TIME_TO_SEC(lessons.duration))) AS totalDuration
            FROM course_subject
            JOIN lessons ON course_subject.lessonId = lessons.id
            WHERE course_subject.subjectId = $subjectId AND course_subject.courseId = $courseId
        ");
        $countData = $countQuery->fetch_assoc();

        $totalDuration = $countData['totalDuration'] ?? '00:00:00';
        $totalDuration = explode('.', $totalDuration)[0];
        list($hours, $minutes, $seconds) = explode(':', $totalDuration);
        $durationText = '';

        if (intval($hours) > 0) {
            $durationText .= intval($hours) . ' ' . (intval($hours) === 1 ? 'hour' : 'hours') . ' ';
        }
        if (intval($minutes) > 0) {
            $durationText .= intval($minutes) . ' ' . (intval($minutes) === 1 ? 'minute' : 'minutes');
        }
        if ($durationText === '') {
            $durationText = '0 minutes';
        }

        $modules[] = [
            'name' => $module['subjectName'],
            'totalLessons' => $countData['totalLessons'] ?? 0,
            'totalDuration' => $durationText,
            'lessons' => $lessonData
        ];
    }
?>

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .enroll-btn:hover {
            transform: translateY(-2px);
        }
        .module-item:hover {
            background-color: #f5f3ff;
        }
        #menu {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: max-height 0.3s ease, opacity 0.3s ease;
        }

        #menu.open {
            max-height: 500px;
            opacity: 1;
        }

        /* On desktop, always show menu */
        @media (min-width: 768px) {
            #menu {
                max-height: none !important;
                opacity: 1 !important;
                display: flex !important;
                overflow: visible !important;
            }
        }
    </style>

    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Main Course Content -->
            <div class="lg:w-2/3">
                <!-- Breadcrumb -->
                <div class="flex items-center text-sm text-gray-500 mb-6">
                    <a href="index.php" class="hover:text-indigo-600">Home</a>
                    <span class="mx-2">/</span>
                    <a href="courses.php?catagory=1" class="hover:text-indigo-600">Web Development</a>
                    <span class="mx-2">/</span>
                    <span class="text-gray-700"><?= htmlspecialchars($data['name']) ?></span>
                </div>

                <!-- Course Header -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                    <div class="flex items-center mb-4">
                        <span class="bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded">POPULAR</span>
                        <span class="ml-2 text-sm text-gray-500">Updated: <?= date("F Y", strtotime($data['updated_at'])) ?></span>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-3"><?= htmlspecialchars($data['name']) ?></h1>
                    <p class="text-xl text-gray-600 mb-6"><?= htmlspecialchars($data['title']) ?></p>
                    <!-- Master full-stack development with Next.js, TypeScript, and modern tooling. -->
                    
                    <div class="flex flex-wrap items-center gap-4 mb-6">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            <span class="ml-1 text-gray-900 font-medium"><?= $average ?></span>
                            <span class="mx-1 text-gray-500">•</span>
                            <span class="text-gray-500"><?= $total ?> reviews</span>
                        </div>
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="ml-1 text-gray-500"><?= htmlspecialchars($data['totalHours']) ?> hours</span>
                        </div>
                        <?php if ($data['isCertificate']) : ?>
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                                <span class="ml-1 text-gray-500">Certificate</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-center mb-8">
                        <div class="flex -space-x-2">
                            <img class="w-10 h-10 rounded-full border-2 border-white" src="https://randomuser.me/api/portraits/women/44.jpg" alt="">
                            <img class="w-10 h-10 rounded-full border-2 border-white" src="https://randomuser.me/api/portraits/men/32.jpg" alt="">
                            <img class="w-10 h-10 rounded-full border-2 border-white" src="https://randomuser.me/api/portraits/women/68.jpg" alt="">
                        </div>
                        <span class="ml-3 text-gray-600">+1,240 students enrolled</span>
                    </div>

                    <!-- Course Image -->
                    <div class="rounded-xl overflow-hidden mb-8">
                        <img src="../uploads/thumbnails/<?= htmlspecialchars($data['thumbnail']) ?>" alt="Next.js Course" class="w-full h-64 object-cover">
                    </div>

                    <!-- Course Tabs -->
                    <div class="border-b border-gray-200 mb-6">
                        <div class="flex space-x-8">
                            <button class="py-4 px-1 border-b-2 font-medium text-sm border-indigo-500 text-indigo-600">
                                Overview
                            </button>
                            <!-- <button class="py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                Curriculum
                            </button>
                            <button class="py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                Reviews
                            </button>
                            <button class="py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                FAQ
                            </button> -->
                        </div>
                    </div>

                    <!-- Course Description -->
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">About This Course</h2>
                        <div class="prose max-w-none text-gray-600">
                            <?= $data['description'] ?>
                        </div>
                    </div>

                    <!-- What You'll Learn -->
                    <div class="bg-indigo-50 rounded-xl p-6 mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">What You'll Learn</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?= $data['function'] ?>
                        </div>
                    </div>
                    
                    <!-- Course Curriculum -->
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Course Curriculum</h2>
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <?php if (count($modules) === 0): ?>
                                <div class="p-4 text-gray-500">No curriculum added yet.</div>
                            <?php else: ?>
                                <?php foreach ($modules as $index => $module): ?>
                                    <div class="border-b border-gray-200 last:border-b-0">
                                        <!-- Toggle Button -->
                                        <button 
                                            class="module-toggle flex justify-between items-center w-full px-5 py-4 text-left bg-gray-50 hover:bg-gray-100 transition"
                                            data-target="module-content-<?= $index ?>"
                                        >
                                            <div>
                                                <span class="font-medium text-gray-900"><?= htmlspecialchars($module['name']) ?></span>
                                                <span class="block text-sm text-gray-500 mt-1">
                                                    <?= $module['totalLessons'] ?> lessons • <?= htmlspecialchars($module['totalDuration']) ?>
                                                </span>
                                            </div>
                                            <svg class="h-5 w-5 text-gray-400 toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <!-- Lesson List -->
                                        <div id="module-content-<?= $index ?>" class="module-content hidden px-5 py-2 bg-white transition-all duration-300">
                                            <?php foreach ($module['lessons'] as $lesson): ?>
                                                <div class="module-item flex items-center justify-between px-3 py-3 rounded-lg cursor-pointer hover:bg-gray-50">
                                                    <div class="flex items-center">
                                                        <svg class="h-5 w-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        <span class="text-gray-700"><?= htmlspecialchars($lesson['title']) ?></span>
                                                    </div>
                                                    <span class="text-sm text-gray-500">
                                                        <?= htmlspecialchars(substr($lesson['duration'], 0, 5)) ?> min
                                                    </span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
      
                  
                    



                    <!-- Instructor -->
                    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Instructor</h2>
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6">
                            <img src="https://randomuser.me/api/portraits/men/42.jpg" alt="Alex Johnson" class="w-20 h-20 rounded-full">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900"><?php echo $teacherName ?></h3>
                                <!-- <p class="text-gray-600 mb-3">Senior Full-Stack Developer & Instructor</p> -->
                                <!-- <div class="flex items-center mb-3">
                                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                    <span class="ml-1 text-gray-900 font-medium">4.8 Instructor Rating</span>
                                    <span class="mx-2 text-gray-300">•</span>
                                    <span class="text-gray-600">12,540 Reviews</span>
                                    <span class="mx-2 text-gray-300">•</span>
                                    <span class="text-gray-600">58,290 Students</span>
                                </div>
                                <p class="text-gray-600">Alex has been building web applications for over 10 years and specializes in React, Next.js, and TypeScript. He's worked with companies like Google, Stripe, and Vercel to build scalable applications.</p> -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enrollment Sidebar -->
            <div class="lg:w-1/3">
                <div class="sticky top-6">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
                        <!-- Price Section -->
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-end mb-2">
                                <span class="text-3xl font-bold text-gray-900"><span class="text-sm">Ks</span><?php echo $data['price'] ?></span>
                                <span class="ml-2 text-lg text-gray-500 line-through">$149</span>
                                <span class="ml-2 bg-red-100 text-red-800 text-xs font-medium px-2 py-0.5 rounded">40% OFF</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-500 mb-4">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Offer ends in 2 days</span>
                            </div>
                            <a href="subject.php" class="enroll-btn w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 px-4 rounded-lg transition-all duration-300 shadow hover:shadow-md">
                                Enroll Now
                            </a>
                            <p class="text-center text-sm text-gray-500 mt-3">30-Day Money-Back Guarantee</p>
                        </div>

                        <!-- Course Includes -->
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="font-bold text-gray-900 mb-3">This course includes:</h3>
                            <ul class="space-y-3">
                                <li class="flex items-center">
                                    <svg class="h-5 w-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-gray-600">32 hours on-demand video</span>
                                </li>
                                <li class="flex items-center">
                                    <svg class="h-5 w-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-gray-600">18 coding exercises</span>
                                </li>
                                <li class="flex items-center">
                                    <svg class="h-5 w-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-gray-600">3 real-world projects</span>
                                </li>
                                <li class="flex items-center">
                                    <svg class="h-5 w-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-gray-600">Certificate of completion</span>
                                </li>
                                <li class="flex items-center">
                                    <svg class="h-5 w-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-gray-600">Full lifetime access</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Share -->
                        <div class="p-6">
                            <h3 class="font-bold text-gray-900 mb-3">Share this course</h3>
                            <div class="flex space-x-3">
                                <button class="w-9 h-9 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center hover:bg-blue-200">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"></path>
                                    </svg>
                                </button>
                                <button class="w-9 h-9 rounded-full bg-blue-100 text-blue-400 flex items-center justify-center hover:bg-blue-200">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"></path>
                                    </svg>
                                </button>
                                <button class="w-9 h-9 rounded-full bg-red-100 text-red-500 flex items-center justify-center hover:bg-red-200">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"></path>
                                    </svg>
                                </button>
                                <button class="w-9 h-9 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center hover:bg-gray-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Apply Coupon -->
                    <div class="bg-white rounded-xl shadow-sm p-6 mt-6 border border-gray-200">
                        <h3 class="font-bold text-gray-900 mb-3">Apply Coupon</h3>
                        <div class="flex">
                            <input type="text" placeholder="Enter coupon code" class="flex-1 border border-gray-300 rounded-l-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <button class="bg-indigo-600 text-white px-4 py-2 rounded-r-lg hover:bg-indigo-700 transition-colors">
                                Apply
                            </button>
                        </div>
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
                const icon = button.querySelector('.toggle-icon');

                if (content.classList.contains('hidden')) {
                    content.classList.remove('hidden');
                    icon.setAttribute('d', 'M19 9l-7 7-7-7'); // Optional: change icon
                } else {
                    content.classList.add('hidden');
                    icon.setAttribute('d', 'M19 9l-7 7-7-7');
                }
            });
        });
    </script>


    <?php require '../templates/template_footer.php'; ?>