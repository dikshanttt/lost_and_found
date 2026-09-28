<?php
/** SMTP email delivery through PHPMailer. */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

function send_email(string $recipient, string $name, string $subject, string $htmlBody, string $textBody): bool {
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        error_log('CivicFind mail is not configured: install Composer dependencies first.');
        return false;
    }

    require_once $autoload;
    if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class) || env_value('SMTP_HOST') === '') {
        error_log('CivicFind mail is not configured: PHPMailer or SMTP_HOST is missing.');
        return false;
    }

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = env_value('SMTP_HOST');
        $mail->Port = max(1, (int)env_value('SMTP_PORT', '587'));
        $mail->SMTPAuth = env_value('SMTP_USERNAME') !== '';
        if ($mail->SMTPAuth) {
            $mail->Username = env_value('SMTP_USERNAME');
            $mail->Password = env_value('SMTP_PASSWORD');
        }
        $encryption = strtolower(env_value('SMTP_ENCRYPTION', 'tls'));
        if ($encryption === 'tls') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption === 'ssl') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption !== 'none') {
            throw new \RuntimeException('SMTP_ENCRYPTION must be tls, ssl, or none.');
        }

        $from = env_value('MAIL_FROM_ADDRESS');
        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('MAIL_FROM_ADDRESS must be a valid email address.');
        }
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($from, env_value('MAIL_FROM_NAME', 'CivicFind'));
        $mail->addAddress($recipient, $name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody;
        return $mail->send();
    } catch (\Throwable $exception) {
        error_log('CivicFind email delivery failed: ' . $exception->getMessage());
        return false;
    }
}

function email_user(string $recipient, string $name, string $subject, string $heading, string $message): bool {
    $safeHeading = htmlspecialchars($heading, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    $siteUrl = rtrim(env_value('APP_BASE_URL', 'http://localhost:8000'), '/') . app_url('/');
    $html = '<!doctype html><html><body style="font-family:Arial,sans-serif;color:#172033;line-height:1.6">'
        . '<h1 style="font-size:22px">' . $safeHeading . '</h1><p>' . $safeMessage . '</p>'
        . '<p><a href="' . htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8') . '">Open LostAndFound</a></p>'
        . '</body></html>';
    return send_email($recipient, $name, $subject, $html, $heading . "\n\n" . $message);
}
