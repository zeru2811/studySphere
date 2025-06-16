<?php
session_start();
require "requires/common.php";
require "requires/title.php";
require "requires/connect.php";

$user_name = "";
$remember = 0;
$error = false;
$user_error = '';
$pass_error = '';
$err_msg = "";
$suc_msg = "";

$success = $_SESSION['success'] ?? '';


if (isset($_POST['form_sub']) && $_POST['form_sub'] == "1") {
    $user_name = $mysqli->real_escape_string($_POST['username']);
    $password = $mysqli->real_escape_string($_POST['password']);
    $remember = (isset($_POST['remember'])) ? $_POST['remember'] : 0;
    if (strlen($user_name) === 0) {
        $error = true;
        $user_error = "Email is required.";
    }
    if (strlen($password) === 0) {
        $error = true;
        $pass_error = "Password is required.";
    }
    if(!$error){
        $sql = "SELECT * FROM `users` WHERE name = '$user_name' OR email = '$user_name'";
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

                if(password_verify($password, $db_password)){
                    $_SESSION['id'] = $user_id;
                    $_SESSION['username'] = $db_username;
                    $_SESSION['email'] = $db_email;
                    $_SESSION['role'] = $db_role;

                    if ($remember == 1) {
                        setcookie("username", $row['name'], time() + (86400 * 30), "/");
                        setcookie("id", $row['id'], time() + (86400 * 30), "/");
                        setcookie("email", $row['email'], time() + (86400 * 30), "/");
                    }

                    if($db_role == 1 || $db_role == 2){
                        $url = $admin_base_url . "index.php?success=Login Success";
                        $suc_msg = "Login Successfully";
                        header("Refresh: 0; url=$url");
                    }
                    else{
                        $url = $base_url . "index.php?success=Login Success";
                        $suc_msg = "Login Successfully";
                        header("Refresh: 0; url=$url");
                    }
                }else{
                    $error = true;
                    $pass_error = "Password is incorrect.";
                }
            }
        }else{
            $error = true;
            $password_error = "Password is incorrect.";
        }
    }else{
        $error = true;
        $user_error = "User not found.";
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
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-xl shadow-2xl overflow-hidden border border-gray-200">
            <!-- Success Message -->
            <?php if ($success) { ?>
                <div id="successMessage" class="bg-green-50  border-green-500 p-4 absolute w-[446px] shadow top-16 rounded">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700"><?= $success ?></p>
                        </div>
                    </div>
                </div>
            <?php } ?>
            <?php if ($suc_msg) { ?>
                <div id="sucMsg" class="bg-green-50 border-green-500 p-4 absolute w-[446px] shadow top-16 rounded">
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

            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 py-8 px-8 text-center">
                <div class="flex justify-center mb-4">
                    <!-- Optional: Add your logo here -->
                    <div class="h-14 w-14 bg-white rounded-full flex items-center justify-center shadow-md overflow-hidden">
                        <img class="" src="<?php echo $img_url ?>logo.png" alt="Logo">
                    </div>
                </div>
                <h1 class=" font-bold text-white" style="letter-spacing: 4px; font-size:40px;"><?php echo $org_name; ?></h1>
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
</body>
<!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> -->
<script src="./assets/js/jquery-3.7.1.min.js"></script>
<script>
    $(document).ready(function(){
        let error = false;
        $('#login_btn').click(function(){
            $('.user_error').hide()
            $('.pass_error').hide()
            let name = $('#username').val()
            let pass = $('#password').val()
            if(name == ''){
                error = true;
                $('.user_error').show()
                $('.user_error').text('Username is required.')
            }
            if(pass == ''){
                error = true;
                $('.pass_error').show()
                $('.pass_error').text('Password is required.')
            }
            if(!error){
                $('#loginForm').submit()
            }
        })
    })


    setTimeout(() => {
        const successMessage = document.getElementById('successMessage');
        const sucMsg = document.getElementById('sucMsg');

        if (successMessage) {
            successMessage.classList.add('opacity-0');
            setTimeout(() => successMessage.remove(), 500);
        }

        if (sucMsg) {
            sucMsg.classList.add('opacity-0');
            setTimeout(() => sucMsg.remove(), 500);
        }
        //remove session with post
        fetch('unset_session_msg.php', { method: 'POST' });
    }, 3000);


</script>

</html>