<?php
class Mailer
{
    public static function send($to, $subject, $message, $from = null)
    {
        $from = $from ?: SMTP_FROM;
        $headers = "From: " . $from . "\r\n";
        $headers .= "Reply-To: " . $from . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        // In a real production environment with Composer, use PHPMailer.
        // For now, we use mail() which works if sendmail/postfix is configured on the server
        // or if XAMPP sendmail is configured.

        // AWS SES can be used via SMTP Interface. 
        // Attempting to use a simple SMTP socket connection if PHPMailer isn't present would be complex.
        // Recommended approach for AWS without Composer: Use the local mail relay (Postfix/Sendmail) 
        // configured to relay to SES.

        if (mail($to, $subject, $message, $headers)) {
            return true;
        } else {
            // Log error
            error_log("Failed to send email to $to");
            return false;
        }
    }
}
