<?php
session_start();
require "../requires/common.php";
require "../requires/title.php";
require "../requires/connect.php";
$error = '';
$currentPage = basename($_SERVER['PHP_SELF']);
$pagetitle = "User Profile";

// Redirect non-authorized users
if(!isset($_SESSION['role_id']) || ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2)){
    header("Location: ../login.php");
    exit;
}

// Fetch user data
$user_id = $_SESSION['id'];
$stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ? AND (role_id = 1 OR role_id = 2)");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found");
}

// Determine role name
$role_name = ($user['role_id'] == 1) ? "Administrator" : "Teacher";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    // Initialize variables
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $gender = $_POST['gender'];
    $note = $_POST['note'];
    $description = $_POST['description'];
    $profile_photo = $user['profile_photo']; // Keep existing photo by default

    // Handle file upload if a new photo was provided
    if (!empty($_FILES['profile_photo']['name'])) {
        $target_dir = "../uploads/profiles/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            if (!mkdir($target_dir, 0755, true)) {
                $error = "Failed to create upload directory";
            }
        }
        
        if (is_dir($target_dir) && is_writable($target_dir)) {
            if ($_FILES['profile_photo']['error'] == UPLOAD_ERR_OK) {
                $check = getimagesize($_FILES['profile_photo']['tmp_name']);
                if ($check !== false) {
                    $file_ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($file_ext, $allowed_extensions)) {
                        $new_filename = "user_" . $user_id . "_" . time() . "." . $file_ext;
                        $target_file = $target_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $target_file)) {
                            // Delete old photo if it exists
                            if (!empty($user['profile_photo']) && file_exists($target_dir . $user['profile_photo'])) {
                                unlink($target_dir . $user['profile_photo']);
                            }
                            $profile_photo = $new_filename;
                        } else {
                            $error = "Sorry, there was an error uploading your file.";
                        }
                    } else {
                        $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
                    }
                } else {
                    $error = "File is not an image.";
                }
            } else {
                $error = "Error uploading file: " . $_FILES['profile_photo']['error'];
            }
        } else {
            $error = "Upload directory is not writable or doesn't exist";
        }
    }

    // Only proceed with database update if no errors occurred
    if (empty($error)) {
        $update_stmt = $mysqli->prepare("UPDATE users SET name=?, email=?, phone=?, gender=?, note=?, description=?, profile_photo=?, updated_at=NOW() WHERE id=?");
        $update_stmt->bind_param("sssssssi", $name, $email, $phone, $gender, $note, $description, $profile_photo, $user_id);

        if ($update_stmt->execute()) {
            $success = "Profile updated successfully!";
            // Refresh user data
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            $error = "Error updating profile: " . $mysqli->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pagetitle; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-indigo: #4f46e5;
            --indigo-600: #4f46e5;
            --indigo-700: #4338ca;
            --indigo-100: #e0e7ff;
            --indigo-50: #eef2ff;
            --gray-900: #111827;
            --gray-800: #1f2937;
            --gray-200: #e5e7eb;
            --gray-50: #f9fafb;
            --white: #ffffff;
            --red-500: #ef4444;
            --green-500: #10b981;
            --shadow-sm: 0 1px 2px 0 rgba(0,0,0,0.05);
            --shadow: 0 1px 3px 0 rgba(0,0,0,0.1), 0 1px 2px -1px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
            --rounded-lg: 0.5rem;
            --rounded-xl: 0.75rem;
            --rounded-2xl: 1rem;
            --rounded-3xl: 1.5rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--gray-50);
            color: var(--gray-900);
        }

        .admin-container {
            margin-left: 260px;
            padding: 2rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .profile-header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--gray-900);
            background: linear-gradient(to right, var(--indigo-600), var(--indigo-700));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 4px rgba(79, 70, 229, 0.1);
        }

        .profile-card {
            background: var(--white);
            border-radius: var(--rounded-2xl);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            border: 1px solid rgba(79, 70, 229, 0.1);
            transform-style: preserve-3d;
            perspective: 1000px;
            transition: all 0.3s ease;
        }

        .profile-card:hover {
            box-shadow: 0 25px 50px -12px rgba(79, 70, 229, 0.25);
            transform: translateY(-2px);
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 0;
        }

        .profile-sidebar {
            background: linear-gradient(135deg, var(--indigo-600), var(--indigo-700));
            padding: 2rem;
            color: var(--white);
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .profile-sidebar::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: rotate(30deg);
        }

        .profile-photo-container {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid rgba(255,255,255,0.2);
            margin-bottom: 1.5rem;
            position: relative;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            z-index: 1;
        }

        .profile-photo-container:hover {
            transform: scale(1.05);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3);
        }

        .profile-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .photo-upload {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            text-align: center;
            padding: 0.5rem;
            font-size: 0.75rem;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.2s ease;
            z-index: 2;
        }

        .profile-photo-container:hover .photo-upload {
            opacity: 1;
        }

        .user-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            z-index: 1;
        }

        .user-role {
            background: rgba(255,255,255,0.15);
            padding: 0.25rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            backdrop-filter: blur(5px);
            z-index: 1;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .user-stats {
            width: 100%;
            margin-top: auto;
            z-index: 1;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            font-size: 0.875rem;
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .profile-content {
            padding: 2rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            position: relative;
        }

        .section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(to right, var(--indigo-100), transparent);
            margin-left: 1rem;
        }

        .section-title i {
            color: var(--indigo-600);
            background: var(--indigo-50);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.1);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1rem;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray-800);
        }

        input, select, textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-200);
            border-radius: var(--rounded-lg);
            font-size: 0.875rem;
            transition: all 0.2s ease;
            background-color: var(--white);
            box-shadow: var(--shadow-sm);
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--indigo-300);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            transform: translateY(-1px);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: var(--rounded-lg);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
            box-shadow: var(--shadow);
        }

        .btn-primary {
            background-color: var(--indigo-600);
            color: var(--white);
            background-image: linear-gradient(to right, var(--indigo-600), var(--indigo-700));
        }

        .btn-primary:hover {
            background-color: var(--indigo-700);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-block {
            display: block;
            width: 100%;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--rounded-lg);
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .alert::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: currentColor;
        }

        .alert-success {
            background-color: #ecfdf5;
            color: #065f46;
        }

        .alert-danger {
            background-color: #fef2f2;
            color: #b91c1c;
        }

        .hidden {
            display: none;
        }

        .update-message {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--indigo-600);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: var(--rounded-xl);
            box-shadow: var(--shadow-xl);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            z-index: 1000;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .update-message.show {
            transform: translateY(0);
            opacity: 1;
        }

        @media (max-width: 1024px) {
            .admin-container {
                margin-left: 0;
                padding-left: 1rem;
                padding-right: 1rem;
            }
        }

        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        /* input{
            padding: 0.75rem 1rem;
        } */
    </style>
