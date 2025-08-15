<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --background: #0f172a;
            --text: #f8fafc;
            --accent: #f43f5e;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background-color: var(--background);
            color: var(--text);
            height: 100vh;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        body::after{
            content: '';
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 9;
            background-color: rgba(0, 0, 0, 0.3);
            background-size: 100% 100%;
            background-position: 0 0;
            animation: animate 5s linear infinite;
        }
        .container {
            position: relative;
            width: 100%;
            max-width: 1200px;
            padding: 2rem;
            z-index: 10;
            
        }
        
        .error-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            margin: 0;
            background: linear-gradient(45deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            position: relative;
        }
        
        .error-message {
            font-size: 1.5rem;
            margin: 1rem 0 2rem;
            max-width: 600px;
            line-height: 1.6;
        }
        
        .home-button {
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 1rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }
        
        .home-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }
        
        .three-d-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }
        
        @media (max-width: 768px) {
            .error-code {
                font-size: 5rem;
            }
            
            .error-message {
                font-size: 1.2rem;
            }
        }
    </style>
</head>

<body>
    <div class="three-d-container" id="threeJsContainer"></div>
    
    <div class="container">
        <div class="error-content">
            <h1 class="error-code">404</h1>
            <p class="error-message">Oops! The page you're looking for doesn't exist or has been moved. Let's get you back on track.</p>
            <button class="home-button">Return to Homepage</button>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script>
        // 3D Background Animation with Three.js
        const container = document.getElementById('threeJsContainer');
        
        // Scene setup
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setClearColor(0x000000, 0);
        container.appendChild(renderer.domElement);
        
        // Floating 3D shapes
        const geometry = new THREE.IcosahedronGeometry(1, 0);
        const material = new THREE.MeshPhongMaterial({ 
            color: 0x6366f1,
            emissive: 0x4f46e5,
            specular: 0xffffff,
            shininess: 50,
            transparent: true,
            opacity: 0.8
        });
        
        const shapes = [];
        const shapeCount = 8;
        
        for (let i = 0; i < shapeCount; i++) {
            const shape = new THREE.Mesh(geometry, material.clone());
            shape.position.x = (Math.random() - 0.5) * 20;
            shape.position.y = (Math.random() - 0.5) * 20;
            shape.position.z = (Math.random() - 0.5) * 20;
            shape.scale.setScalar(Math.random() * 2 + 1);
            shapes.push(shape);
            scene.add(shape);
        }
        
        // Lighting
        const ambientLight = new THREE.AmbientLight(0x404040);
        scene.add(ambientLight);
        
        const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
        directionalLight.position.set(1, 1, 1);
        scene.add(directionalLight);
        
        const pointLight = new THREE.PointLight(0xf43f5e, 1, 50);
        pointLight.position.set(5, 5, 5);
        scene.add(pointLight);
        
        camera.position.z = 15;
        
        // Animation
        function animate() {
            requestAnimationFrame(animate);
            
            shapes.forEach((shape, i) => {
                shape.rotation.x += 0.005 * (i + 1);
                shape.rotation.y += 0.007 * (i + 1);
                
                // Gentle floating movement
                shape.position.y += Math.sin(Date.now() * 0.001 + i) * 0.01;
                shape.position.x += Math.cos(Date.now() * 0.001 + i) * 0.01;
            });
            
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