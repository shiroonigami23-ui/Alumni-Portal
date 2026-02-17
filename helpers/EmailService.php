<?php
require_once __DIR__ . '/AWSHelper.php';

class EmailService
{
    private $aws;
    private $fromEmail;
    private $fromName;

    public function __construct()
    {
        $this->aws = new AWSHelper();
        $this->fromEmail = getenv('AWS_SES_FROM_EMAIL') ?: 'noreply@alumni.rjit.ac.in';
        $this->fromName = getenv('AWS_SES_FROM_NAME') ?: 'RJIT Alumni Portal';
    }

    /**
     * Send email using AWS SES or fallback to PHP mail()
     */
    public function send($to, $subject, $body, $isHtml = true)
    {
        // Try AWS SES first if configured
        if (AWSHelper::isConfigured()) {
            $result = $this->aws->sendEmail($to, $subject, $body);
            if ($result) {
                return ['success' => true, 'method' => 'SES', 'message_id' => $result];
            }
        }

        // Fallback to PHP mail()
        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        if ($isHtml) {
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        }

        $success = mail($to, $subject, $body, $headers);

        return [
            'success' => $success,
            'method' => 'PHP_MAIL',
            'message_id' => null
        ];
    }

    /**
     * Send welcome email to new users
     */
    public function sendWelcomeEmail($userEmail, $userName)
    {
        $subject = "Welcome to RJIT Alumni Portal!";
        $body = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>Welcome, {$userName}!</h2>
            <p>Thank you for joining the RJIT Alumni Portal.</p>
            <p>You can now:</p>
            <ul>
                <li>Connect with fellow alumni</li>
                <li>Find mentorship opportunities</li>
                <li>Attend exclusive events</li>
                <li>Share your success stories</li>
            </ul>
            <p>Best regards,<br>RJIT Alumni Team</p>
        </body>
        </html>
        ";

        return $this->send($userEmail, $subject, $body);
    }

    /**
     * Send notification email
     */
    public function sendNotification($to, $title, $message)
    {
        $subject = "RJIT Alumni - " . $title;
        $body = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h3>{$title}</h3>
            <p>{$message}</p>
            <hr>
            <p style='font-size: 12px; color: #666;'>
                This is an automated notification from RJIT Alumni Portal.
            </p>
        </body>
        </html>
        ";

        return $this->send($to, $subject, $body);
    }
}
