<?php 
  // session_start();  // Uncommented this as it's necessary for session functionality
  $title = "Study Sphere";
  require "template_header.php";
  $user_id = $_SESSION['id'];
  $Pstmt = $mysqli->prepare("SELECT profile_photo FROM users WHERE id = ?");
  $Pstmt->bind_param("i", $user_id);
  $Pstmt->execute();
  $Presult = $Pstmt->get_result();
  $userProfilePhoto = $Presult->fetch_assoc();
?>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
  body {
    font-family: 'Space Grotesk', sans-serif;
  }
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
  .profile-section {
    display: flex;
    align-items: center;
  }
  #profile-dropdown {
    display: none;
  }
  #profile-dropdown.show {
    display: block;
  }
</style>
<body class="bg-white">

<!-- Navbar -->
<nav class="border-b">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center py-4">
      <?php if (isset($_SESSION['role_id']) && ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2)): ?>
        <a href="../admin/user_management.php"
           class="absolute top-3 left-4 px-4 py-2 font-semibold rounded-xl shadow hover:bg-purple-600 hover:text-white transition duration-200">
           <i class="fas fa-arrow-left mr-2"></i>Back
        </a>
      <?php endif; ?>
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
        <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] != 4): ?>
          <a href="learning_path.php" class="text-black hover:text-blue-600">Learning Path</a>
        <?php endif; ?>
        <a href="discussion.php" class="text-black hover:text-blue-600">Discussion</a>
        <a href="blogs.php" class="text-black hover:text-blue-600">Blogs</a>
        <a href="about_us.php" class="text-black hover:text-blue-600">About Us</a>
      </div>

      <!-- Profile Section -->
      <?php if (isset($_SESSION['username'])): ?>
      <div class="hidden md:block relative">
        <button id="profile-btn" class="flex items-center space-x-3 focus:outline-none">
          <img src="../uploads/profiles/<?php echo htmlspecialchars($userProfilePhoto['profile_photo'] ?? '../img/image.png'); ?>" alt="Profile Photo" class="w-8 h-8 rounded-full">
          <div class="text-left">
            <p class="font-medium text-sm"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
            <p class="text-xs text-gray-500">
              <?php 
                if (!isset($_SESSION['role_id'])) {
                  header("Location: ../login.php");
                } elseif ($_SESSION['role_id'] == 1) {
                  echo 'Administrator';
                } elseif ($_SESSION['role_id'] == 2) {
                  echo 'Teacher';
                } elseif ($_SESSION['role_id'] == 3) {
                  echo 'Student';
                } else {
                  echo 'External User';
                }
              ?>
            </p>
          </div>
          <i class="fas fa-chevron-down ml-2 text-gray-500 text-xs"></i>
        </button>

        <!-- Profile Dropdown -->
        <div id="profile-dropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
          <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
            <i class="fas fa-user-circle mr-2"></i> My Profile
          </a>
          <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
            <i class="fas fa-cog mr-2"></i> Settings
          </a>
          <?php if (isset($_SESSION['role_id']) && ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2)): ?>
            <a href="../admin/dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
              <i class="fas fa-tachometer-alt mr-2"></i> Admin Dashboard
            </a>
          <?php endif; ?>
          <div class="border-t border-gray-200"></div>
          <a href="../logout.php" class="block px-4 py-2 text-sm text-red-500 hover:bg-gray-100">
            <i class="fas fa-sign-out-alt mr-2"></i> Logout
          </a>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Mobile Dropdown Menu -->
    <div id="mobile-menu" class="dropdown-enter md:hidden flex flex-col space-y-2 text-sm font-medium rounded-lg bg-white shadow-sm border border-gray-200 px-3 py-3">
      <a href="courses.php" class="text-black hover:text-blue-600">Courses</a>
      <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] != 4): ?>
        <a href="learning_path.php" class="text-black hover:text-blue-600">Learning Path</a>
      <?php endif; ?>
      <a href="discussion.php" class="text-black hover:text-blue-600">Discussion</a>
      <a href="blogs.php" class="text-black hover:text-blue-600">Blogs</a>
      <a href="about_us.php" class="text-black hover:text-blue-600">About Us</a>
      
      <?php if (isset($_SESSION['username'])): ?>
      <div class="border-t pt-2 mt-2">
        <a href="profile.php" class="block px-3 py-2 text-sm rounded hover:bg-gray-100">
          <i class="fas fa-user-circle mr-2 text-gray-600"></i> My Profile
        </a>
        <a href="settings.php" class="block px-3 py-2 text-sm rounded hover:bg-gray-100">
          <i class="fas fa-cog mr-2 text-gray-600"></i> Settings
        </a>
        <?php if (isset($_SESSION['role_id']) && ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2)): ?>
          <a href="../admin/dashboard.php" class="block px-3 py-2 text-sm rounded hover:bg-gray-100">
            <i class="fas fa-tachometer-alt mr-2 text-gray-600"></i> Admin Dashboard
          </a>
        <?php endif; ?>
        <a href="../logout.php" class="block px-3 py-2 text-sm rounded hover:bg-gray-100 text-red-500">
          <i class="fas fa-sign-out-alt mr-2"></i> Logout
        </a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</nav>

<script>
  // Mobile menu toggle
  const btn = document.getElementById('menu-btn');
  const menu = document.getElementById('mobile-menu');
  
  btn.addEventListener('click', () => {
    menu.classList.toggle('dropdown-active');
  });

  // Profile dropdown toggle
  const profileBtn = document.getElementById('profile-btn');
  const profileDropdown = document.getElementById('profile-dropdown');

  if (profileBtn && profileDropdown) {
    profileBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      profileDropdown.classList.toggle('show');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
      if (!profileBtn.contains(e.target) && !profileDropdown.contains(e.target)) {
        profileDropdown.classList.remove('show');
      }
    });
  }
</script>