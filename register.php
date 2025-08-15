<?php

session_start();
require "requires/common.php";
require "requires/title.php";
require "requires/connect.php";


$error = false;
$error_msg = '';
$suc_msg = '';

$name_error =
    $email_error =
    $password_error =
    $confirm_password_error =
    $phone_error =
    $gender_error =
    $face_error =
    $name =
    $email =
    $phone =
    $gender =
    $password =
    $confirm_password = '';

function uniqueEmail($value, $mysqli)
{
    $sql = "Select count(id) as count from `users` WHERE email = '$value'";
    $result = $mysqli->query($sql);
    $data = $result->fetch_assoc();
    return $data['count'];
}


if (isset($_POST['form_sub']) && $_POST['form_sub'] == '1') {
    $name = $mysqli->real_escape_string($_POST['name']);
    $email = $mysqli->real_escape_string($_POST['email']);
    $phone = $mysqli->real_escape_string($_POST['phone']);
    $gender = $mysqli->real_escape_string($_POST['gender']);
    $password = $mysqli->real_escape_string($_POST['password']);
    $confirm_password = $mysqli->real_escape_string($_POST['confirm_password']);
    $face_data = isset($_POST['face_data']) ? $mysqli->real_escape_string($_POST['face_data']) : '';
    $roleId = 4;
    
    if (strlen($name) === 0) {
        $error = true;
        $name_error = "Name is required.";
    } else if (strlen($name) < 3) {
        $error = true;
        $name_error = "Name must be at least 3 characters.";
    } else if (strlen($name) >= 100) {
        $error = true;
        $name_error = "Name must be less than 100 characters.";
    }
    
    // Email Validation
    if (strlen($email) === 0) {
        $error = true;
        $email_error = "Email is required.";
    } else if (strlen($email) < 3) {
        $error = true;
        $email_error = "Email must be at least 3 characters.";
    } else if (strlen($email) >= 100) {
        $error = true;
        $email_error = "Email must be less than 100 characters.";
    } elseif (uniqueEmail($email, $mysqli) > 0) {
        $error = true;
        $email_error = "This email is already registered.";
    }
    
    // Phone Validation
    if (strlen($phone) === 0) {
        $error = true;
        $phone_error = "Phone is required.";
    } else if (strlen($phone) < 3) {
        $error = true;
        $phone_error = "Phone number must be at least 3 characters.";
    } else if (strlen($phone) >= 30) {
        $error = true;
        $phone_error = "Phone Number must be less than 30 characters.";
    }
    
    // Gender Validation
    if (strlen($gender) === 0) {
        $error = true;
        $gender_error = "Gender is required.";
    }
    
    // Password Validate
    if (strlen($password) === 0) {
        $error = true;
        $password_error = "Password is required.";
    } else if (strlen($password) < 8) {
        $error = true;
        $password_error = "Password must be at least 8 characters.";
    } else if (strlen($password) >= 30) {
        $error = true;
        $password_error = "Password must be less than 30 characters.";
    }
    
    if(strlen($confirm_password) === 0){
        $error = true;
        $confirm_password_error = "Confirm Password is required.";
    }
    // Confirm Password Validation 
    else if ($password !== $confirm_password) {
        $error = true;
        $confirm_password_error = "Password and Confirm Password are not same.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    }
    
    // Face Data Validation
    if (empty($face_data)) {
        $error = true;
        $face_error = "Face recognition is required for registration.";
    }

   
    
    if (!$error) {
        // First check for duplicate face data if provided
        if (!empty($face_data)) {
            $inputDescriptor = json_decode($face_data, true);
        
            if ($inputDescriptor === null) {
                $error = true;
                $face_error = "Invalid face data format.";
            } else {
                // Check for duplicate face_data using Euclidean distance
                $query = "SELECT id, face_data FROM users WHERE face_data IS NOT NULL AND face_data != ''";
                $result = $mysqli->query($query);

                if ($result && $result->num_rows > 0) {
                    $duplicateFound = false;
                
                    while ($row = $result->fetch_assoc()) {
                        $dbDescriptor = json_decode($row['face_data'], true);
                    
                        if (is_array($dbDescriptor) && count($dbDescriptor) === count($inputDescriptor)) {
                            // Calculate Euclidean distance
                            $distance = 0.0;
                            for ($i = 0; $i < count($inputDescriptor); $i++) {
                                $distance += pow($inputDescriptor[$i] - $dbDescriptor[$i], 2);
                            }
                            $distance = sqrt($distance);


                            if ($distance < 0.5) {
                                $duplicateFound = true;
                                break;
                            }
                        }
                    }

                    if ($duplicateFound) {
                        $error = true;
                        $user_error = "Face data already exists!";
                        $face_error = "This face is already registered.";
                    }
                }
            }
        }

    
        
        // Then check for duplicate email if no face error
        if (!$error) {
            $query = "SELECT * FROM users WHERE email = '$email'";
            $result = mysqli_query($mysqli, $query);
        
            if (mysqli_num_rows($result) > 0) {
                $error = true;
                $email_error = "Email already exists.";
            }
        }
        
        
        // Finally, insert if no errors
        if (!$error) {
            $sql = "INSERT INTO users (name, email, password, role_id, phone, gender, status, face_data) 
                    VALUES ('$name', '$email', '$hashed_password', '$roleId', '$phone', '$gender', TRUE, '$face_data')";
         
            $result = $mysqli->query($sql);
            if ($result) {
                $url = 'login.php?success=Register Success';
                $suc_msg = "Register Successfully";
                echo "<script>
                    setTimeout(function() {
                        window.location.href = '$url';
                    }, 2000);
                </script>";
            } else {
                $error = true;
                $error_msg = "Error in Registration: " . $mysqli->error;
            }
        }
    }
    else{
        $error_msg = "Error in Registration";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $register ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <style>
        .video-container {
            position: relative;
            width: 100%;
            height: 300px;
            margin-bottom: 5px;
           
            border-radius: 0.5rem;
            overflow: hidden;
            background-color: #000;
        }
        #video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1); /* Mirror effect */
        }
        #canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            transform: scaleX(-1); /* Mirror effect */
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

        .video-container {
            position: relative;
            width: 100%;
            height: 0;
            padding-bottom: 75%; /* 4:3 aspect ratio */
            margin-bottom: 1rem;
            border-radius: 0.5rem;
            overflow: hidden;
            background-color: #000;
        }
    
        .video-container video,
        .video-container canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
        }
    
        /* Responsive adjustments */
        @media (max-width: 1023px) {
            .video-container {
                padding-bottom: 56.25%; /* 16:9 aspect ratio for smaller screens */
            }
        }
    
        @media (max-width: 767px) {
            .grid-cols-3 {
                grid-template-columns: repeat(2, 1fr);
            }
        
            .video-container {
                padding-bottom: 100%; /* Square aspect ratio for mobile */
            }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center p-2">
    <div class="w-full max-w-3xl pb-5">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
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
            <!-- Form Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 py-5 px-8 text-center">
                <div class="flex justify-center mb-4">
                    <div class="h-16 w-16 bg-white rounded-full flex items-center justify-center shadow-md overflow-hidden">
                        <img class="" src="<?php echo $img_url ?>logo.png" alt="Logo">
                    </div>
                </div>
                <h1 class="text-2xl font-bold text-white tracking-widest">Create Your Account</h1>
                <p class="text-blue-100 text-xs opacity-90">Join our community today</p>
            </div>

            

            <!-- Registration Form -->
            <div class="px-8 py-2">
                <form id="registerForm" action="<?php $cp_base_url ?>register.php" method="POST" class="space-y-5" novalidate>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Left Column - Form Fields -->
                        <div class="space-y-5">
                            <!-- Name Field -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>"
                                    class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                                    placeholder="John Doe" required>
                                <?php if ($error && isset($name_error)) { ?>
                                    <p class="mt-1 text-sm text-red-600"><?= $name_error ?></p>
                                <?php } ?>
                                <span class="name_error text-sm text-red-600 hidden"></span>
                            </div>

                            <!-- Email Field -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>"
                                    class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                                    placeholder="you@example.com" required>
                                <?php if ($error && isset($email_error)) { ?>
                                    <p class="mt-1 text-sm text-red-600"><?= $email_error ?></p>
                                <?php } ?>
                                <span class="email_error text-sm text-red-600 hidden"></span>
                            </div>

                            <!-- Phone Field -->
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($phone) ?>"
                                    class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                                    placeholder="+1234567890" required>
                                <?php if ($error && isset($phone_error)) { ?>
                                    <p class="mt-1 text-sm text-red-600"><?= $phone_error ?></p>
                                <?php } ?>
                                <span class="phone_error text-sm text-red-600 hidden"></span>
                            </div>

                            <!-- Gender Field -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                                <div class="grid grid-cols-3 gap-3">
                                    <label class="inline-flex items-center px-3 py-2 bg-gray-50 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-100">
                                        <input type="radio" name="gender" value="male" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                            <?= (isset($gender) && $gender == 'male') ? 'checked' : '' ?> required>
                                        <span class="ml-2 text-sm text-gray-700">Male</span>
                                    </label>
                                    <label class="inline-flex items-center px-3 py-2 bg-gray-50 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-100">
                                        <input type="radio" name="gender" value="female" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                            <?= (isset($gender) && $gender == 'female') ? 'checked' : '' ?> required>
                                        <span class="ml-2 text-sm text-gray-700">Female</span>
                                    </label>
                                    <label class="inline-flex items-center px-3 py-2 bg-gray-50 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-100">
                                        <input type="radio" name="gender" value="other" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                            <?= (isset($gender) && $gender == 'other') ? 'checked' : '' ?> required>
                                        <span class="ml-2 text-sm text-gray-700">Other</span>
                                    </label>
                                </div>
                                <?php if ($error && isset($gender_error)) { ?>
                                    <p class="mt-1 text-sm text-red-600"><?= $gender_error ?></p>
                                <?php } ?>
                                <span class="gender_error text-sm text-red-600 hidden"></span>
                            </div>

                            <!-- Password Field -->
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                <input type="password" id="password" name="password"
                                    class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                                    placeholder="••••••••" required>
                                <?php if ($error && isset($password_error)) { ?>
                                    <p class="mt-1 text-sm text-red-600"><?= $password_error ?></p>
                                <?php } ?>
                                <span class="password_error text-sm text-red-600 hidden"></span>
                            </div>

                            <!-- Confirm Password Field -->
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password"
                                    class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                                    placeholder="••••••••" required>
                                <?php if ($error && isset($confirm_password_error)) { ?>
                                    <p class="mt-1 text-sm text-red-600"><?= $confirm_password_error ?></p>
                                <?php } ?>
                                <span class="confirm_password_error text-sm text-red-600 hidden"></span>
                            </div>
                        </div>

                        <!-- Right Column - Face Recognition -->
                        <div class="space-y-5">
                            <div class="sticky top-5">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Face Recognition</label>
                                <div class="video-container">
                                    <video id="video" width="600" height="450" autoplay muted playsinline></video>
                                    <canvas id="canvas" width="600" height="450"></canvas>
                                </div>
                                <div class="flex justify-center gap-3 mt-3">
                                    <button type="button" id="captureBtn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <i class="fas fa-camera mr-2"></i>Capture
                                    </button>
                                    <button type="button" id="retryBtn" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 hidden">
                                        <i class="fas fa-redo-alt mr-2"></i>Retry
                                    </button>
                                </div>
                                <div id="faceStatus" class="face-status hidden mt-3"></div>
                                <?php if ($error && isset($face_error)) { ?>
                                    <p class="mt-1 text-sm text-red-600"><?= $face_error ?></p>
                                <?php } ?>
                                <input type="hidden" name="face_data" id="faceData">
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4">
                        <button id="registerBtn" type="button"
                            class="w-full max-w-full px-8 py-3 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                            Create Account
                        </button>
                        <input type="hidden" name="form_sub" value="1">
                    </div>
                </form>

                <!-- Login Link -->
                <div class="mt-2 text-center text-sm">
                    <p class="text-gray-400">
                        Already have an account?
                        <a href="<?php $cp_base_url ?>login.php" class="font-medium text-blue-400 hover:text-blue-600 hover:underline">Sign in</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <script src="./assets/js/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
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
            const registerBtn = document.getElementById('registerBtn');
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
            $('#registerBtn').click(function(e) {
                e.preventDefault();
                
                $('.name_error').hide()
                $('.email_error').hide()
                $('.gender_error').hide()
                $('.password_error').hide()
                $('.confirm_password_error').hide()
                $('.phone_error').hide()
                
                let name = $('#name').val();
                let password = $('#password').val();
                let confirm_password = $('#confirm_password').val();
                let gender = $('input[name="gender"]:checked').val()
                let email = $('#email').val();
                let phone = $('#phone').val();
                let error = false;

                if (name == '') {
                    error = true;
                    $('.name_error').show()
                    $('.name_error').text('Name is required')
                } else if (name.length < 3) {
                    error = true;
                    $('.name_error').show()
                    $('.name_error').text('Name must be at least 3 characters')
                } else if (name.length >= 100) {
                    error = true;
                    $('.name_error').show()
                    $('.name_error').text('Name must be less than 100 characters')
                }
                
                if (email == '') {
                    error = true;
                    $('.email_error').show()
                    $('.email_error').text('Email is required')
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    error = true;
                    $('.email_error').show()
                    $('.email_error').text('Email is invalid!')
                }
                
                if (phone == '') {
                    error = true;
                    $('.phone_error').show()
                    $('.phone_error').text('Phone is required')
                } else if (phone.length <= 3) {
                    error = true;
                    $('.phone_error').show();
                    $('.phone_error').text('Phone number must be more than 3 characters');
                } else if (phone.length >= 30) {
                    error = true;
                    $('.phone_error').show();
                    $('.phone_error').text('Phone number must be less than 30 characters');
                }
                
                if (!gender) {
                    error = true;
                    $('.gender_error').show()
                    $('.gender_error').text('Gender is required')
                }
                
                if (password == '') {
                    error = true;
                    $('.password_error').show()
                    $('.password_error').text('Password is required')
                } else if (password.length < 8) {
                    error = true;
                    $('.password_error').show()
                    $('.password_error').text('Password must be at least 8 characters')
                }
                
                if (confirm_password == '') {
                    error = true;
                    $('.confirm_password_error').show()
                    $('.confirm_password_error').text('Confirm Password is required')
                } else if (password != confirm_password) {
                    error = true;
                    $('.confirm_password_error').show()
                    $('.confirm_password_error').text('Password and Confirm Password should be same')
                }
                
                if (!faceDataInput.value) {
                    error = true;
                    showFaceStatus('Please capture your face before registering', 'face-error');
                }
                
                if (!error) {
                    $('#registerForm').submit();
                }
            });

            // Start face recognition
            initFaceRecognition();

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