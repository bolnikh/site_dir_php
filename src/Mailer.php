<?php

namespace App;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Класс для отправки почты через SMTP (PHPMailer)
 */
class Mailer
{
    private array $config;
    private ?PHPMailer $mailer = null;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Создать и настроить экземпляр PHPMailer
     */
    private function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = $this->config['host'];
        $mail->Port = (int) $this->config['port'];
        $mail->SMTPAuth = !empty($this->config['username']);
        $mail->Username = $this->config['username'];
        $mail->Password = $this->config['password'];

        if ($this->config['encryption'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($this->config['encryption'] === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->CharSet = 'UTF-8';
        $mail->setFrom($this->config['from'], $this->config['from_name']);

        return $mail;
    }

    /**
     * Отправить письмо
     */
    public function send(string $to, string $subject, string $body): bool
    {
        try {
            $mail = $this->createMailer();
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Mailer error: ' . $e->getMessage());
            return false;
        }
    }
}
