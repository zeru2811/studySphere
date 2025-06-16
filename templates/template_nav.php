<nav class="bg-gray-800 shadow-lg">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16">
      <!-- Logo/Brand -->
      <div class="flex-shrink-0">
        <a href="<?= $base_url ?>index.php" class="text-white font-bold text-xl">
          <?= isset($setting['name']) ? $setting['name'] : 'Study Sphere'; ?>
        </a>
      </div>
      
      <!-- Mobile menu button -->
      <div class="md:hidden">
        <button type="button" class="text-gray-300 hover:text-white focus:outline-none" aria-controls="mobile-menu" aria-expanded="false">
          <span class="sr-only">Open main menu</span>
          <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
      </div>
      
      <!-- Desktop Menu -->
      <div class="hidden md:block">
        <div class="ml-10 flex items-baseline space-x-4">
          <a href="<?= $base_url ?>index.php" class="<?= $current_page == 'home' ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?> px-3 py-2 rounded-md text-sm font-medium">Home</a>
          <a href="<?= $base_url ?>courses.php" class="<?= $current_page == 'courses' ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?> px-3 py-2 rounded-md text-sm font-medium">Courses</a>
          <a href="<?= $base_url ?>learning_path.php" class="<?= $current_page == 'learning_path' ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?> px-3 py-2 rounded-md text-sm font-medium">Learning Path</a>
          <a href="<?= $base_url ?>discussion.php" class="<?= $current_page == 'discussion' ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?> px-3 py-2 rounded-md text-sm font-medium">Discussion</a>
          <a href="<?= $base_url ?>blog.php" class="<?= $current_page == 'blog' ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?> px-3 py-2 rounded-md text-sm font-medium">Blog</a>
          <a href="<?= $base_url ?>about.php" class="<?= $current_page == 'about' ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?> px-3 py-2 rounded-md text-sm font-medium">About Us</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Mobile Menu -->
  <div class="md:hidden hidden" id="mobile-menu">
    <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
      <a href="<?= $base_url ?>index.php" class="<?= $current_page == 'home' ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?> block px-3 py-2 rounded-md text-base font-medium">Home</a>
      <a href="<?= $base_url ?>courses.php" class="<?= $current_page == 'courses' ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?> block px-3 py-2 rounded-md text-base font-medium">Courses</a>
      <a href="<?= $base_url ?>learning_path.php" class="<?= $current_page == 'learning_path' ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?> block px-3 py-2 rounded-md text-base font-medium">Learning Path</a>
      <a href="<?= $base_url ?>discussion.php" class="<?= $current_page == 'discussion' ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?> block px-3 py-2 rounded-md text-base font-medium">Discussion</a>
      <a href="<?= $base_url ?>blog.php" class="<?= $current_page == 'blog' ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?> block px-3 py-2 rounded-md text-base font-medium">Blog</a>
      <a href="<?= $base_url ?>about.php" class="<?= $current_page == 'about' ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?> block px-3 py-2 rounded-md text-base font-medium">About Us</a>
    </div>
  </div>
</nav>