
<body class="bg-gray-50">
<!-- Mobile Menu Button -->
<button class="mobile-menu-button fixed top-4 right-4 z-50 p-2 w-10 h-10 rounded-md bg-primary text-white lg:hidden">
    <i class="fas fa-bars"></i>
</button>

<div class="flex min-h-screen">
    <!-- Sidebar -->
    <div class="sidebar bg-white  shadow-lg py-6 flex flex-col lg:relative">
        <div class="px-6 mb-8">
            <h1 class="text-2xl font-bold text-primary flex items-center">
                <i class="fas fa-graduation-cap mr-2"></i>
                StudySphere
            </h1>
            <p class="text-xs text-gray-500 mt-1">Learning & Career Platform</p>
        </div>
            
        <nav class="flex-1 w-full">
            <div class="px-4 mb-4">
                <h3 class="text-xs uppercase text-gray-500 font-semibold tracking-wider">Navigation</h3>
            </div>
            <a href="user_management.php" class="block py-3 px-6 <?= $currentPage == 'user_management.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                <i class="fas fa-users mr-3"></i> User Management
            </a>

            <a href="courses.php" class="block py-3 px-6 <?= $currentPage == 'courses.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                <i class="fas fa-book mr-3"></i> Courses
            </a>

            <a href="category.php" class="block py-3 px-6 <?= $currentPage == 'category.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                <i class="fas fa-tags mr-3"></i> Course Category
            </a>
            <a href="subject.php" class="block py-3 px-6 <?= $currentPage == 'subject.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                <i class="fas fa-chalkboard-teacher mr-3"></i>Subject
            </a>
            
            <a href="lesson.php" class="block py-3 px-6 <?= $currentPage == 'lesson.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                <i class="fas fa-chalkboard-teacher mr-3"></i>Lesson
            </a>

            <a href="comment.php" class="block py-3 px-6 <?= $currentPage == 'comment.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                <i class="fas fa-comment mr-3"></i> Comment
            </a>

            <a href="blog.php" class="block py-3 px-6 <?= $currentPage == 'blog.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                <i class="fas fa-pencil-alt mr-3"></i> Blog
            </a>

             <a href="learning_path.php" class="block py-3 px-6 <?= $currentPage == 'learning_path.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                <i class="fas fa-road mr-3"></i> Learning Path
            </a>
            
            <a href="learning_path_course.php" class="block py-3 px-6 <?= $currentPage == 'learning_path_course.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                <i class="fas fa-link mr-3"></i> Learning Path Course
            </a>
            <a href="course_curriculum.php" class="block py-3 px-6 <?= $currentPage == 'course_curriculum.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                <i class="fas fa-link mr-3"></i> Create Connection
            </a>
            <a href="enroll_list.php" class="block py-3 px-6 <?= $currentPage == 'enroll_list.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                <i class="fas fa-list mr-3"></i> Enroll List
            </a>
            <a href="module.php" class="block py-3 px-6 <?= $currentPage == 'module.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                <i class="fas fa-layer-group mr-3"></i> Module
            </a>
            <a href="answer.php" class="block py-3 px-6 <?= $currentPage == 'answer.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                <i class="fas fa-reply mr-3"></i> Answer
            </a>
            <a href="../frontend/" class="block py-3 px-6">
                <i class="fas fa-link mr-3"></i>Go to Frontend
            </a>

            <a href="#" class="block py-3 px-6 text-gray-700 hover:bg-gray-100">
                <i class="fas fa-cog mr-3"></i> Settings
            </a>
                
            <div class="px-4 mt-8 mb-4">
                <h3 class="text-xs uppercase text-gray-500 font-semibold tracking-wider">Account</h3>
            </div>
            <a href="#" class="block py-3 px-6 text-gray-700 hover:bg-gray-100">
                <i class="fas fa-user mr-3"></i> My Profile
            </a>
            <a href="#" onclick="openModal('logoutModal')" class="block py-3 px-6 text-gray-700 hover:bg-gray-100">
                <i class="fas fa-sign-out-alt mr-3"></i> Logout
            </a>
        </nav>
        <div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-sm">
                <h2 class="text-lg font-semibold mb-4">Confirm Logout</h2>
                <p class="text-gray-600 mb-6">Are you sure you want to log out?</p>
                <div class="flex justify-end gap-2">
                    <button onclick="closeModal('logoutModal')" class="px-4 py-2 border rounded">Cancel</button>
                    <a href="../logout.php" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Logout</a>
                </div>
            </div>
        </div>
        <div class="px-6 mt-auto">
            <div class="bg-light rounded-lg p-4">
                <div class="flex items-center">
                    <div class="bg-secondary rounded-full p-2">
                        <i class="fas fa-headset text-white text-lg"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">Need Help?</p>
                        <p class="text-xs text-gray-600">Contact our support team</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }
    </script>
