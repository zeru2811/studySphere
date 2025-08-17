<?php
session_start();
$type = "Home";
require '../requires/connect.php';
require '../templates/template_header.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header('Location: ../login.php');
    exit();
}

// Get user data
$user_id = $_SESSION['id'];
$stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? $user['name'];
    $email = $_POST['email'] ?? $user['email'];
    $phone = $_POST['phone'] ?? $user['phone'];
    $gender = $_POST['gender'] ?? $user['gender'];
    $description = $_POST['description'] ?? $user['description'];
    
    // Handle profile photo upload
    $profile_photo = $user['profile_photo'];
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/profiles/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid('profile_', true) . '.' . $file_ext;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
            // Delete old profile photo if exists
            if ($profile_photo && file_exists($upload_dir . $profile_photo)) {
                unlink($upload_dir . $profile_photo);
            }
            $profile_photo = $new_filename;
        }
    }
    
    // Update user data
    $stmt = $mysqli->prepare("UPDATE users SET name = ?, email = ?, phone = ?, gender = ?, description = ?, profile_photo = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssssssi", $name, $email, $phone, $gender, $description, $profile_photo, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Profile updated successfully!";
        header("Location: profile.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error updating profile: " . $mysqli->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - StudySphere</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Space Grotesk', sans-serif;
            background-color: #f8f9fa;
        }
        .profile-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
        }
        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #6c63ff;
            margin-right: 2rem;
        }
        .profile-info h2 {
            margin: 0;
            color: #333;
        }
        .profile-info p {
            margin: 0.5rem 0 0;
            color: #666;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
        }
        .btn {
            background-color: #6c63ff;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #5a52d6;
        }
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .role-badge {
            display: inline-block;
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        .admin {
            background-color: #ff0000ff;
            color: white;
        }
        .teacher {
            background-color: indigo;
            color: white;
        }
        .student {
            background-color: #48dbfb;
            color: white;
        }
    </style>
</head>
<body>
    
    <?php require '../templates/template_nav.php'; ?>

    <div class="profile-container">
        <div class="profile-header">
            <img src="../uploads/profiles/<?php echo htmlspecialchars($user['profile_photo'] ?? '../img/image.png'); ?>" 
                 alt="Profile Picture" 
                 class="profile-pic"
                 onerror="this.src='../img/image.png'">
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
                <span class="role-badge <?php 
                    if ($user['role_id'] == 1) echo 'admin';
                    elseif ($user['role_id'] == 2) echo 'teacher';
                    elseif ($user['role_id'] == 3) echo 'student';
                    else echo 'external user';
                ?>">
                    <?php 
                        if ($user['role_id'] == 1) echo 'Admin';
                        elseif ($user['role_id'] == 2) echo 'Teacher';
                        elseif ($user['role_id'] == 3) echo 'Student';
                        else echo 'External User';
                    ?>
                </span>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <form action="profile.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="profile_photo">Profile Photo</label>
                <input type="file" id="profile_photo" name="profile_photo" accept="image/*">
            </div>

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" 
                       value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-control" 
                       value="<?php echo htmlspecialchars($user['phone']); ?>">
            </div>

            <div class="form-group">
                <label>Gender</label>
                <div>
                    <input type="radio" id="male" name="gender" value="male" 
                           <?php echo ($user['gender'] === 'male') ? 'checked' : ''; ?>>
                    <label for="male" style="display: inline; margin-right: 1rem;">Male</label>
                    
                    <input type="radio" id="female" name="gender" value="female" 
                           <?php echo ($user['gender'] === 'female') ? 'checked' : ''; ?>>
                    <label for="female" style="display: inline; margin-right: 1rem;">Female</label>
                    
                    <input type="radio" id="other" name="gender" value="other" 
                           <?php echo ($user['gender'] === 'other') ? 'checked' : ''; ?>>
                    <label for="other" style="display: inline;">Other</label>
                </div>
            </div>

            <div class="form-group">
                <label for="description">About Me</label>
                <textarea id="description" name="description" class="form-control" 
                          rows="4"><?php echo htmlspecialchars($user['description']); ?></textarea>
            </div>

            <button type="submit" class="btn">Update Profile</button>
        </form>
    </div>

    <script>
        // Preview profile photo before upload
        document.getElementById('profile_photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.querySelector('.profile-pic').src = event.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>

    <?php require '../templates/template_backtotop.php'  ?>
    <?php require '../templates/template_footer.php'; ?>
</body>
</html>