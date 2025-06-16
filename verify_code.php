<?php
session_start();
require "requires/connect.php";
require "requires/common_function.php";
require "requires/common.php";

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['verify_code_error'] ?? '';
unset($_SESSION['success'], $_SESSION['verify_code_error']);

$err_msg = "";

if (isset($_POST['form_sub']) && $_POST['form_sub'] == "1") {
    $code = trim($mysqli->real_escape_string($_POST['code']) ?? '');
    $userId = $_SESSION['pending_user_id'];

    if (!$userId) {
        $err_msg = "Session expired. Please try again.";
    }else {
        $where = "`userId` = '$userId' AND `reset_code` = '$code'";
        $result = selectData("password_token", $mysqli, $where, "*");
        $row = fetchSingle($result);

        if ($row) {
            $_SESSION['successVerify'] = "Verify Code Successfully";
            $_SESSION['verified_user_id'] = $userId;
            header("Location: reset_password.php");
            exit;
        } else {
            $err_msg = "Incorrect or expired code.";
        }
    }

    $_SESSION['verify_code_error'] = $err_msg;
    header("Location: verify_code.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <?php if ($success): ?>
        <div class="mt-4 text-center text-green-600 text-sm absolute top-0 right-5 bg-green-50 border-l-4 border-green-500 p-5" id="successMessage">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="mt-4 text-center text-red-600 text-sm absolute top-0 right-5 bg-red-50 border-l-4 border-red-500 p-5" id="errorMessage">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['verify_code_error'])): ?>
        <div class="mt-4 text-center text-red-600 text-sm absolute top-0 right-5  bg-red-50 border-l-4 border-red-500 p-5" id="errorMessage">
            <?= htmlspecialchars($_SESSION['verify_code_error']) ?>
        </div>
        <?php unset($_SESSION['verify_code_error']); ?>
    <?php endif; ?>
    <div class="w-full max-w-md">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 py-8 px-8 text-center">
                <div class="flex justify-center mb-4">
                    <div class="h-12 w-12 bg-white rounded-full flex items-center justify-center shadow-md">
                        <i class="fas fa-shield-alt text-blue-600 text-xl"></i>
                    </div>
                </div>
                <h1 class="text-2xl font-bold text-white">Verify Code</h1>
                <p class="text-blue-100 mt-2 opacity-90">Enter the 6-digit code sent to your email</p>
            </div>

            <!-- Form -->
            <div class="px-8 py-6">
                <form action="verify_code.php" id="verifyForm" method="POST" class="space-y-5" novalidate>
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Verification Code</label>
                        <div class="flex space-x-2 justify-center">
                            <input type="text" maxlength="1" pattern="[0-9]" 
                                   class="input w-12 h-12 text-center text-xl border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out" required>
                            <input type="text" maxlength="1" pattern="[0-9]" 
                                   class="input w-12 h-12 text-center text-xl border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out" required>
                            <input type="text" maxlength="1" pattern="[0-9]" 
                                   class="input w-12 h-12 text-center text-xl border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out" required>
                            <input type="text" maxlength="1" pattern="[0-9]" 
                                   class="input w-12 h-12 text-center text-xl border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out" required>
                            <input type="text" maxlength="1" pattern="[0-9]" 
                                   class="input w-12 h-12 text-center text-xl border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out" required>
                            <input type="text" maxlength="1" pattern="[0-9]" 
                                   class="input w-12 h-12 text-center text-xl border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out" required>
                        </div>
                        <input type="hidden" name="code" id="fullCode">
                        <span class="code_error text-sm text-red-600 hidden"></span>
                    </div>

                    <!-- <div class="text-center text-sm text-gray-600">
                        Didn't receive code? <a href="#" class="font-medium text-blue-600 hover:text-blue-500">Resend</a>
                    </div> -->

                    <div>
                        <button id="verify_btn" type="button" 
                                class="w-full flex justify-center items-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                            Verify Code
                        </button>
                        <input type="hidden" name="form_sub" value="1">
                    </div>
                </form>
                <form id="resendForm" action="resend_code.php" method="POST" class="text-center mt-4">
                    <input type="hidden" name="resend" value="1">
                    <span class="text-sm font-medium">Didn't receive code? </span>
                    <button type="submit" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                        Resend
                    </button>
                </form>
            </div>
        </div>
    </div>
    <script src="./assets/js/jquery-3.7.1.min.js"></script>
    <script>
        // Auto-focus and move between inputs
        document.querySelectorAll('input[type="text"]').forEach((input, index, inputs) => {
            input.addEventListener('input', (e) => {
                if (e.target.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
                updateHiddenCode();
            });
            
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && e.target.value.length === 0 && index > 0) {
                    inputs[index - 1].focus();
                }
                updateHiddenCode();
            });
        });

        function updateHiddenCode() {
            const codeInputs = document.querySelectorAll('input[type="text"]');
            let fullCode = '';
            codeInputs.forEach(input => {
                fullCode += input.value;
            });
            document.getElementById('fullCode').value = fullCode;
        }

        $(document).ready(function(){
            $('#verify_btn').click(function(){
                let error = false;
                $('.code_error').hide()
                let code = $('#fullCode').val();
                if(code.length !== 6){
                    error = true;
                    $('.code_error').show()
                    $('.code_error').text("Need to fill 6 code")
                }
                if(!error){
                    $('#verifyForm').submit()
                }
                
            })
        })


        document.addEventListener('DOMContentLoaded', function() {
        const errorMessage = document.getElementById('errorMessage');
            setTimeout(() => {
                errorMessage.classList.remove('right-5');
                errorMessage.classList.add('right-[-100%]');
            }, 5000);
        });


    </script>
</body>
</html>