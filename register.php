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
    $roleId = 4;
    if (strlen($name) === 0) {
        $error = true;
        $name_error = "Name is required.";
    } else if (strlen($name) < 3) {
        $error = true;
        $name_error = "Name must be less then 3.";
    } else if (strlen($name) >= 100) {
        $error = true;
        $name_error = "Name must be greather then 100.";
    }
    // Email Validation
    if (strlen($email) === 0) {
        $error = true;
        $email_error = "Email is required.";
    } else if (strlen($email) < 3) {
        $error = true;
        $email_error = "Email must be less then 3.";
    } else if (strlen($email) >= 100) {
        $error = true;
        $email_error = "Email must be greather then 100.";
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
        $phone_error = "Phone number must be greater than 3 characters.";
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
        $password_error = "Password must be less then 8.";
    } else if (strlen($password) >= 30) {
        $error = true;
        $password_error = "Password must be greather then 30.";
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
    if (!$error) {
        $sql = "INSERT INTO users (name, email, password, role_id, phone, gender, status) VALUES ('$name', '$email', '$hashed_password', '$roleId', '$phone', '$gender', TRUE)";
        $result  = $mysqli->query($sql);
        if ($result) {
            $url = $cp_base_url . 'login.php?success=Register Success';
            $suc_msg = "Login Successfully";
            echo "<script>
                setTimeout(function() {
                    window.location.href = '$url';
                }, 2000);
            </script>";
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
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md py-5">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
            

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

            <?php if($suc_msg){ ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 fixed w-[300px] top-5 right-[-100%] transition-all duration-500 ease-in-out transform translate-x-0" id="successMessage">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700"><?= $suc_msg ?></p>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ($error_msg) { ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 fixed w-[300px] top-5 right-[-100%] transition-all duration-500 ease-in-out transform" id="errorMessage">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700"><?= $error_msg ?></p>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <!-- Registration Form -->
            <div class="px-8 py-6">
                <form id="registerForm" action="<?php $cp_base_url ?>register.php" method="POST" class="space-y-5" novalidate>
                    <!-- Name Field -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" id="name" name="name"
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
                        <input type="email" id="email" name="email"
                            class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                            placeholder="you@example.com" required>
                        <?php if ($error && isset($email_error)) { ?>
                            <p class="mt-1 text-sm text-red-600"><?= $email_error ?></p>
                        <?php } ?>
                        <span class="email_error text-sm text-red-600 hidden"></span>
                    </div>

                    <!-- Phone Field -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number (Optional)</label>
                        <input type="tel" id="phone" name="phone"
                            class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out"
                            placeholder="+1234567890">
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
                                    <?= (isset($gender) && $gender == 'male') ? 'checked' : '' ?>>
                                <span class="ml-2 text-sm text-gray-700">Male</span>
                            </label>
                            <label class="inline-flex items-center px-3 py-2 bg-gray-50 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-100">
                                <input type="radio" name="gender" value="female" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                    <?= (isset($gender) && $gender == 'female') ? 'checked' : '' ?>>
                                <span class="ml-2 text-sm text-gray-700">Female</span>
                            </label>
                            <label class="inline-flex items-center px-3 py-2 bg-gray-50 rounded-lg border border-gray-200 cursor-pointer hover:bg-gray-100">
                                <input type="radio" name="gender" value="other" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                    <?= (isset($gender) && $gender == 'other') ? 'checked' : '' ?>>
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

                    <!-- Submit Button -->
                    <div>
                        <button id="register_btn" type="button"
                            class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
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
            let error = false;
            $('#register_btn').click(function() {
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

                if (name == '') {
                    error = true;
                    $('.name_error').show()
                    $('.name_error').text('Name is required')
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
                }else if (phone.length <= 3) {
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
                }else if (password != confirm_password) {
                    error = true;
                    $('.confirm_password_error').show()
                    $('.confirm_password_error').text('Password and Confirm Password should be same')
                }
                if (!error) {
                    $('#registerForm').submit()
                }

            })
        })


        document.addEventListener('DOMContentLoaded', function() {
            const successMessage = document.getElementById('successMessage');
            const errorMessage = document.getElementById('errorMessage');
    
            // Trigger the slide-in effect after a small delay
            setTimeout(() => {
                successMessage.classList.remove('right-[-100%]');
                successMessage.classList.add('right-5');
                errorMessage.classList.remove('right-[-100%]');
                errorMessage.classList.add('right-5');
            }, 100);
    
            // Optional: Auto-hide after 5 seconds
            setTimeout(() => {
                successMessage.classList.remove('right-5');
                successMessage.classList.add('right-[-100%]');
                errorMessage.classList.remove('right-5');
                errorMessage.classList.add('right-[-100%]');
            }, 1500);
        });
    </script>
</body>

</html>