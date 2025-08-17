<?php
session_start();
$type = "Learning Paths";
require '../requires/connect.php';
require '../templates/template_nav.php';
$basePath = '/studysphere/frontend';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}
// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$difficulty = isset($_GET['difficulty']) ? $_GET['difficulty'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Base query
$query = "SELECT lp.* FROM learning_path lp WHERE 1=1";
$params = [];
$types = '';

// Apply filters
if (!empty($search)) {
    $query .= " AND (lp.title LIKE ? OR lp.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}


$categoryQuery = "SELECT DISTINCT category FROM learning_path WHERE category IS NOT NULL AND category != ''";
$categoryResult = $mysqli->query($categoryQuery);

$availableCategories = [];
if ($categoryResult && $categoryResult->num_rows > 0) {
    while ($row = $categoryResult->fetch_assoc()) {
        $availableCategories[] = $row['category'];
    }
}



if (!empty($difficulty)) {
    $query .= " AND lp.difficulty = ?";
    $params[] = $difficulty;
    $types .= 's';
}

// Apply sorting
switch ($sort) {
    case 'popular':
        $query .= " ORDER BY lp.total_enrollments DESC";
        break;
    case 'duration':
        $query .= " ORDER BY (SELECT SUM(c.totalHours) FROM learning_path_courseid lpc JOIN courses c ON c.id = lpc.courseId WHERE lpc.learning_pathId = lp.id) DESC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY lp.created_at DESC";
        break;
}

// Prepare and execute query
$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$paths = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Learning Paths | Study Sphere</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .path-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); }
        .path-card { transition: all 0.3s ease; }
        .active-filter { background-color: #6366f1; color: white; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Page header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Learning Paths</h1>
            <p class="text-gray-600">Browse our curated collection of learning paths to master new skills.</p>
        </div>
        
        <!-- Search and filter -->
        <form method="GET" class="mb-8">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="relative w-full md:w-96">
                    <input type="text" name="search" placeholder="Search learning paths..." 
                           value="<?= htmlspecialchars($search) ?>" 
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>
                <div class="flex gap-2 flex-wrap">
                 
                    <select name="category" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600">
                        <option value="">All Categories</option>
                        <?php foreach ($availableCategories as $availableCategory): ?>
                            <option value="<?= htmlspecialchars($availableCategory) ?>" 
                                    <?= $category === $availableCategory ? 'selected' : '' ?>>
                                <?= htmlspecialchars($availableCategory) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="sort" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-600 focus:border-indigo-600">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                        <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Popular</option>
                        <option value="duration" <?= $sort === 'duration' ? 'selected' : '' ?>>Duration</option>
                    </select>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Apply</button>
                </div>
            </div>
            
            <!-- Difficulty filter chips -->
            <div class="mt-4 flex gap-2">
                <a href="?<?= http_build_query(array_merge($_GET, ['difficulty' => ''])) ?>" 
                   class="px-3 py-1 rounded-full text-sm <?= empty($difficulty) ? 'active-filter' : 'bg-gray-200 text-gray-700' ?>">
                   All Levels
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['difficulty' => 'Beginner'])) ?>" 
                   class="px-3 py-1 rounded-full text-sm <?= $difficulty === 'Beginner' ? 'active-filter' : 'bg-green-100 text-green-800' ?>">
                   Beginner
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['difficulty' => 'Intermediate'])) ?>" 
                   class="px-3 py-1 rounded-full text-sm <?= $difficulty === 'Intermediate' ? 'active-filter' : 'bg-blue-100 text-blue-800' ?>">
                   Intermediate
                </a>
                <a href="?<?= http_build_query(array_merge($_GET, ['difficulty' => 'Advanced'])) ?>" 
                   class="px-3 py-1 rounded-full text-sm <?= $difficulty === 'Advanced' ? 'active-filter' : 'bg-purple-100 text-purple-800' ?>">
                   Advanced
                </a>
            </div>
        </form>
        
        <!-- Learning paths grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($paths as $path): 
                // Get course count and total hours
                $stmt = $mysqli->prepare("SELECT COUNT(*) as course_count, SUM(c.totalHours) as total_hours 
                                        FROM learning_path_courseid lpc
                                        JOIN courses c ON c.id = lpc.courseId
                                        WHERE lpc.learning_pathId = ?");
                $stmt->bind_param("i", $path['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                $data = $result->fetch_assoc();
                $courseCount = $data['course_count'] ?? 0;
                $totalHours = $data['total_hours'] ?? 0;
            ?>
                <a href="learning_path_details.php?id=<?= $path['id'] ?>" class="path-card bg-white rounded-xl shadow-md overflow-hidden hover:no-underline">
                    <?php if ($path['thumbnail_url']): ?>
                        <img src="<?= htmlspecialchars($path['thumbnail_url']) ?>" alt="<?= htmlspecialchars($path['title']) ?>" class="w-full h-48 object-cover">
                    <?php else: ?>
                        <div class="w-full h-48 bg-indigo-100 flex items-center justify-center">
                            <i class="fas fa-book-open text-4xl text-indigo-600"></i>
                        </div>
                    <?php endif; ?>
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($path['title']) ?></h3>
                            <?php if ($path['is_featured']): ?>
                                <span class="text-xs font-medium px-2 py-1 bg-yellow-100 text-yellow-800 rounded">Featured</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-gray-600 mb-4 line-clamp-2"><?= htmlspecialchars($path['description']) ?></p>
                        
                        <div class="flex flex-wrap gap-4 text-sm text-gray-600">
                            <div class="flex items-center">
                                <i class="fas fa-book mr-2 text-indigo-600"></i>
                                <span><?= $courseCount ?> Courses</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-clock mr-2 text-indigo-600"></i>
                                <span><?= $totalHours ?> Hours</span>
                            </div>
                            <?php if ($path['total_enrollments'] > 0): ?>
                                <div class="flex items-center">
                                    <i class="fas fa-users mr-2 text-indigo-600"></i>
                                    <span><?= $path['total_enrollments'] ?> Enrolled</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <span class="text-xs px-2 py-1 bg-gray-100 text-gray-700 rounded">
                                    <?= htmlspecialchars($path['category']) ?>
                                </span>
                                <?php if ($path['difficulty'] === 'Beginner'): ?>
                                    <span class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded">Beginner</span>
                                <?php elseif ($path['difficulty'] === 'Intermediate'): ?>
                                    <span class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded">Intermediate</span>
                                <?php else: ?>
                                    <span class="text-xs px-2 py-1 bg-purple-100 text-purple-800 rounded">Advanced</span>
                                <?php endif; ?>
                            </div>
                            <span class="text-sm font-medium text-indigo-600 flex items-center">
                                View Path <i class="fas fa-chevron-right ml-1 text-xs"></i>
                            </span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Empty state -->
        <?php if (empty($paths)): ?>
            <div class="text-center py-12">
                <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-book-open text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-xl font-medium text-gray-900 mb-2">No learning paths found</h3>
                <p class="text-gray-600 max-w-md mx-auto">We couldn't find any learning paths matching your criteria. Try adjusting your search or filters.</p>
                <a href="learning_path.php" class="mt-4 inline-block px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Clear Filters
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Real-time search could be implemented with JavaScript for better UX
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="search"]');
            const form = document.querySelector('form');
            
            // Optional: Add debounce for search input
            // searchInput.addEventListener('input', function() {
            //     clearTimeout(this.timer);
            //     this.timer = setTimeout(() => {
            //         form.submit();
            //     }, 1000);
            // });
            
            // Auto-submit when select changes (optional)
            document.querySelectorAll('select').forEach(select => {
                select.addEventListener('change', function() {
                    form.submit();
                });
            });
        });
    </script>
</body>
</html>
<?php require '../templates/template_backtotop.php'  ?>
<?php require '../templates/template_footer.php'; ?>