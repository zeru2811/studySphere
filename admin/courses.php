<?php  
session_start();
require "../requires/common.php";  
require "../requires/title.php";  
require "../requires/connect.php";  
$pagetitle = "Courses";
$currentPage = basename($_SERVER['PHP_SELF']);  
require './templates/admin_header.php';  
require './templates/admin_sidebar.php';  

?>

<?php if (isset($_SESSION['message'])): ?>
    <div class="fixed top-4 right-4 z-50">
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg shadow-lg transform transition-all duration-300 ease-in-out animate-slide-in" role="alert">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium"><?php echo $_SESSION['message'];
                                                    unset($_SESSION['message']); ?></p>
                </div>
                <button class="ml-auto -mx-1.5 -my-1.5 bg-green-100 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex h-8 w-8" onclick="this.parentElement.parentElement.remove()">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="fixed top-4 right-4 z-50">
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg shadow-lg transform transition-all duration-300 ease-in-out animate-slide-in" role="alert">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium"><?php echo $_SESSION['error'];
                                                    unset($_SESSION['error']); ?></p>
                </div>
                <button class="ml-auto -mx-1.5 -my-1.5 bg-red-100 text-red-500 rounded-lg focus:ring-2 focus:ring-red-400 p-1.5 hover:bg-red-200 inline-flex h-8 w-8" onclick="this.parentElement.parentElement.remove()">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>
<div class="p-9 w-full">
    <h2 class="text-2xl font-bold mb-4">Course List</h2>
    <div class="flex justify-end mb-4 absolute top-8 right-10">
        <a href="add_course.php"
           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
            <i class="fas fa-plus mr-2"></i>
            Add Course
        </a>
    </div>
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="py-3 px-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th class="py-3 px-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="py-3 px-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Price</th>
                <th class="py-3 px-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Title</th>
                <th class="py-3 px-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Thumbnail</th>
                <th class="py-3 px-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Category</th>
                <th class="py-3 px-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Teacher</th>
                <th class="py-3 px-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Certificate</th>
                <th class="py-3 px-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Total Hours</th>
                <th class="py-3 px-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php
            $sql = "SELECT courses.*, category.name AS categoryName, users.name AS teacherName FROM courses LEFT JOIN category ON courses.categoryId = category.id LEFT JOIN users ON courses.teacherId = users.id ORDER BY courses.id DESC";
            $result = $mysqli->query($sql);

            $teacher = "SELECT * FROM users, courses WHERE users.id = courses.teacherId";
            $teachers = $mysqli-> query($teacher);
        

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    ?>
                    <tr>
                        <td class="py-4 px-4 whitespace-nowrap text-sm text-gray-500 text-center"><?php echo $row['id']; ?></td>
                        <td class="py-4 px-4 whitespace-nowrap text-sm font-medium text-gray-900 text-center"><?php echo $row['name']; ?></td>
                        <td class="py-4 px-4 whitespace-nowrap text-sm text-gray-500 text-center hidden md:table-cell"><?php echo $row['price']; ?>Ks</td>
                        <td class="py-4 px-4 whitespace-nowrap text-sm text-gray-500 text-center hidden md:table-cell"><?= htmlspecialchars(strlen($row['title']) > 10 ? substr($row['title'], 0, 10) . '...' : $row['title']) ?></td>
                        <td class="py-4 px-4 whitespace-nowrap text-sm text-gray-500 text-center hidden md:table-cell"><?= htmlspecialchars(strlen($row['thumbnail']) > 15 ? substr($row['thumbnail'], 0, 15) . '...' : $row['thumbnail']) ?></td>
                        <td class="py-4 px-4 whitespace-nowrap text-sm text-gray-500 text-center hidden sm:table-cell"><?php echo $row['categoryName']; ?></td>
                        <td class="py-4 px-4 whitespace-nowrap text-sm text-gray-500 text-center hidden lg:table-cell"><?php echo $row['teacherName']; ?></td>
                        <td class="py-4 px-4 whitespace-nowrap text-sm text-gray-500 text-center hidden lg:table-cell"><?php echo $row['isCertificate'] ? 'Yes' : 'No'; ?></td>
                        <td class="py-4 px-4 whitespace-nowrap text-sm text-gray-500 text-center hidden md:table-cell"><?php echo $row['totalHours']; ?> hrs</td>
                        <td class="py-4 px-4 whitespace-nowrap text-sm font-medium text-center">
                            <a href="edit_course.php?id=<?php echo $row['id']; ?>" class="text-blue-600 hover:text-blue-900 mx-2"><i class="fas fa-edit"></i></a>
                            <!-- <span class="mx-1">|</span> -->
                            <a href="#" class="text-red-600 hover:text-red-900 open-delete-course-modal mx-2" data-course-id="<?= $row['id'] ?>" title="Delete Course"> <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php
                }
            } else {
                echo '<tr><td colspan="9" class="py-4 px-4 text-center text-sm text-gray-500">No courses found.</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>
<div id="deleteCourseModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
        <h2 class="text-xl font-semibold mb-4">Delete Course</h2>
        <p class="text-gray-700 mb-4">Are you sure you want to delete this course? This action cannot be undone.</p>
        <form action="delete_course.php" method="get">
            <input type="hidden" name="id" id="deleteCourseId">
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeDeleteCourseModal()" class="bg-gray-300 px-4 py-2 rounded">Cancel</button>
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded">Delete</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script>
    $(document).ready(function() {
        $('[role="alert"]').each(function() {
            var $message = $(this);
            setTimeout(function() {
                $message.animate({
                    opacity: 0
                }, 300, function() {
                    $(this).remove();
                });
            }, 5000);
        });
    });


    document.querySelectorAll('.open-delete-course-modal').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const courseId = this.getAttribute('data-course-id');
            document.getElementById('deleteCourseId').value = courseId;
            document.getElementById('deleteCourseModal').classList.remove('hidden');
        });
    });

    // Close Modal Function
    function closeDeleteCourseModal() {
        document.getElementById('deleteCourseModal').classList.add('hidden');
    }
</script>
<?php require './templates/admin_footer.php'; ?>


