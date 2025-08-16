<?php 
session_start(); 
// var_dump($_SESSION['id']);
// exit();
// var_dump("<strong>Server time: </strong>" . date("Y-m-d H:i:s") . "<br>");
// exit();
$type = "Courses";
require '../templates/template_nav.php';
require '../requires/connect.php';
$basePath = '/studysphere/frontend';
// Get categories
$catRes = $mysqli->query("SELECT * FROM category");
$categories = $catRes->fetch_all(MYSQLI_ASSOC);

// Category filter from URL
$filterCat = isset($_GET['category']) ? intval($_GET['category']) : null;

// Get course feedback stats
$feedbackSql = "SELECT courseId, SUM(ratingCount) AS totalRating FROM course_feedback GROUP BY courseId";
$feedbackRes = $mysqli->query($feedbackSql);
$ratings = [];
while ($row = $feedbackRes->fetch_assoc()) {
    $ratings[$row['courseId']] = $row['totalRating'];
}

// Get all courses
$courseSql = "SELECT c.*, cat.name AS catName FROM courses c
              JOIN category cat ON cat.id = c.categoryId";
if ($filterCat) {
    $courseSql .= " WHERE c.categoryId = $filterCat";
}
$courseSql .= " ORDER BY c.created_at DESC";
$coursesRes = $mysqli->query($courseSql);
$courses = $coursesRes->fetch_all(MYSQLI_ASSOC);

// Separate popular and all (filtered) courses
$popularCourses = [];
$filteredCourses = [];
foreach ($courses as $course) {
    $courseId = $course['id'];
    $isPopular = isset($ratings[$courseId]) && $ratings[$courseId] >= 5;

    if ($isPopular) {
        $popularCourses[] = $course;
    }
    $filteredCourses[] = $course;
}

// Find latest 3 course IDs for NEW badge
// $newCourseIds = array_slice(array_column($courses, 'id'), 0, 3);
$newCourseIds = [];
$sql = "SELECT id FROM courses WHERE created_at >= NOW() - INTERVAL 1 DAY";
$result = $mysqli->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $newCourseIds[] = (int) $row['id'];
    }
}





?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Courses</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-3">Study Sphere Courses</h1>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                Join over 10,000 students advancing their skills with Study Sphere.
            </p>
        </div>

        <!-- Category Filter Buttons -->
        <div class="flex flex-wrap gap-2 mb-8 justify-between">
            <div class="flex justify-center items-center">
                <a href="courses.php" class="me-2 px-4 py-2 <?= is_null($filterCat) ? 'bg-indigo-600 text-white' : 'bg-white border' ?> rounded-full font-medium">
                    All Courses
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a href="?category=<?= $cat['id'] ?>" class=" me-2 px-4 py-2 <?= ($filterCat == $cat['id']) ? 'bg-indigo-600 text-white' : 'bg-white border' ?> rounded-full font-medium">
                        <?= htmlspecialchars($cat['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="relative w-full md:w-64">
                <input type="text" placeholder="Search courses..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>

        <!-- Popular Courses Section -->
        <?php if (!empty($popularCourses)): ?>
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Popular Courses</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            <?php foreach ($popularCourses as $course): ?>
                <a href="enroll.php?id=<?= $course['id'] ?>" class="course-card bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                    <div class="relative">
                        <img src="../uploads/thumbnails/<?= htmlspecialchars($course['thumbnail']) ?>" alt="<?= htmlspecialchars($course['name']) ?>" class="w-full h-48 object-cover">
                        <?php if (in_array($course['id'], $newCourseIds)): ?>
                            <div class="absolute top-2 right-2 bg-green-600 text-white text-xs font-bold px-2 py-1 rounded">NEW</div>
                        <?php elseif (!empty($ratings[$course['id']]) && $ratings[$course['id']] >= 5): ?>
                            <div class="absolute top-2 right-2 bg-indigo-600 text-white text-xs font-bold px-2 py-1 rounded">POPULAR</div>
                        <?php endif; ?>
                    </div>
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-sm font-medium text-indigo-600"><?= strtoupper($course['catName']) ?></span>
                            <span class="text-sm text-gray-500"><?= date('Y-m-d', strtotime($course['created_at'])) ?></span>
                        </div>
                        <h3 class="course-title text-xl font-bold text-gray-900 mb-3 transition-colors duration-300"><?= htmlspecialchars($course['name']) ?></h3>
                        <p class="text-gray-600 mb-4"><?= htmlspecialchars($course['title']) ?></p>
                        <div class="flex justify-between items-center">
                            <div class="flex items-center">
                                <div class="flex -space-x-1">
                                    <img class="w-6 h-6 rounded-full border-2 border-white" src="https://randomuser.me/api/portraits/women/44.jpg" alt="">
                                    <img class="w-6 h-6 rounded-full border-2 border-white" src="https://randomuser.me/api/portraits/men/32.jpg" alt="">
                                    <img class="w-6 h-6 rounded-full border-2 border-white" src="https://randomuser.me/api/portraits/women/68.jpg" alt="">
                                </div>
                                <span class="ml-2 text-sm text-gray-500">+<?= rand(50, 150) ?> enrolled</span>
                            </div>
                            <span class="text-lg font-bold text-gray-900">
                                Ks<?= $course['price'] ?>
                                <!-- <span class="text-sm text-gray-400 line-through">Ks//number_format($course['price'] + 50, 2) ?></span> -->
                            </span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- All Courses Section -->
        <h2 class="text-2xl font-bold text-gray-900 mb-6"><?= $filterCat ? 'Filtered Courses' : 'All Courses' ?></h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            <?php if (empty($filteredCourses)): ?>
                <p class="text-gray-500">No courses found.</p>
            <?php else: ?>
                <?php foreach ($filteredCourses as $course): ?>
                    <?php include 'components/course_card.php'; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php require '../templates/template_footer.php'; ?>
