<?php
session_start();
require "requires/common.php";
require "requires/common_function.php";
require "requires/title.php";
require "requires/connect.php";
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

define('SMTP_USER', 'waiyan.koko.2811@gmail.com');
define('SMTP_PASS', 'zhsiwkibvivzyrrp');

$email = "";
$error = false;
$email_error = "";
$err_msg = "";
$suc_msg = "";

if (isset($_POST['form_sub']) && $_POST['form_sub'] == "1") {
    $email = $mysqli->real_escape_string($_POST['email']);

    if (strlen($email) === 0) {
        $error = true;
        $email_error = "Email is required.";
    }

    if (!$error) {
        $where = "email = '$email'";
        $result = selectData("users", $mysqli, $where, "id,name");
        

        
        $num_rows = $result->num_rows;
        if ($num_rows == 1) {
            while($row = $result->fetch_assoc()){
                $user_id = (int) ($row['id']);
                $name = $row['name'];

                $resetCode = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
                $insert_data = [
                    'userId' => $user_id,
                    'reset_code' => $resetCode
                ];
                $insertResult = insertData('password_token', $mysqli, $insert_data);
                
                if ($insertResult) {
                    // Send email
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = SMTP_USER;
                        $mail->Password = SMTP_PASS;
                        $mail->SMTPSecure = 'tls';
                        $mail->Port = 587;

                        $mail->setFrom(SMTP_USER, 'StudySphere Support');
                        $mail->addAddress($email);
                        
                        $mail->isHTML(true);
                        $mail->Subject = 'Your Password Reset Code | StudySphere';
                        $mail->Body = '
                        <!DOCTYPE html>
                        <html>
                        <head>
                        </head>
                        <body style="font-family: \'Inter\', Arial, sans-serif; background-color: #f3f4f6; padding: 20px 0;">
                            <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);">
                                <!-- Header with gradient -->
                                <div style="background: linear-gradient(135deg, #6366f1, #8b5cf6); padding: 30px; text-align: center; color: white;">
                                    <h2 style="margin: 0; font-size: 24px; font-weight: 700;">StudySphere Password Reset</h2>
                                </div>
        
                                <!-- Content -->
                                <div style="padding: 30px;">
                                    <p style="margin: 0 0 20px 0; font-size: 16px; color: #4b5563;">Dear '.$name.',</p>
            
                                    <p style="margin: 0 0 20px 0; font-size: 16px; color: #4b5563;">
                                        We received a request to reset your password. Here\'s your one-time verification code:
                                    </p>
            
                                    <!-- Code box -->
                                    <div style="background-color: #f5f3ff; border: 1px solid #8b5cf6; border-radius: 12px; padding: 20px; margin: 25px 0; text-align: center;">
                                        <div style="font-size: 28px; font-weight: 700; letter-spacing: 2px; color: #7c3aed;">'.$resetCode.'</div>
                                    </div>
            
                                    <!-- Note box -->
                                    <div style="background-color: #ecfdf5; border-left: 4px solid #10b981; border-right: 4px solid #10b981; padding: 16px; margin: 25px 0; border-radius: 0 8px 8px 0;">
                                        <p style="margin: 0; font-size: 14px; color: #065f46;">
                                            <strong>Note:</strong> This code will expire in 15 minutes. Please don\'t share it with anyone.
                                        </p>
                                    </div>
            
                                    <p style="margin: 0 0 20px 0; font-size: 16px; color: #4b5563;">
                                        If you didn\'t request this password reset, please secure your account immediately.
                                    </p>
            
                                </div>
        
                                <!-- Footer -->
                                <div style="padding: 20px; background-color: #f9fafb; text-align: center; border-top: 1px solid #e5e7eb;">
                                    <p style="margin: 0; font-size: 14px; color: #6b7280;">
                                        © '.date("Y").' StudySphere. All rights reserved.<br>
                                        <a href="mailto:support@studysphere.com" style="color: #6366f1; text-decoration: none;">support@studysphere.com</a>
                                    </p>
                                </div>
                            </div>
                        </body>
                        </html>
                        ';

                        $mail->send();

                        $_SESSION['pending_user_id'] = $user_id;
                        header("Location: verify_code.php");
                        exit;
                    } catch (Exception $e) {
                        $error = true;
                        $err_msg = "Failed to send email: " . $mail->ErrorInfo;
                    }
                }
                else{
                     $error = true;
                    $email_error = "No account found with that email.";
                }
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Study Sphere</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 py-8 px-8 text-center">
                <div class="flex justify-center mb-4">
                    <div class="h-12 w-12 bg-white rounded-full flex items-center justify-center shadow-md">
                        <i class="fas fa-key text-blue-600 text-xl"></i>
                    </div>
                </div>
                <h1 class="text-2xl font-bold text-white">Forgot Password</h1>
                <p class="text-blue-100 mt-2 opacity-90">Enter your email to reset your password</p>
            </div>

            <!-- Form -->
            <div class="px-8 py-6">
                <form id="forgetForm" action="forget_password.php" method="POST" class="space-y-5">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input type="email" id="email" name="email" 
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-150 ease-in-out" 
                                   placeholder="you@example.com" required>
                        </div>
                        <span class="email_error text-sm text-red-600 hidden"></span>
                    </div>

                    <div>
                        <button id="sent_btn" type="button" 
                                class="w-full flex justify-center items-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                            Send Reset Code
                        </button>
                        <input type="hidden" name="form_sub" value="1">
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
    <script src="./assets/js/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function(){
            $('#sent_btn').click(function(){
                let error = false;
                $('.email_error').hide()
                let email = $('#email').val();
                if(email == ''){
                    error = true;
                    $('.email_error').show()
                    $('.email_error').text('Email is required')
                }
                if(!error){
                    $('#forgetForm').submit()
                }
            })
        })
    </script>
</body>

</html>