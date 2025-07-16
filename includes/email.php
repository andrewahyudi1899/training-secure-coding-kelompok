<?php
require_once __DIR__ . '/../config/env.php';

class EmailService {
    private $host;
    private $port;
    private $username;
    private $password;
    
    public function __construct() {
        $this->host = MAILTRAP_HOST;
        $this->port = MAILTRAP_PORT;
        $this->username = MAILTRAP_USERNAME;
        $this->password = MAILTRAP_PASSWORD;
    }
    
    /**
     * Send registration verification email
     */
    public function sendRegistrationEmail($to_email, $username, $verification_token) {
        $subject = "Welcome to Job Portal - Please Verify Your Email";
        
        // Create verification URL
        $verification_url = BASE_URL . "/pages/auth/verify.php?token=" . $verification_token;
        
        // HTML email template
        $html_body = $this->getRegistrationEmailTemplate($username, $verification_url);
        
        // Plain text version
        $text_body = $this->getRegistrationEmailText($username, $verification_url);
        
        return $this->sendEmail($to_email, $subject, $html_body, $text_body);
    }
    
    /**
     * Send email using Mailtrap SMTP
     */
    private function sendEmail($to_email, $subject, $html_body, $text_body = null) {
        try {
            // Create boundary for multipart email
            $boundary = md5(time());
            
            // Headers
            $headers = [
                "MIME-Version: 1.0",
                "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
                "From: Job Portal <noreply@jobportal.com>",
                "Reply-To: noreply@jobportal.com",
                "X-Mailer: PHP/" . phpversion(),
                "X-Priority: 3",
                "Return-Path: noreply@jobportal.com"
            ];
            
            // Email body with multipart
            $email_body = "--{$boundary}\r\n";
            
            // Plain text part
            if ($text_body) {
                $email_body .= "Content-Type: text/plain; charset=UTF-8\r\n";
                $email_body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
                $email_body .= $text_body . "\r\n\r\n";
                $email_body .= "--{$boundary}\r\n";
            }
            
            // HTML part
            $email_body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $email_body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $email_body .= $html_body . "\r\n\r\n";
            $email_body .= "--{$boundary}--";
            
            // Use SMTP with Mailtrap
            $result = $this->sendSMTP($to_email, $subject, $email_body, $headers);
            
            if ($result) {
                error_log("Registration email sent successfully to: {$to_email}");
                return true;
            } else {
                error_log("Failed to send registration email to: {$to_email}");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email via SMTP using socket connection
     */
    private function sendSMTP($to_email, $subject, $body, $headers) {
        // Create socket connection
        $socket = fsockopen($this->host, $this->port, $errno, $errstr, 30);
        
        if (!$socket) {
            error_log("SMTP connection failed: {$errno} - {$errstr}");
            return false;
        }
        
        // Read server response
        $response = fgets($socket, 512);
        
        // SMTP commands
        $commands = [
            "EHLO localhost\r\n",
            "AUTH LOGIN\r\n",
            base64_encode($this->username) . "\r\n",
            base64_encode($this->password) . "\r\n",
            "MAIL FROM: <noreply@jobportal.com>\r\n",
            "RCPT TO: <{$to_email}>\r\n",
            "DATA\r\n",
            "Subject: {$subject}\r\n" . implode("\r\n", $headers) . "\r\n\r\n{$body}\r\n.\r\n",
            "QUIT\r\n"
        ];
        
        foreach ($commands as $command) {
            fputs($socket, $command);
            $response = fgets($socket, 512);
            
            // Check for errors (5xx responses)
            if (substr($response, 0, 1) == '5') {
                error_log("SMTP Error: {$response}");
                fclose($socket);
                return false;
            }
        }
        
        fclose($socket);
        return true;
    }
    
    /**
     * HTML email template for registration
     */
    private function getRegistrationEmailTemplate($username, $verification_url) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Welcome to Job Portal</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; background: #f8f9fa; }
                .button { display: inline-block; padding: 12px 30px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to Job Portal!</h1>
                </div>
                <div class='content'>
                    <h2>Hello {$username}!</h2>
                    <p>Thank you for registering with Job Portal. To complete your registration, please verify your email address by clicking the button below:</p>
                    
                    <p style='text-align: center;'>
                        <a href='{$verification_url}' class='button'>Verify Email Address</a>
                    </p>
                    
                    <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
                    <p style='word-break: break-all; background: #e9ecef; padding: 10px; border-radius: 3px;'>{$verification_url}</p>
                    
                    <p>This verification link will expire in 24 hours for security reasons.</p>
                    
                    <p>If you didn't create an account with us, please ignore this email.</p>
                    
                    <p>Best regards,<br>The Job Portal Team</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message, please do not reply to this email.</p>
                    <p>&copy; 2024 Job Portal. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Plain text email template for registration
     */
    private function getRegistrationEmailText($username, $verification_url) {
        return "
Welcome to Job Portal!

Hello {$username}!

Thank you for registering with Job Portal. To complete your registration, please verify your email address by visiting this link:

{$verification_url}

This verification link will expire in 24 hours for security reasons.

If you didn't create an account with us, please ignore this email.

Best regards,
The Job Portal Team

---
This is an automated message, please do not reply to this email.
© 2024 Job Portal. All rights reserved.
        ";
    }
}
?>
