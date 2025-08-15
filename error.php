<?php
header("HTTP/1.0 404 Not Found");
require './requires/common.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --background: #111827;
            --text: #e5e7eb;
            --accent: #8b5cf6;
            --card-bg: rgba(17, 24, 39, 0.8);
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Space Grotesk', sans-serif;
            background-color: var(--background);
            color: var(--text);
            height: 100vh;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .container {
            position: relative;
            width: 100%;
            max-width: 1200px;
            padding: 2rem;
            z-index: 10;
        }
        
        .error-card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }
        
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            margin: 0;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            position: relative;
            line-height: 1;
        }
        
        .error-message {
            font-size: 1.5rem;
            margin: 1.5rem 0 2.5rem;
            line-height: 1.6;
            opacity: 0.9;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 1rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn:hover {
            transform: translateY(-3px);
        }
        
        .btn-primary:hover {
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .three-d-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            overflow: hidden;
        }
        
        .grid-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                linear-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 40px 40px;
        }
        
        @media (max-width: 768px) {
            .error-card {
                padding: 2rem 1.5rem;
            }
            
            .error-code {
                font-size: 5rem;
            }
            
            .error-message {
                font-size: 1.2rem;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .btn {
                width: 100%;
            }
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
          nav{
            z-index: 100;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            background-color: #ffffff20;
          }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-4">
          <?php if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2):?>
            <a href="../admin/user_management.php"
               class="absolute top-3 left-4 px-4 py-2 font-semibold rounded-xl shadow hover:bg-purple-600 hover:text-white transition duration-200">
               <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
          <?php endif;?>
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
            <a href="<?php echo $base_url . 'courses.php'; ?>" class="text-black hover:text-blue-600">Courses</a>
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

    <div class="three-d-container">
        <div class="grid-pattern"></div>
        <canvas id="threeJsCanvas"></canvas>
    </div>
    
    <div class="container">
        <div class="error-card">
            <h1 class="error-code">404</h1>
            <p class="error-message">The page you're looking for doesn't exist or may have been moved. Don't worry, we'll help you find your way.</p>
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="window.location.href='/'">Go to Homepage</button>
                <button class="btn btn-secondary" onclick="window.history.back()">Go Back</button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script>
        // More subtle 3D animation with floating particles
        const canvas = document.getElementById('threeJsCanvas');
        
        // Scene setup
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        const renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setClearColor(0x000000, 0);
        
        // Create particles
        const particleCount = 500;
        const particles = new THREE.BufferGeometry();
        const positions = new Float32Array(particleCount * 3);
        const sizes = new Float32Array(particleCount);
        const colors = new Float32Array(particleCount * 3);
        
        const colorPrimary = new THREE.Color(0x10b981);
        const colorAccent = new THREE.Color(0x8b5cf6);
        
        for (let i = 0; i < particleCount; i++) {
            // Positions
            positions[i * 3] = (Math.random() - 0.5) * 40;
            positions[i * 3 + 1] = (Math.random() - 0.5) * 40;
            positions[i * 3 + 2] = (Math.random() - 0.5) * 40;
            
            // Sizes
            sizes[i] = Math.random() * 0.5 + 0.1;
            
            // Colors - interpolate between primary and accent
            const color = colorPrimary.clone().lerp(colorAccent, Math.random());
            colors[i * 3] = color.r;
            colors[i * 3 + 1] = color.g;
            colors[i * 3 + 2] = color.b;
        }
        
        particles.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        particles.setAttribute('size', new THREE.BufferAttribute(sizes, 1));
        particles.setAttribute('color', new THREE.BufferAttribute(colors, 3));
        
        // Particle material
        const particleMaterial = new THREE.PointsMaterial({
            size: 0.2,
            vertexColors: true,
            transparent: true,
            opacity: 0.8,
            blending: THREE.AdditiveBlending,
            sizeAttenuation: true
        });
        
        const particleSystem = new THREE.Points(particles, particleMaterial);
        scene.add(particleSystem);
        
        // Lighting
        const ambientLight = new THREE.AmbientLight(0x404040);
        scene.add(ambientLight);
        
        const pointLight = new THREE.PointLight(0x8b5cf6, 1, 30);
        pointLight.position.set(5, 5, 5);
        scene.add(pointLight);
        
        camera.position.z = 15;
        
        // Animation
        function animate() {
            requestAnimationFrame(animate);
            
            // Rotate particles
            particleSystem.rotation.x += 0.001;
            particleSystem.rotation.y += 0.002;
            
            renderer.render(scene, camera);
        }
        
        animate();
        
        // Handle window resize
        window.addEventListener('resize', () => {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });
    </script>
</body>
</html>