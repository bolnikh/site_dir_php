# Шаг 16: Отправка email-уведомлений

## Цель

Реализовать отправку email-уведомлений авторам сайтов при одобрении или отклонении.

## Когда отправляются письма

| Событие | Шаблон | Условие |
|---|---|---|
| Сайт одобрен | `site_approved.php` | У сайта указан email |
| Сайт отклонён | `site_rejected.php` | У сайта указан email |

## Шаблоны писем

### `templates/emails/site_approved.php`

```php
<?php
/**
 * Переменные:
 * @var string $siteName   Название сайта
 * @var int    $siteId     ID сайта
 * @var string $siteUrl    URL каталога
 */
?>
Тема: Ваш сайт "<?= h($siteName) ?>" опубликован!

Здравствуйте!

Ваш сайт "<?= h($siteName) ?>" прошёл модерацию и опубликован в каталоге.
Посмотреть: <?= h($siteUrl) ?>/site/<?= $siteId ?>

С уважением,
Каталог сайтов
```

### `templates/emails/site_rejected.php`

```php
<?php
/**
 * Переменные:
 * @var string $siteName     Название сайта
 * @var string $contactEmail  Email для связи
 */
?>
Тема: Ваш сайт "<?= h($siteName) ?>" отклонён

Здравствуйте!

К сожалению, ваш сайт "<?= h($siteName) ?>" не прошёл модерацию.
Если вы считаете это ошибкой, свяжитесь с нами: <?= h($contactEmail) ?>

С уважением,
Каталог сайтов
```

## Класс Mailer (`src/Mailer.php`)

Использует только PHPMailer + SMTP (согласно `technical.md`):

```php
namespace App;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(string $to, string $subject, string $body): bool
    {
        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host       = $this->config['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['username'];
            $mail->Password   = $this->config['password'];
            $mail->Port       = (int)$this->config['port'];
            
            if (($this->config['encryption'] ?? '') === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif (($this->config['encryption'] ?? '') === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
            
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($this->config['from'], $this->config['from_name'] ?? '');
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mailer error: {$mail->ErrorInfo}");
            return false;
        }
    }

    /**
     * Отправить уведомление об одобрении сайта
     */
    public function sendSiteApproved(string $toEmail, string $siteName, int $siteId, string $baseUrl): bool
    {
        $subject = "Ваш сайт \"{$siteName}\" опубликован!";
        
        $body = "Здравствуйте!\n\n"
              . "Ваш сайт \"{$siteName}\" прошёл модерацию и опубликован в каталоге.\n"
              . "Посмотреть: {$baseUrl}/site/{$siteId}\n\n"
              . "С уважением,\n"
              . "Каталог сайтов";
        
        return $this->send($toEmail, $subject, $body);
    }

    /**
     * Отправить уведомление об отклонении сайта
     */
    public function sendSiteRejected(string $toEmail, string $siteName, string $contactEmail): bool
    {
        $subject = "Ваш сайт \"{$siteName}\" отклонён";
        
        $body = "Здравствуйте!\n\n"
              . "К сожалению, ваш сайт \"{$siteName}\" не прошёл модерацию.\n"
              . "Если вы считаете это ошибкой, свяжитесь с нами: {$contactEmail}\n\n"
              . "С уважением,\n"
              . "Каталог сайтов";
        
        return $this->send($toEmail, $subject, $body);
    }
}
```

## Интеграция в модерацию (шаг 13)

В `moderator/moderate.php`, после одобрения:
```php
if ($action === 'approve' && !empty($site['email'])) {
    $mailer->sendSiteApproved(
        $site['email'],
        $site['name'],
        $site['id'],
        $config['url']
    );
}
```

После отклонения:
```php
if ($action === 'reject' && !empty($site['email'])) {
    $mailer->sendSiteRejected(
        $site['email'],
        $site['name'],
        'catalog@homecatalog.ru'
    );
}
```

## Конфигурация (`config/mail.php`)

```php
return [
    'host'       => getenv('SMTP_HOST') ?: 'smtp.example.com',
    'port'       => getenv('SMTP_PORT') ?: 587,
    'username'   => getenv('SMTP_USER') ?: 'noreply@homecatalog.ru',
    'password'   => getenv('SMTP_PASS') ?: '',
    'encryption' => getenv('SMTP_ENCRYPTION') ?: 'tls',
    'from'       => getenv('SMTP_FROM') ?: 'noreply@homecatalog.ru',
    'from_name'  => 'Каталог сайтов',
];
```

## Контрольные точки

- [ ] Mailer создаётся с конфигом из `config/mail.php`
- [ ] Используется PHPMailer + SMTP
- [ ] Письмо об одобрении отправляется при утверждении сайта
- [ ] Письмо об отклонении отправляется при отклонении сайта
- [ ] Если email не указан — письмо не отправляется (нет ошибки)
- [ ] Ошибки отправки логируются, но не ломают работу модерации
- [ ] Кодировка UTF-8 в письмах
- [ ] Тема и тело письма соответствуют шаблонам
