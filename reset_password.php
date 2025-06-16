<?php
session_start();
require "requires/connect.php";
require "requires/common_function.php";
require "requires/common.php";

$err_msg = "";
$success_msg = "";
$successVerify = $_SESSION['successVerify'] ?? '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $userId = $_SESSION['verified_user_id'] ?? null;

    if (!$userId) {
        $err_msg = "Session expired. Please verify again.";
    } else {
        $password = trim($_POST['password'] ?? '');
        $confirmPassword = trim($_POST['confirm_password'] ?? '');
        if (strlen($password) < 8) {
            $err_msg = "Password must be at least 8 characters long.";
        } elseif ($password !== $confirmPassword) {
            $err_msg = "Passwords do not match.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
           
            $updateData = [
                'password' => $hashedPassword
            ];

            $where = ['id' => $userId];
            // var_dump($update );
            // exit;
            $update = updateData("users", $mysqli, $updateData, $where);
            // var_dump($update );
            // exit;
            if ($update) {
                unset($_SESSION['verified_user_id']);

                $success_msg = "Password reset successfully. You can now login.";
                $_SESSION['success'] = $success_msg;
                header("Location: login.php");
                exit;
            } else {
                $err_msg = "Failed to reset password. Try again.";
            }
        }
    }

    $_SESSION['reset_error'] = $err_msg;
    header("Location: reset_password.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
            <?php if ($successVerify): ?>
                <div class="mt-4 text-center text-green-600 text-sm absolute top-0 right-5 bg-green-50 border-l-4 border-green-500 p-5" id="successMessage">
                    <?= htmlspecialchars($successVerify) ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['reset_error'])): ?>
                <div class="mt-4 text-center text-red-600 text-sm absolute top-0 right-5 bg-red-50 border-l-4 border-red-500 p-5" id="errorMessage">
                    <?= htmlspecialchars($_SESSION['reset_error']) ?>
                </div>
                <?php unset($_SESSION['reset_error']); ?>
            <?php endif; ?>

            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 py-8 px-8 text-center">
                <div class="flex justify-center mb-4">
                    <div class="h-12 w-12 bg-white rounded-full flex items-center justify-center shadow-md">
                        <i class="fas fa-lock text-blue-600 text-xl"></i>
                    </div>
                </div>
                <h1 class="text-2xl font-bold text-white">Reset Password</h1>
                <p class="text-blue-100 mt-2 opacity-90">Create your new password</p>
            </div>

            <!-- Form -->
            <div class="px-8 py-6">
                <form action="reset_password.php" method="POST" class="space-y-5">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" id="password" name="password" 
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out" 
                                   placeholder="••••••••" required>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i class="fas fa-eye-slash text-gray-400 cursor-pointer toggle-password"></i>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Must be at least 8 characters</p>
                        <span class="password_error text-sm text-red-600 hidden"></span>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out" 
                                   placeholder="••••••••" required>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i class="fas fa-eye-slash text-gray-400 cursor-pointer toggle-password"></i>
                            </div>
                        </div>
                        <span class="confirm_password_error text-sm text-red-600 hidden"></span>
                    </div>

                    <div>
                        <button type="submit" 
                                class="w-full flex justify-center items-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                            Reset Password
                        </button>
                    </div>
                </form>

                <div class="mt-6 text-center text-sm">
                    <p class="text-gray-600">
                        Remember your password? 
                        <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">Sign in</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(icon => {
            icon.addEventListener('click', function() {
                const input = this.parentElement.parentElement.querySelector('input');
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('fa-eye-slash');
                this.classList.toggle('fa-eye');
            });
        });

        // Password validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password.length < 8) {
                e.preventDefault();
                document.querySelector('.password_error').textContent = 'Password must be at least 8 characters';
                document.querySelector('.password_error').classList.remove('hidden');
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                document.querySelector('.confirm_password_error').textContent = 'Passwords do not match';
                document.querySelector('.confirm_password_error').classList.remove('hidden');
            }
        });
    </script>
</body>
</html>