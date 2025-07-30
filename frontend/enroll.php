<?php
session_start();
$type = "Enroll";

require '../requires/connect.php';
require '../requires/common_function.php';
$basePath = '/studysphere/frontend';


// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$roleId = isset($_SESSION['role_id']) ? intval($_SESSION['role_id']) : 0;
$userId = isset($_SESSION['id']) ? intval($_SESSION['id']) : 0;
$courseId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($courseId <= 0) {
    die('Invalid Course ID');
}

// Get user data
$whereUser = "id = $userId";
$userData = selectData('users', $mysqli, $whereUser);
if (!empty($userData)) {
    $user = $userData->fetch_assoc();
    $roleId = isset($user['role_id']) ? intval($user['role_id']) : 0;
} else {
    die('User not found');
}

// Get Course
$where = "id = $courseId";
$showResult = selectData('courses', $mysqli, $where);
if ($showResult->num_rows > 0) {
    $data = $showResult->fetch_assoc();
} else {
    die("Course not found");
}

// Check if user is already enrolled
$enrollmentCheck = $mysqli->query("SELECT * FROM enroll_course WHERE userId = $userId AND courseId = $courseId");
if ($enrollmentCheck && $enrollmentCheck->num_rows > 0) {
    $enrollment = $enrollmentCheck->fetch_assoc();
    if ($enrollment['payment_status'] === 'paid') {
        header("Location: subject.php?id=$courseId");
        exit();
    }
}


// Handle form submission


// Get Rating
$ratingSql = "SELECT AVG(ratingCount) AS avgRating, COUNT(*) AS total FROM course_feedback WHERE courseId = $courseId";
$ratingResult = $mysqli->query($ratingSql);
$average = $total = 0;
if ($ratingResult && $ratingResult->num_rows > 0) {
    $ratingData = $ratingResult->fetch_assoc();
    $average = round($ratingData['avgRating'], 1);
    $total = $ratingData['total'];
}

$real = "SELECT * FROM courses WHERE id = $courseId";
$realPResult = $mysqli->query($real);
$realResult = $realPResult->fetch_assoc();

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

    $allDurationQuery = $mysqli->query("
        SELECT 
            COUNT(*) AS totalLessons, 
            SEC_TO_TIME(SUM(TIME_TO_SEC(lessons.duration))) AS totalDuration
        FROM course_subject
        JOIN lessons ON course_subject.lessonId = lessons.id
        WHERE course_subject.courseId = $courseId
    ");
    $countData = $countQuery->fetch_assoc();
    $allDurationData = $allDurationQuery->fetch_assoc();

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


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $userId = $_SESSION['id'];
        $paymentMethod = $_POST['payment_method'] ?? null;
        $transactionNo = $_POST['transaction_no'] ?? null;
        
        if (!$paymentMethod){
            $_SESSION['error'] = "Please select a payment method.";
        }

    

        //check duplication
        $checkDuplication = $mysqli->prepare("SELECT id FROM enroll_course WHERE userId = ? AND courseId = ?");
        $checkDuplication->bind_param("ii", $userId, $courseId);
        $checkDuplication->execute();
        $checkDuplication->store_result();
        if ($checkDuplication && $checkDuplication->num_rows > 0) {
            $_SESSION['warning'] = "You are already enrolled in this course. Please wait for approval.";
            header("Location: enroll.php?id=$courseId");
            exit();
        }else{
            // Step 1: Enroll Course
            $stmt = $mysqli->prepare("INSERT INTO enroll_course (userId, courseId) VALUES (?, ?)");
            $stmt->bind_param("ii", $userId, $courseId);
            if (!$stmt->execute()){
                $_SESSION['error'] = "Failed to enroll in course. Please try again.";
            }
        }
        
        $enrollCourseId = $stmt->insert_id;

        // Step 2: Get payment type ID
        $stmt = $mysqli->prepare("SELECT id FROM payment_type WHERE name = ?");
        $stmt->bind_param("s", $paymentMethod);
        $stmt->execute();
        $result = $stmt->get_result();
        $paymentType = $result->fetch_assoc();
        if (!$paymentType){
            $_SESSION['error'] = "Invalid payment method.";
        }
        $paymentTypeId = $paymentType['id'];

        // Step 3: Insert Payment
        $path = null;
        $amount = ($roleId == 3) ? $data['price'] / 2 : $data['price'];

        if ($paymentMethod === 'kpay') {
            if (empty($transactionNo)) throw new Exception("Transaction number required.");

            // Upload image
            if (!isset($_FILES['kpay_image']) || $_FILES['kpay_image']['error'] !== 0) {
                $_SESSION['error'] = "Please upload a valid KPay screenshot.";
            }

            $image = $_FILES['kpay_image'];
            $ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
            $validExt = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $validExt)) {
                $_SESSION['error'] = "Invalid image format. Allowed: " . implode(', ', $validExt);
            }

            if ($image['size'] > 2 * 1024 * 1024) {
                $_SESSION['error'] = "Image size exceeds 2MB limit.";
            }

            $fileName = uniqid('', true) . '.' . $ext;
            $uploadDir = __DIR__ . "/../uploads/payments";
            $uploadPath = $uploadDir . "/" . $fileName;
            $relativePath = $fileName;

            // Ensure directory exists
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    $_SESSION['error'] = "Failed to create upload directory.";
                } else {
                    chmod($uploadDir, 0777);
                }
            }

            // Save file
            if (!move_uploaded_file($image['tmp_name'], $uploadPath)) {
                $_SESSION['error'] = "Failed to upload image. Please try again.";
            } else {
                $path = $relativePath;
            }

        } elseif ($paymentMethod === 'cash') {
            // No image upload for cash payment
            $path = null;
            if (empty($transactionNo)) {
                $transactionNo = 'cash-payment';
            }

        } else {
            $_SESSION['error'] = "Invalid payment method selected.";
        }

        $stmt = $mysqli->prepare("INSERT INTO enroll_payment (paymentTypeId, enroll_courseId, transitionId, amount, screenshot_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisds", $paymentTypeId, $enrollCourseId, $transactionNo, $amount, $path);
        $stmt->execute();

        $_SESSION['success'] = "Enrollment successful. Please wait for approval.";
        header("Location: enroll.php?id=$courseId");
        exit;
}

