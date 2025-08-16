<?php
session_start();
// var_dump(password_hash("Wai180180", PASSWORD_DEFAULT));
// exit();
require "requires/common.php";
require "requires/title.php";
require "requires/connect.php";

$user_name = "";
$remember = 0;
$error = false;
$user_error = '';
$pass_error = '';
$face_error = '';
$err_msg = "";
$suc_msg = "";

$success = $_SESSION['success'] ?? '';

if (isset($_POST['form_sub']) && $_POST['form_sub'] == "1") {
    $user_name = $mysqli->real_escape_string($_POST['username']);
    $password = $mysqli->real_escape_string($_POST['password']);
    $remember = (isset($_POST['remember'])) ? $_POST['remember'] : 0;
    $face_data = isset($_POST['face_data']) ? $mysqli->real_escape_string($_POST['face_data']) : '';
    
    if (strlen($user_name) === 0) {
        $error = true;
        $user_error = "Email is required.";
    }
    if (strlen($password) === 0) {
        $error = true;
        $pass_error = "Password is required.";
    }
    if (empty($face_data)) {
        $error = true;
        $face_error = "Face recognition is required.";
    }
    
    if(!$error){
        $sql = "SELECT * FROM `users` WHERE (name = '$user_name' OR email = '$user_name')";
        $result = $mysqli->query($sql);
        $num_rows = $result->num_rows;
        
        if ($num_rows == 1) {
            while($row = $result->fetch_assoc()){
                $user_id = (int) ($row['id']);
                $db_name = htmlspecialchars($row['name']);
                $db_email = htmlspecialchars($row['email']);
                $db_password = $row['password'];
                $db_status = $row['status'];
                $db_role = $row['role_id'];
                $db_face_data = $row['face_data'];

                // Verify password first
                if(password_verify($password, $db_password)){
                    // Then verify face data if available
                    if (!empty($db_face_data)) {
                        $stored_descriptor = json_decode($db_face_data, true);
                        $input_descriptor = json_decode($face_data, true);
                        
                        if ($stored_descriptor && $input_descriptor) {
                            // Calculate Euclidean distance between descriptors
                            $distance = 0;
                            for ($i = 0; $i < count($stored_descriptor); $i++) {
                                $distance += pow($stored_descriptor[$i] - $input_descriptor[$i], 2);
                            }
                            $distance = sqrt($distance);
                            
                            // Threshold for face matching (adjust as needed)
                            if ($distance > 0.5) {
                                $error = true;
                                $face_error = "Face recognition failed. Please try again.";
                                $user_error = "Face recognition failed. Please try again.";
                                continue;
                            }
                        }
                    }

                    // If we get here, both password and face are valid
                    $_SESSION['id'] = $user_id;
                    $_SESSION['username'] = $db_name;
                    $_SESSION['email'] = $db_email;
                    $_SESSION['role_id'] = $db_role;

                    if ($remember == 1) {
                        setcookie("username", $row['name'], time() + (86400 * 30), "/");
                        setcookie("id", $row['id'], time() + (86400 * 30), "/");
                        setcookie("email", $row['email'], time() + (86400 * 30), "/");
                    }

                    if($db_role == 1 || $db_role == 2){
                        $_SESSION['id'] = $user_id;
                        $url = $admin_base_url . "user_management.php?success=Login Success";
                        $suc_msg = "Login Successfully";
                        header("Refresh: 2; url=$url");
                    }
                    else{
                        $_SESSION['id'] = $user_id;
                        $url = $base_url . "index.php?success=Login Success";
                        $suc_msg = "Login Successfully";
                        header("Refresh: 2; url=$url");
                    }
                } else {
                    $error = true;
                    $pass_error = "Password is incorrect!";
                }
            }
        } else {
            $error = true;
            $pass_error = "User not found!";
        }
    } else {
        $error = true;
        $user_error = "Error!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $login ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <style>
        .video-container {
            position: relative;
            width: 100%;
            height: 250px;
            margin-bottom: 5px;
            border-radius: 0.5rem;
            overflow: hidden;
            background-color: #000;
        }
        #video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
        }
        #canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            transform: scaleX(-1);
        }
        .face-status {
            margin-top: 10px;
            padding: 8px;
            border-radius: 5px;
            text-align: center;
            font-size: 14px;
        }
        .face-success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        .face-error {
            background-color: #f2dede;
            color: #a94442;
        }
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .notification {
            position: relative;
            width: 320px;
            padding: 18px 24px;
            border-radius: 16px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.85);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.18);
            transform: translateX(120%);
            opacity: 0;
            transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            overflow: hidden;
        }

        .notification.show {
            transform: translateX(0);
            opacity: 1;
        }

        .notification.hide {
            transform: translateX(120%);
            opacity: 0;
        }

        .notification-success {
            background: rgba(72, 187, 120, 0.85);
            color: white;
            border-left: 4px solid rgba(255, 255, 255, 0.5);
        }

        .notification-error {
            background: rgba(245, 101, 101, 0.85);
            color: white;
            border-left: 4px solid rgba(255, 255, 255, 0.5);
        }

        .notification-icon {
            position: absolute;
            top: -20px;
            right: -20px;
            font-size: 80px;
            opacity: 0.1;
            transform: rotate(15deg);
        }

        .notification-close {
            position: absolute;
            top: 15px;
            right: 12px;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border-radius: 50%;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.2);
        }

        .notification-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .notification-content {
            position: relative;
            z-index: 2;
        }

        .notification-title {
            font-weight: 600;
            margin-bottom: 4px;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .notification-title i {
            font-size: 18px;
        }

        .notification-message {
            font-size: 14px;
            line-height: 1.4;
            opacity: 0.9;
        }

        .notification-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: rgba(255, 255, 255, 0.3);
            width: 100%;
        }

        .notification-progress-bar {
            height: 100%;
            background: white;
            width: 100%;
            transform-origin: left;
            animation: progress-shrink 4s linear forwards;
        }

        @keyframes progress-shrink {
            from { transform: scaleX(1); }
            to { transform: scaleX(0); }
        }

        /* Floating animation */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }

        .notification:hover {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-xl">
        <div class="bg-white rounded-xl shadow-2xl overflow-hidden border border-gray-200">
            <!-- Notification Container -->
            <div class="notification-container">
                <?php if ($success) : ?>
                    <div class="notification notification-success">
                        <!-- <i class="notification-icon fas fa-check-circle"></i> -->
                        <div class="notification-content">
                            <div class="notification-title">
                                <i class="fas fa-check-circle"></i> Success
                            </div>
                            <div class="notification-message"><?= $success ?></div>
                        </div>
                        <div class="notification-close">
                            <i class="fas fa-times"></i>
                        </div>
                        <div class="notification-progress">
                            <div class="notification-progress-bar"></div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($suc_msg) : ?>
                    <div class="notification notification-success">
                        <i class="notification-icon fas fa-check-circle"></i>
                        <div class="notification-content">
                            <div class="notification-title">
                                <i class="fas fa-check-circle"></i> Success
                            </div>
                            <div class="notification-message"><?= $suc_msg ?></div>
                        </div>
                        <div class="notification-close">
                            <i class="fas fa-times"></i>
                        </div>
                        <div class="notification-progress">
                            <div class="notification-progress-bar"></div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($user_error) : ?>
                    <div class="notification notification-error">
                        <i class="notification-icon fas fa-exclamation-circle"></i>
                        <div class="notification-content">
                            <div class="notification-title">
                                <i class="fas fa-exclamation-circle"></i> Error
                            </div>
                            <div class="notification-message"><?= $user_error ?></div>
                        </div>
                        <div class="notification-close">
                            <i class="fas fa-times"></i>
                        </div>
                        <div class="notification-progress">
                            <div class="notification-progress-bar"></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 py-8 px-8 text-center">
                <div class="flex justify-center mb-4">
                    <div class="h-14 w-14 bg-white rounded-full flex items-center justify-center shadow-md overflow-hidden">
                        <img class="" src="<?php echo $img_url ?>logo.png" alt="Logo">
                    </div>
                </div>
                <h1 class="font-bold text-white" style="letter-spacing: 4px; font-size:40px;"><?php echo $org_name; ?></h1>
                <p class="text-blue-100 opacity-90" style="font-size: 14px;">Expand Your Knowledge Horizon</p>
            </div>

            <!-- Form -->
            <div class="px-8 py-6">
                <form action="<?php $base_url ?>login.php" method="POST" id="loginForm">
                    <div class="space-y-5">
                        <!-- Username/Email Field -->
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Email or Username <sup class="text-red-500">*</sup></label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <input type="text" id="username" name="username" value="<?php echo $user_name; ?>" 
                                       class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out" 
                                       placeholder="you@example.com" required>
                            </div>
                            <?php if ($error && $user_error) { ?>
                                <p class="mt-1 text-sm text-red-600"><?= $user_error ?></p>
                            <?php } ?>
                            <span class="user_error text-sm text-red-600 hidden"></span>
                        </div>

                        <!-- Password Field -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password <sup class="text-red-500">*</sup></label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <input type="password" id="password" name="password" 
                                       class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out" 
                                       placeholder="••••••••" required>
                            </div>
                            <?php if ($error && $pass_error) { ?>
                                <p class="mt-1 text-sm text-red-600"><?= $pass_error ?></p>
                            <?php } ?>
                            <span class="pass_error text-sm text-red-600 hidden"></span>
                        </div>

                        

                    

                        <!-- Face Recognition Section -->
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Face Recognition <sup class="text-red-500">*</sup></label>
                            <div class="video-container">
                                <video id="video" width="600" height="450" autoplay muted playsinline></video>
                                <canvas id="canvas" width="600" height="450"></canvas>
                            </div>
                            <div class="absolute top-9 left-3 flex justify-between">
                                <button type="button" id="captureBtn" class=" mx-auto px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <i class="fas fa-camera"></i>
                                </button>
                                <button type="button" id="retryBtn" class=" mx-auto px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 hidden">
                                    <i class="fas fa-redo"></i>
                                </button>
                            </div>
                            <div id="faceStatus" class="face-status hidden"></div>
                            <?php if ($error && $face_error) { ?>
                                <p class="mt-1 text-sm text-red-600"><?= $face_error ?></p>
                            <?php } ?>
                            <input type="hidden" name="face_data" id="faceData">
                        </div>


                        <!-- Remember Me -->
                        <div class="flex justify-between">
                            <div class="flex items-center">
                                <input type="checkbox" name="remember" value="1" id="remember" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" 
                                       <?= $remember == 1 ? 'checked' : '' ?>>
                                <label for="remember" class="ml-2 block text-sm text-gray-700">Remember me</label>
                            </div>
                            <a href="forget_password.php" class="text-sm text-blue-600 hover:text-blue-500">Forgot password?</a>
                        </div>
                        
                        <!-- Submit Button -->
                        <div>
                            <button id="login_btn" type="button" 
                                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                                Sign in
                            </button>
                            <input type="hidden" name="form_sub" value="1">
                        </div>
                    </div>
                </form>

                <!-- Footer Links -->
                <div class="mt-2 text-center text-sm">
                    <p class="text-gray-400">
                        Don't have an account? 
                        <a href="./register.php" class="font-medium text-blue-400 hover:text-blue-600 hover:underline">Sign up</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="./assets/js/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function(){
            // Configuration
            const MODEL_URL = './models';
            let faceDescriptor = null;
            let videoStream = null;
            let modelsLoaded = false;

            // DOM Elements
            const video = document.getElementById('video');
            const canvas = document.getElementById('canvas');
            const captureBtn = document.getElementById('captureBtn');
            const retryBtn = document.getElementById('retryBtn');
            const faceDataInput = document.getElementById('faceData');
            const faceStatus = document.getElementById('faceStatus');

            function showNotifications() {
                $('.notification').each(function(index) {
                    const notification = $(this);
                    setTimeout(() => {
                        notification.addClass('show');
                        
                        // Auto-hide after 5 seconds
                        setTimeout(() => {
                            hideNotification(notification);
                        }, 5000);
                    }, index * 200);
                });
            }

            function hideNotification(notification) {
                notification.removeClass('show');
                notification.addClass('hide');
                
                // Remove from DOM after animation
                setTimeout(() => {
                    notification.remove();
                }, 500);
            }

             $(document).on('click', '.notification-close', function() {
                const notification = $(this).closest('.notification');
                hideNotification(notification);
            });

            showNotifications();

            // Load face recognition models
            async function loadModels() {
                showFaceStatus('Loading face recognition models...', 'face-success');
                
                try {
                    await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
                    await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                    await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
                    
                    modelsLoaded = true;
                    showFaceStatus('Models loaded successfully!', 'face-success');
                    return true;
                } catch (error) {
                    console.error('Error loading models:', error);
                    showFaceStatus('Failed to load face recognition models', 'face-error');
                    return false;
                }
            }

            // Start camera stream
            async function startVideo() {
                showFaceStatus('Starting camera...', 'face-success');
                
                try {
                    const constraints = {
                        video: {
                            width: { ideal: 600 },
                            height: { ideal: 450 },
                            facingMode: 'user'
                        },
                        audio: false
                    };

                    videoStream = await navigator.mediaDevices.getUserMedia(constraints);
                    video.srcObject = videoStream;
                    showFaceStatus('Camera ready - Please capture your face', 'face-success');
                } catch (error) {
                    console.error('Camera error:', error);
                    let message = 'Camera access denied';
                    if (error.name === 'NotFoundError') {
                        message = 'No camera found';
                    } else if (error.name === 'NotAllowedError') {
                        message = 'Camera permission denied. Please allow access.';
                    }
                    showFaceStatus(message, 'face-error');
                    return false;
                }
                return true;
            }

            // Capture face data
            async function captureFace() {
                if (!modelsLoaded) {
                    showFaceStatus('Models not loaded yet. Please wait...', 'face-error');
                    return;
                }
                
                showFaceStatus('Detecting face...', 'face-success');
                captureBtn.disabled = true;
                
                try {
                    // Detect face with landmarks and descriptor
                    const detection = await faceapi.detectSingleFace(
                        video, 
                        new faceapi.TinyFaceDetectorOptions()
                    ).withFaceLandmarks().withFaceDescriptor();
                    
                    if (detection) {
                        // Draw face landmarks
                        faceapi.matchDimensions(canvas, { width: 600, height: 450 });
                        faceapi.draw.drawDetections(canvas, detection);
                        faceapi.draw.drawFaceLandmarks(canvas, detection);
                        
                        // Store face descriptor
                        faceDescriptor = detection.descriptor;
                        faceDataInput.value = JSON.stringify(Array.from(faceDescriptor));
                        
                        // Show success and enable retry
                        showFaceStatus('Face captured successfully!', 'face-success');
                        captureBtn.classList.add('hidden');
                        retryBtn.classList.remove('hidden');
                    } else {
                        showFaceStatus('No face detected. Please try again.', 'face-error');
                    }
                } catch (error) {
                    console.error('Face detection error:', error);
                    showFaceStatus('Error detecting face: ' + error.message, 'face-error');
                } finally {
                    captureBtn.disabled = false;
                }
            }

            // Retry face capture
            function retryFaceCapture() {
                // Clear canvas
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                
                // Reset face data
                faceDescriptor = null;
                faceDataInput.value = '';
                
                // Show capture button again
                captureBtn.classList.remove('hidden');
                retryBtn.classList.add('hidden');
                
                showFaceStatus('Please capture your face', 'face-success');
            }

            // Show face status message
            function showFaceStatus(message, type) {
                faceStatus.textContent = message;
                faceStatus.className = `face-status ${type}`;
                faceStatus.classList.remove('hidden');
            }

            // Initialize the application
            async function initFaceRecognition() {
                // First load models
                await loadModels();
                
                // Then start camera
                await startVideo();
                
                // Set up event listeners
                captureBtn.addEventListener('click', captureFace);
                retryBtn.addEventListener('click', retryFaceCapture);
                
                // Clean up on page exit
                window.addEventListener('beforeunload', () => {
                    if (videoStream) {
                        videoStream.getTracks().forEach(track => track.stop());
                    }
                });
            }

            // Form validation
            $('#login_btn').click(function(){
                $('.user_error').hide();
                $('.pass_error').hide();
                
                let name = $('#username').val();
                let pass = $('#password').val();
                let error = false;

                if(name == ''){
                    error = true;
                    $('.user_error').show();
                    $('.user_error').text('Username is required.');
                }
                
                if(pass == ''){
                    error = true;
                    $('.pass_error').show();
                    $('.pass_error').text('Password is required.');
                }
                
                if(!faceDataInput.value){
                    error = true;
                    showFaceStatus('Please capture your face before logging in', 'face-error');
                }
                
                if(!error){
                    $('#loginForm').submit();
                }
            });

            // Start face recognition
            initFaceRecognition();

            // Notification animation
            setTimeout(() => {
                const successMessage = document.getElementById('successMessage');
                const sucMsg = document.getElementById('sucMsg');
                const userError = document.getElementById('userError');

                if (successMessage) {
                    successMessage.classList.add('opacity-0');
                    setTimeout(() => successMessage.remove(), 500);
                }

                if (sucMsg) {
                    sucMsg.classList.add('opacity-0');
                    setTimeout(() => sucMsg.remove(), 500);
                }

                if (userError) {
                    userError.classList.add('opacity-0');
                    setTimeout(() => userError.remove(), 500);
                }
                
                //remove session with post
                fetch('unset_session_msg.php', { method: 'POST' });
            }, 3000);
        });
    </script>
</body>
</html>