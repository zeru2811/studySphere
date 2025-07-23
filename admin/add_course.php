<?php

session_start();
require "../requires/common.php";  
require "../requires/title.php";  
require "../requires/connect.php";  

// Get all categories for dropdown
$result = $mysqli->query("SELECT id, name FROM category ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Get all teachers for dropdown
$result = $mysqli->query("SELECT id, name FROM users WHERE role_id = 2 ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $teachers[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $title = trim($_POST['title']);
    $categoryId = intval($_POST['categoryId']);
    $teacherId = intval($_POST['teacherId']);
    $isCertificate = isset($_POST['isCertificate']) ? 1 : 0;
    $totalHours = intval($_POST['totalHours']);
    $description = trim($_POST['description']);
    $function = trim($_POST['function']);
    $realProjectCount = intval($_POST['realProjectCount']);

    // Keep existing thumbnail if no new file uploaded
    $thumbnailPath = $course['thumbnail'] ?? '';

    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if (in_array($_FILES['thumbnail']['type'], $allowedTypes)) {

            $uploadDir = __DIR__ . '/../uploads/thumbnails/';
            // var_dump($uploadDir);
            // var_dump(is_dir($uploadDir));
            // var_dump(is_writable($uploadDir));
            // exit;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filename = uniqid() . '_' . basename($_FILES['thumbnail']['name']);
            $targetPath = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $targetPath)) {
                $thumbnailPath = $filename;
            } else {
                $_SESSION['error'] = "Failed to upload thumbnail.";
            }

        } else {
            $_SESSION['error'] = "Invalid file type. Only JPG, PNG, or GIF allowed.";
        }
    }
    
    // Basic validation
    if (empty($name)){
        $_SESSION['error'] = "Course name is required";
    } elseif ($price <= 0) {
        $_SESSION['error'] = "Price must be greater than 0";
    } else {
        $stmt = $mysqli->prepare("INSERT INTO courses (name, price, title, categoryId, teacherId, isCertificate, totalHours, description, thumbnail, function, realProjectCount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sdssiiissii", $name, $price, $title, $categoryId, $teacherId, $isCertificate, $totalHours, $description, $thumbnailPath, $function, $realProjectCount);
    }
    if ($stmt->execute()) {
        $_SESSION['message'] = "Course created successfully";
        header("Location: courses.php");
        exit();
    } else {
        $_SESSION['error'] = "Error saving course: " . $mysqli->error;
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $course_id > 0 ? 'Edit' : 'Add'; ?> Course - StudySphere Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        input:focus{
            outline: none;
        }
        textarea:focus{
            outline: none;
        }
        select{
            outline: none;
        }
        .no-spinner::-webkit-outer-spin-button,
        .no-spinner::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .no-spinner {
            -moz-appearance: textfield;
        }
    </style>
</head>
<body class="bg-gray-100">


    <?php require './templates/admin_header.php';  
    require './templates/admin_sidebar.php';   ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800"><?php echo $course_id > 0 ? 'Edit Course' : 'Add New Course'; ?></h1>
            <a href="courses.php" class="text-blue-600 hover:text-blue-800 flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Back to Courses
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div id="alert-success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div id="alert-error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow rounded-lg p-6">
            <form method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Basic Information -->
                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">Basic Information</h2>
        
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 pt-3">Course Name*</label>
                            <input type="text" id="name" name="name" required 
                                   value="<?php echo htmlspecialchars($course['name'] ?? ''); ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 pt-3">Title</label>
                            <input type="text" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($course['title'] ?? ''); ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700 pt-3">Price*</label>
                            <div class="relative mt-1 rounded-md shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-1">
                                    <span class="text-gray-500 sm:text-sm">Ks</span>
                                </div>
                            
                                <input type="number" step="100" id="price" name="price" required 
                                       value="<?php echo htmlspecialchars($course['price'] ?? ''); ?>"
                                       class="block w-full rounded-md border-gray-300 pl-7 pr-12 focus:border-blue-500 focus:ring-blue-500 no-spinner">
                            </div>
                        </div>

                        <div>
                            <label for="thumbnail" class="block text-sm font-medium text-gray-700 pt-3">Thumbnail Image</label>
                            <input type="file" id="thumbnail" name="thumbnail"
                                   accept="image/*"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    
                            <?php if (!empty($course['thumbnail'])): ?>
                                <img src="../uploads/thumbnails/<?php echo htmlspecialchars($course['thumbnail']); ?>" alt="Thumbnail" class="h-24 mt-1 rounded border">
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Course Details -->
                    <div class="space-y-4">
                        <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">Course Details</h2>
                        
                        <div>
                            <label for="categoryId" class="block text-sm font-medium text-gray-700 pt-3">Category*</label>
                            <select id="categoryId" name="categoryId" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"
                                        <?php if (isset($course['categoryId']) && $course['categoryId'] == $category['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="teacherId" class="block text-sm font-medium text-gray-700 pt-3">Teacher*</label>
                            <select id="teacherId" name="teacherId" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select a teacher</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>"
                                        <?php if (isset($course['teacherId']) && $course['teacherId'] == $teacher['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($teacher['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="totalHours" class="block text-sm font-medium text-gray-700 pt-3">Total Hours</label>
                                <input type="number" id="totalHours" name="totalHours" 
                                       value="<?php echo htmlspecialchars($course['totalHours'] ?? ''); ?>"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 no-spinner">
                            </div>

                            <div class="flex items-end">
                                <div class="flex items-center h-5">
                                    <input id="isCertificate" name="isCertificate" type="checkbox" 
                                           <?php if (isset($course['isCertificate']) && $course['isCertificate']) echo 'checked'; ?>
                                           class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                </div>
                                <label for="isCertificate" class="ml-2 block text-sm text-gray-700">Certificate Available</label>
                            </div>
                        </div>

                        <div>
                            <label for="realProjectCount" class="block text-sm font-medium text-gray-700 pt-3">Real Project Count</label>
                            <input type="number" id="realProjectCount" name="realProjectCount"
                                   value="<?php echo htmlspecialchars($course['realProjectCount'] ?? ''); ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 no-spinner">
                        </div>
                    </div>

                    <!-- Full-width fields -->
                    <div class="md:col-span-2">
                        <div>
                            <label for="function" class="block text-sm font-medium text-gray-700 pt-3">Course Function</label>
                            <textarea id="function" name="function" rows="20"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"><?php echo htmlspecialchars($course['function'] ?? ''); ?></textarea>
                        </div>
                        <hr class="my-3">
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea id="description" name="description" rows="20"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border-gray-300 focus:border-blue-500 focus:ring-blue-500"><?php echo htmlspecialchars($course['description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <a href="courses.php" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Save Course
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script>
        setTimeout(() => {
            const successAlert = document.getElementById('alert-success');
            if (successAlert) successAlert.remove();

            const errorAlert = document.getElementById('alert-error');
            if (errorAlert) errorAlert.remove();
        }, 3000);
    </script>
</body>
</html>