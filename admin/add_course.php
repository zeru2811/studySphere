<?php
session_start();
require "../requires/common.php";  
require "../requires/title.php";  
require "../requires/connect.php";  

// Get all categories for dropdown
$categories = [];
$result = $mysqli->query("SELECT id, name FROM category ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

// Get all teachers for dropdown
$teachers = [];
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
    $thumbnailPath = '';

    // File upload
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['thumbnail']['type'], $allowedTypes)) {
            $uploadDir = __DIR__ . '/../uploads/thumbnails/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 755, true);

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

    // Validation and save
    if (empty($name)) {
        $_SESSION['error'] = "Course name is required";
    } elseif ($price <= 0) {
        $_SESSION['error'] = "Price must be greater than 0";
    } else {
        $stmt = $mysqli->prepare("INSERT INTO courses (name, price, title, categoryId, teacherId, isCertificate, totalHours, description, thumbnail, function, realProjectCount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sdsiiiisssi", $name, $price, $title, $categoryId, $teacherId, $isCertificate, $totalHours, $description, $thumbnailPath, $function, $realProjectCount);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Course created successfully";
            header("Location: courses.php");
            exit();
        } else {
            $_SESSION['error'] = "Error saving course: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Course - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        input:focus, textarea:focus, select:focus { outline: none; }
        .no-spinner::-webkit-outer-spin-button,
        .no-spinner::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .no-spinner { -moz-appearance: textfield; }
    </style>
</head>
<body class="bg-gray-100">
<?php require './templates/admin_header.php'; ?>
<?php require './templates/admin_sidebar.php'; ?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Add New Course</h1>
        <a href="courses.php" class="text-blue-600 hover:text-blue-800 flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back to Courses
        </a>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" enctype="multipart/form-data">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">Basic Information</h2>

                    <div>
                        <label for="name">Course Name*</label>
                        <input type="text" name="name" id="name" required class="mt-1 w-full border-gray-300 rounded shadow-sm">
                    </div>

                    <div>
                        <label for="title">Title</label>
                        <input type="text" name="title" id="title" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                    </div>

                    <div>
                        <label for="price">Price*</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-gray-500">Ks</span>
                            <input type="number" step="100" name="price" id="price" required class="pl-8 no-spinner w-full border-gray-300 rounded shadow-sm">
                        </div>
                    </div>

                    <div>
                        <label for="thumbnail">Thumbnail Image</label>
                        <input type="file" name="thumbnail" id="thumbnail" accept="image/*" class="mt-1 w-full border-gray-300 rounded shadow-sm">
                    </div>
                </div>

                <div class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-700 border-b pb-2">Course Details</h2>

                    <div>
                        <label for="categoryId">Category*</label>
                        <select name="categoryId" id="categoryId" required class="w-full border-gray-300 rounded shadow-sm mt-1">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="teacherId">Teacher*</label>
                        <select name="teacherId" id="teacherId" required class="w-full border-gray-300 rounded shadow-sm mt-1">
                            <option value="">Select Teacher</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['id'] ?>"><?= htmlspecialchars($teacher['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="totalHours">Total Hours</label>
                            <input type="number" name="totalHours" id="totalHours" class="w-full border-gray-300 rounded shadow-sm mt-1 no-spinner">
                        </div>
                        <div class="flex items-end mt-4">
                            <input type="checkbox" name="isCertificate" id="isCertificate" class="mr-2">
                            <label for="isCertificate">Certificate Available</label>
                        </div>
                    </div>

                    <div>
                        <label for="realProjectCount">Real Project Count</label>
                        <input type="number" name="realProjectCount" id="realProjectCount" class="w-full border-gray-300 rounded shadow-sm mt-1 no-spinner">
                    </div>
                </div>

                <div class="md:col-span-2">
                    <div>
                        <label for="function">Course Function</label>
                        <textarea name="function" id="function" rows="6" class="w-full border-gray-300 rounded shadow-sm mt-1"></textarea>
                    </div>

                    <div class="mt-4">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" rows="6" class="w-full border-gray-300 rounded shadow-sm mt-1"></textarea>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="courses.php" class="px-4 py-2 bg-gray-100 rounded border">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save Course</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<script>
    setTimeout(() => {
        document.querySelector('#alert-success')?.remove();
        document.querySelector('#alert-error')?.remove();
    }, 3000);
</script>
</body>
</html>