$checkEnroll = $mysqli->query("SELECT id, payment_status FROM enroll_course WHERE userId = $userId AND courseId = $courseId");
if ($checkEnroll->num_rows > 0) {
    $enrollment = $checkEnroll->fetch_assoc();
    $enrollmentStatus = $enrollment['payment_status'] ?? 'pending';
}else{
    $enrollmentStatus = null;
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
    @layer utilities {
        .animate-3d-rotate {
            animation: rotate3d 4s infinite ease-in-out;
        }
        .animate-pulse-glow {
            animation: pulse-glow 2s infinite ease-in-out;
        }
        @keyframes rotate3d {
            0%, 100% {
                transform: rotateY(0deg) rotateX(5deg);
            }
            50% {
                transform: rotateY(15deg) rotateX(-5deg);
            }
        }
        @keyframes pulse-glow {
            0%, 100% {
                box-shadow: 0 0 10px rgba(234, 179, 8, 0.5);
            }
            50% {
                box-shadow: 0 0 20px rgba(234, 179, 8, 0.8);
            }
        }
    }

    .animate-fade-in-up {
        animation: fadeInUp 0.3s ease-out forwards;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
<?php require '../templates/template_nav.php'; ?>
<div class="container mx-auto px-4 ">
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
            <?php if (isset($_SESSION['warning'])): ?>
                <div class="bg-yellow-100 text-yellow-700 px-4 py-2 rounded mb-4"><?= $_SESSION['warning'] ?></div>
                <?php unset($_SESSION['warning']); ?>
            <?php endif; ?>
             <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 text-green-700 px-4 py-2 rounded mb-4"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
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
                        <span class="ml-1 text-gray-500"><?= ltrim(explode(':', $allDurationData['totalDuration'])[0], '0') ?> hours</span>
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
                                        data-target="module-content-<?= $index ?>">
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
                                                    <?= htmlspecialchars(substr($lesson['duration'], 0, 5)) ?>
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
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <!-- Price & Enrollment Section -->
                    <div class="p-6 border-b border-gray-200">
                        
                        <?php if ($roleId == 3 || $roleId == 4): ?>
                            <div class="mb-6">
                                <h3 class="text-sm font-medium text-gray-500 mb-1">Course Price</h3>
                                <div class="flex items-baseline flex-wrap gap-2">
                                    <span class="text-3xl font-bold text-gray-900">
                                        Ks<?= ($roleId == 3) ? number_format($data['price'] / 2) : number_format($data['price']); ?>
                                    </span>

                                    <?php if ($roleId == 3): ?>
                                        <span class="text-lg text-gray-400 line-through">
                                            Ks<?= number_format($data['price']); ?>
                                        </span>
                                        <span class="bg-green-100 text-green-800 text-xs font-semibold px-2 py-0.5 rounded-full">
                                            50% Student Discount
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($enrollmentStatus === null): ?>
                                <!-- Not enrolled -->
                                <a href="#" id="openModalBtn"
                                   class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 px-4 rounded-lg transition-all duration-200 shadow-sm hover:shadow-md text-center focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                                    Enroll Now
                                </a>
                            <?php elseif ($enrollmentStatus === 'pending'): ?>
                                <!-- Enrolled, payment pending -->
                                <div class="relative group cursor-not-allowed">
                                    
                                    <div class="relative w-full px-6 py-4 bg-yellow-400 text-yellow-900 font-bold rounded-lg shadow-lg transform-style-preserve-3d animate-pulse animate-3d-rotate animate-pulse-glow transition-all duration-300">
                                        <div class="flex items-center justify-center space-x-2">
                                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            <span>Payment Pending...</span>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($enrollmentStatus === 'paid'): ?>
                                <!-- Enrolled, payment done -->
                                <a href="subject.php?id=<?= $data['id'] ?>"
                                   class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 px-4 rounded-lg transition-all duration-200 shadow-sm hover:shadow-md text-center focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                                    View Course
                                </a>
                            <?php elseif ($enrollmentStatus === 'failed'): ?>
                                <!-- Enrolled, payment rejected -->
                                <div class="bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-4">
                                    <p class="font-medium">Payment Rejected</p>
                                    <p class="text-sm">Please contact support for more details.</p>
                                </div>
                                <a href="#" id="openModalBtn"
                                    class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 px-4 rounded-lg transition-all duration-200 shadow-sm hover:shadow-md text-center focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                                    Re-Enroll                       
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($roleId == 1 || $roleId == 2): ?>
                            <a href="subject.php?id=<?= $data['id'] ?>"
                               class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 px-4 rounded-lg transition-all duration-200 shadow-sm hover:shadow-md text-center focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                                View Course
                            </a>
                        <?php endif; ?>



                        

                        <!-- Enroll Now Button -->
                        <!-- <a href="#" id="openModalBtn"
                            class="block w-full max-w-xs bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 px-4 rounded-lg transition-all duration-200 shadow-sm hover:shadow-md text-center focus:ring-2 focus:ring-indigo-200 focus:outline-none">
                            Enroll Now
                        </a> -->

                        <div class="mt-4 flex items-center justify-center text-sm text-gray-500">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Lifetime access</span>
                        </div>
                    </div>

                    <!-- Course Includes -->
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="font-bold text-lg text-gray-900 mb-4">This course includes:</h3>
                        <ul class="space-y-3">
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-indigo-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-600">
                                    <?= ltrim(explode(':', $allDurationData['totalDuration'])[0], '0') ?> hours on-demand video
                                </span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-indigo-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-600"><?= $allDurationData['totalLessons'] ?> coding exercises</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-indigo-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-600"><?= $realResult['realProjectCount'] ?> real-world projects</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-indigo-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-600">Downloadable resources</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-indigo-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-gray-600">Certificate of completion</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Share Section -->
                    <div class="p-6">
                        <h3 class="font-bold text-lg text-gray-900 mb-4">Share this course</h3>
                        <div class="flex justify-center space-x-4">
                            <a href="#" class="text-gray-500 hover:text-purple-600 transition-colors">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"></path>
                                </svg>
                            </a>
                            <a href="#" class="text-gray-500 hover:text-purple-400 transition-colors">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"></path>
                                </svg>
                            </a>
                            <a href="#" class="text-gray-500 hover:text-red-500 transition-colors">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"></path>
                                </svg>
                            </a>
                            <a href="javascript:void(0)" onclick="showSModal()" class="text-gray-500 hover:text-gray-700 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Share Modal -->
                <div id="copyModal" class="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 hidden transition-opacity duration-300">
                    <div class="bg-white p-6 rounded-xl shadow-2xl w-full max-w-md mx-4 animate-fade-in-up">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-semibold text-gray-800">Share this course</h2>
                            <button onclick="hideModal()" class="text-gray-500 hover:text-gray-700 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="">
                            <div class="relative">
                                <input id="copyInput" type="text"
                                    value="<?= htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>"
                                    class="border border-gray-300 rounded-lg w-full p-3 pr-12 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                <button onclick="copyText()" class="absolute right-2 top-1/2 -translate-y-1/2 bg-indigo-100 text-indigo-700 px-3 py-1 rounded-md text-sm font-medium hover:bg-indigo-200 transition-colors">
                                    Copy
                                </button>
                            </div>
                            <p id="copyFeedback" class="text-sm text-green-600 mt-1 h-5"></p>
                        </div>


                    </div>
                </div>


                <!-- Apply Coupon -->
                <!-- <div class="bg-white rounded-xl shadow-sm p-6 mt-6 border border-gray-200">
                        <h3 class="font-bold text-gray-900 mb-3">Apply Coupon</h3>
                        <div class="flex">
                            <input type="text" placeholder="Enter coupon code" class="flex-1 border border-gray-300 rounded-l-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <button class="bg-indigo-600 text-white px-4 py-2 rounded-r-lg hover:bg-indigo-700 transition-colors">
                                Apply
                            </button>
                        </div>
                    </div> -->
            </div>
        </div>
    </div>
</div>
<?php
if ($roleId == 3) {
    $amount = ($data['price'] / 2);
} else {
    $amount = ($data['price']);
} ?>
<!-- Modal Overlay -->
<div id="paymentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <!-- Modal Card -->
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md overflow-hidden relative">

        <!-- Close Button -->
        <button id="closeModalBtn" class="absolute top-3 right-3 text-gray-600 hover:text-black text-2xl">&times;</button>

        <!-- Header -->
        <div class="bg-purple-600 p-6 text-white">
            <h1 class="text-2xl font-bold">Complete Your Payment</h1>
            <p class="text-purple-100">Choose your preferred payment method</p>
        </div>
        
        <div class="flex items-baseline flex-wrap gap-2 mt-3 px-6">
            <span class="text-3xl font-bold text-gray-900">
                Ks<?= number_format($amount) ?>
            </span>

            <?php if ($roleId == 3): ?>
                <span class="text-lg text-gray-400 line-through">
                    Ks<?= number_format($data['price']) ?>
                </span>
                <div class="w-full">
                    <span class="bg-green-100 text-green-800 text-xs font-semibold px-2 py-0.5 rounded-full">
                        50% Student Discount
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Payment Form -->
        <form method="POST" enctype="multipart/form-data" class="p-4 space-y-6">
            <input type="hidden" name="course_id" value="<?= $courseId ?>">
            <!-- Payment Method -->
            <div>
                <label class="block text-gray-700 font-medium mb-3">Payment Method</label>
                <div class="space-y-3">
                    <!-- KPay Option -->
                    <div class="flex items-center">
                        <input id="kpay" name="payment_method" type="radio" value="kpay" class="h-5 w-5 text-purple-600" checked required>
                        <label for="kpay" class="ml-3 flex items-center">
                            <img src="../img/image1.png" alt="KPay Logo" class="h-5 w-5 mr-2">
                            <span class="text-gray-700">KPay</span>
                        </label>
                    </div>

                    <!-- Cash Option -->
                    <div class="flex items-center">
                        <input id="cash" name="payment_method" type="radio" value="cash" class="h-5 w-5 text-purple-600" required>
                        <label for="cash" class="ml-3 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span class="text-gray-700">Cash</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- KPay Details -->
            <div id="kpay-details" class="space-y-4 transition-all duration-200">
                <div>
                    <label for="transaction_no" class="block text-gray-700 font-medium mb-2">KPay Transaction Number</label>
                    <input type="text" id="transaction_no" name="transaction_no"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none"
                        placeholder="Enter transaction number" required>
                </div>
                <div>
                    <label for="kpay_image" class="block text-gray-700 font-medium mb-2">Upload KPay Screenshot</label>
                    <input type="file" id="kpay_image" accept="image/*" name="kpay_image"
                        class="w-full p-2 border border-gray-300 rounded-lg bg-white focus:outline-none" required>
                    <p class="text-xs text-gray-500 mt-1">Only JPG, PNG, or GIF (Max 2MB)</p>
                </div>
            </div>

            <!-- Cash Info -->
            <div id="cash-details" class="transition-all duration-200 hidden">
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Please prepare exact amount. Your order will be confirmed when payment is received.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Confirm Payment
            </button>
        </form>
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

    function showSModal() {
        document.getElementById("copyModal").classList.remove("hidden");
    }

    function hideModal() {
        document.getElementById("copyModal").classList.add("hidden");
    }

    function copyText() {
        const copyText = document.getElementById("copyInput");
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        document.execCommand("copy");

        const feedback = document.getElementById("copyFeedback");
        feedback.textContent = "Link copied to clipboard!";
        setTimeout(() => feedback.textContent = "", 2000);
    }

    // Show/hide modal
    const modal = document.getElementById('paymentModal');
    document.getElementById('openModalBtn').addEventListener('click', e => {
      e.preventDefault();
      modal.classList.remove('hidden');
      modal.classList.add('flex');
    });

    document.getElementById('closeModalBtn').addEventListener('click', () => {
      modal.classList.add('hidden');
    });

    // Switch between payment methods
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const isKPay = this.value === 'kpay';
        
            // Toggle visibility
            document.getElementById('kpay-details').classList.toggle('hidden', !isKPay);
            document.getElementById('cash-details').classList.toggle('hidden', isKPay);
        
            // Toggle required attributes
            const transactionInput = document.querySelector('[name="transaction_no"]');
            const kpayImageInput = document.querySelector('[name="kpay_image"]');
        
            if (isKPay) {
                // For KPay - add required
                if (transactionInput) transactionInput.required = true;
                if (kpayImageInput) kpayImageInput.required = true;
            } else {
                // For Cash - remove required
                if (transactionInput) transactionInput.required = false;
                if (kpayImageInput) kpayImageInput.required = false;
            }
        });
    });


</script>


<?php require '../templates/template_footer.php'; ?>