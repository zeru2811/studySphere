
<body class="bg-gray-50">
<!-- Mobile Menu Button -->
<button class="mobile-menu-button fixed top-4 right-4 z-50 p-2 w-10 h-10 rounded-md bg-primary text-white lg:hidden">
    <i class="fas fa-bars"></i>
</button>

<div class="flex min-h-screen">
    <!-- Sidebar -->
    <div class="sidebar bg-white w-64 shadow-lg py-6 flex flex-col lg:relative">
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

            <a href="subject.php" class="block py-3 px-6 <?= $currentPage == 'subject.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                <i class="fas fa-chalkboard-teacher mr-3"></i>Subject
            </a>
            
            <a href="lesson.php" class="block py-3 px-6 <?= $currentPage == 'lesson.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                <i class="fas fa-chalkboard-teacher mr-3"></i>Lesson
            </a>
            
            <a href="course_curriculum.php" class="block py-3 px-6 <?= $currentPage == 'course_curriculum.php' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                <i class="fas fa-link mr-3"></i> Create Connection
            </a>
            <a href="#" class="block py-3 px-6 text-gray-700 hover:bg-gray-100">
                <i class="fas fa-comments mr-3"></i> Discussions
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
            <a href="#" class="block py-3 px-6 text-gray-700 hover:bg-gray-100">
                <i class="fas fa-sign-out-alt mr-3"></i> Logout
            </a>
        </nav>
            
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