</head>
<body>
    <?php require './templates/admin_header.php'; ?>
    <?php require './templates/admin_sidebar.php'; ?>

    <div class="admin-container max-w-6xl w-full mx-auto p-6">
        <div class="profile-header">
            <h1><?php echo $role_name; ?> Profile</h1>
        </div>

        <?php if(isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if(!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="profile-card">
            <div class="profile-grid">
                <div class="profile-sidebar">
                    <div class="profile-photo-container" id="photoContainer">
                        <?php if(!empty($user['profile_photo'])): ?>
                            <img src="../uploads/profiles/<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Profile Photo" class="profile-photo" id="profileImage">
                        <?php else: ?>
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=4f46e5&color=fff" alt="Profile Photo" class="profile-photo" id="profileImage">
                        <?php endif; ?>
                        <div class="photo-upload" id="uploadTrigger">
                            <i class="fas fa-camera"></i> Change Photo
                        </div>
                    </div>

                    <h2 class="user-name"><?php echo htmlspecialchars($user['name']); ?></h2>
                    <div class="user-role"><?php echo $role_name; ?></div>

                    <div class="user-stats">
                        <div class="stat-item">
                            <span><i class="fas fa-envelope"></i> Email</span>
                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="stat-item">
                            <span><i class="fas fa-phone"></i> Phone</span>
                            <span><?php echo htmlspecialchars($user['phone'] ?? 'Not set'); ?></span>
                        </div>
                        <div class="stat-item">
                            <span><i class="fas fa-user-tag"></i> Gender</span>
                            <span><?php echo htmlspecialchars($user['gender'] ?? 'Not set'); ?></span>
                        </div>
                        <div class="stat-item">
                            <span><i class="fas fa-id-card"></i> Unique ID</span>
                            <span><?php echo htmlspecialchars($user['uniqueId']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="profile-content">
                    <form action="" method="post" enctype="multipart/form-data" id="profileForm">
                        <input type="file" id="profile_photo" name="profile_photo" accept="image/*" class="hidden">
                        
                        <div class="form-section">
                            <h3 class="section-title"><i class="fas fa-user-edit"></i> Personal Information</h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="name">Full Name</label>
                                    <input type="text" id="name" name="name" class="p-2" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" id="email" name="email" class="p-2" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" id="phone" name="phone" class="p-2" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="gender">Gender</label>
                                    <select id="gender" name="gender" class="p-2">
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo ($user['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo ($user['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo ($user['gender'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title"><i class="fas fa-sticky-note"></i> Notes</h3>
                            <div class="form-group">
                                <textarea id="note" class="p-2" name="note" placeholder="Add any quick notes here..."><?php echo htmlspecialchars($user['note'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-section">
                            <h3 class="section-title"><i class="fas fa-info-circle"></i> Description</h3>
                            <div class="form-group">
                                <textarea id="description" class="p-2" name="description" placeholder="Tell something about yourself..."><?php echo htmlspecialchars($user['description'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <button type="submit" name="update_profile" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="update-message" id="updateMessage">
        <i class="fas fa-info-circle"></i>
        <span>Don't forget to update your profile to save changes!</span>
    </div>

    <script>
        // Photo upload trigger and preview
        const photoInput = document.getElementById('profile_photo');
        const uploadTrigger = document.getElementById('uploadTrigger');
        const profileImage = document.getElementById('profileImage');
        const photoContainer = document.getElementById('photoContainer');
        const updateMessage = document.getElementById('updateMessage');
        const form = document.getElementById('profileForm');
        
        uploadTrigger.addEventListener('click', function() {
            photoInput.click();
        });

        photoInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                const reader = new FileReader();
                
                reader.onload = function(event) {
                    profileImage.src = event.target.result;
                    showUpdateMessage();
                }
                
                reader.readAsDataURL(file);
            }
        });

        function showUpdateMessage() {
            updateMessage.classList.add('show');
            
            setTimeout(() => {
                updateMessage.classList.remove('show');
            }, 5000);
        }

        // Show message when any form field changes
        const formFields = form.querySelectorAll('input, select, textarea');
        formFields.forEach(field => {
            field.addEventListener('change', showUpdateMessage);
        });

        // Auto-dismiss alerts
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });

        // // Add hover effect to profile card
        // const profileCard = document.querySelector('.profile-card');
        // profileCard.addEventListener('mousemove', (e) => {
        //     const x = e.clientX - profileCard.getBoundingClientRect().left;
        //     const y = e.clientY - profileCard.getBoundingClientRect().top;
            
        //     const centerX = profileCard.offsetWidth / 2;
        //     const centerY = profileCard.offsetHeight / 2;
            
        //     const angleX = (y - centerY) / 20;
        //     const angleY = (centerX - x) / 20;
            
        //     profileCard.style.transform = `perspective(1000px) rotateX(${angleX}deg) rotateY(${angleY}deg)`;
        // });

        // profileCard.addEventListener('mouseleave', () => {
        //     profileCard.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
        // });
    </script>
</body>
</html>