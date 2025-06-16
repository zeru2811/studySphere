
<?php
session_start();
require "requires/connect.php";
require "requires/common_function.php";
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

define('SMTP_USER', 'waiyan.koko.2811@gmail.com');
define('SMTP_PASS', 'zhsiwkibvivzyrrp');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend'])) {

    if (!isset($_SESSION['pending_user_id'])) {
        $_SESSION['error'] = "Session expired. Please try again.";
        header("Location: forget_password.php");
        exit;
    }

    $user_id = $_SESSION['pending_user_id'];

    $where = "id = '$user_id'";
    $result = selectData("users", $mysqli, $where, "email,name");

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $email = $row['email'];
        $name = $row['name'];

        $resetCode = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);

        $insert_data = [
            'userId' => $user_id,
            'reset_code' => $resetCode
        ];

        if (insertData("password_token", $mysqli, $insert_data)) {
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
                $mail->Subject = 'Resend Code | StudySphere';
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

                $_SESSION['success'] = "A new reset code has been sent to your email.";
                header("Location: verify_code.php");
                exit;
            } catch (Exception $e) {
                $_SESSION['error'] = "Failed to send email: " . $mail->ErrorInfo;
                header("Location: verify_code.php");
                exit;
            }
        } else {
            $_SESSION['error'] = "Failed to store reset code.";
            header("Location: verify_code.php");
            exit;
        }

    } else {
        $_SESSION['error'] = "User not found.";
        header("Location: verify_code.php");
        exit;
    }
} else {
    header("Location: forget_password.php");
    exit;
}
