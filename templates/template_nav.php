<?php 
  $title = "Study Sphere";
  require "template_header.php";
?>
<style>
  .dropdown-enter {
    max-height: 0;
    opacity: 0;
    transition: max-height 0.4s ease, opacity 0.3s ease;
    overflow: hidden;
  }
  .dropdown-active {
    max-height: 500px;
    opacity: 1;
  }
</style>
<body class="bg-white">

<!-- Navbar -->
<nav class="border-b">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center py-4">
      
      <!-- Logo -->
      <a href="../frontend/index.php" class="flex items-center space-x-2">
        <img src="../img/logo.png" alt="Logo" class="w-8 h-8">
        <span class="font-bold text-xl">StudySphere</span>
      </a>

      <!-- Menu button (Mobile) -->
      <div class="md:hidden">
        <button id="menu-btn" class="text-gray-800 focus:outline-none">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2"
               viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
      </div>

      <!-- Desktop Menu -->
      <div class="hidden md:flex space-x-8 text-sm font-medium">
        <a href="courses.php" class="text-black hover:text-blue-600">Courses</a>
        <?php if ($_SESSION['role_id'] != 4): ?>
          <a href="learning_path.php" class="text-black hover:text-blue-600">Learning Path</a>
        <?php endif; ?>
        <a href="discussion.php" class="text-black hover:text-blue-600">Discussion</a>
        <a href="blogs.php" class="text-black hover:text-blue-600">Blogs</a>
        <a href="about_us.php" class="text-black hover:text-blue-600">About Us</a>
      </div>

      <!-- Search + Profile (Desktop) -->
      <div class="hidden md:flex items-center space-x-4">
        <!-- <div class="relative">
          <input type="text" placeholder="Search..." class="pl-10 pr-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-400" />
          <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                 viewBox="0 0 24 24">
              <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </div>
        </div> -->
        <img src="../img/image.png" alt="Profile" class="w-8 h-8 rounded-full object-cover border p-1 shadow">
      </div>
    </div>

    <!-- Mobile Dropdown Menu -->
    <div id="mobile-menu" class="dropdown-enter md:hidden flex flex-col space-y-2 text-sm font-medium rounded-lg bg-white shadow-sm border border-gray-200">
      <a href="courses.php" class="text-black hover:text-blue-600 hidden">Courses</a>
      <?php if ($_SESSION['role_id'] != 4): ?>
        <a href="learning_path.php" class="text-black hover:text-blue-600">Learning Path</a>
      <?php endif; ?>
      <a href="discussion.php" class="text-black hover:text-blue-600 hidden">Discussion</a>
      <a href="blogs.php" class="text-black hover:text-blue-600 hidden">Blogs</a>
      <a href="about_us.php" class="text-black hover:text-blue-600 hidden">About Us</a>
      <div class="flex items-center space-x-4 pt-2 px-2">
        <!-- <div class="relative w-full">
          <input type="text" placeholder="Search..." class="pl-10 pr-4 py-2 w-full rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-400" />
          <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                 viewBox="0 0 24 24">
              <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </div>
        </div> -->
        <img src="https://i.pravatar.cc/40?img=10" alt="Profile" class="w-8 h-8 rounded-full object-cover">
      </div>
    </div>
  </div>
</nav>


