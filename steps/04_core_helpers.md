# Шаг 4: Реализация базовых классов и хелперов

## Цель

Создать все вспомогательные классы и функции, которые будут использоваться на страницах.

## Файлы для реализации

### 1. `src/Database.php` — Подключение к БД

```php
namespace App;

use PDO;
use PDOException;

class Database
{
    private PDO $pdo;

    public function __construct(array $config)
    {
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database']
        );

        $this->pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        if (isset($config['charset'])) {
            $this->pdo->exec("SET NAMES '{$config['charset']}'");
        }
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }
}
```

### 2. `src/Cache.php` — Работа с Redis

Используется расширение `php-redis` (установлено в Dockerfile):

```php
namespace App;

class Cache
{
    private ?\Redis $redis = null;
    private string $prefix;
    private bool $available = false;

    public function __construct(array $config)
    {
        $this->prefix = $config['prefix'] ?? 'catalog:';
        
        try {
            $this->redis = new \Redis();
            $this->redis->connect($config['host'], (int)$config['port']);
            $this->available = true;
        } catch (\Exception $e) {
            error_log('Redis connection failed: ' . $e->getMessage());
        }
    }
    
    // Методы: get, set, delete, deletePattern, remember (см. шаг 15)
}
```

Интерфейс:
- `get(string $key): mixed` — получить из кэша
- `set(string $key, mixed $value, int $ttl = 300): bool` — сохранить в кэш
- `delete(string $key): bool` — удалить ключ
- `deletePattern(string $pattern): int` — удалить по шаблону (для инвалидации)
- `remember(string $key, callable $callback, int $ttl): mixed` — получить или вычислить и сохранить

Логика: если Redis недоступен — методы `get`/`remember` возвращают результат колбэка без кэширования (graceful degradation).

### 3. `src/Mailer.php` — Отправка почты

Всегда используется PHPMailer + SMTP (согласно `technical.md`):

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
    
    // Методы sendSiteApproved, sendSiteRejected (см. шаг 16)
}
```

### 4. `helpers/flash.php` — Flash-сообщения

```php
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function flash_get(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}
```

### 5. `helpers/security.php` — XSS-защита

```php
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function clean_html(string $html): string
{
    // Использовать HTMLPurifier для очистки HTML из визуального редактора
    $config = HTMLPurifier_Config::createDefault();
    $purifier = new \HTMLPurifier($config);
    return $purifier->purify($html);
}
```

### 6. `helpers/validation.php` — Серверная валидация

Функция `validate(array $data, array $rules): array`
- Возвращает массив ошибок (пустой = валидация пройдена)
- Поддерживаемые правила:
  - `required` — поле обязательно
  - `string` / `integer` — тип
  - `max:N` — максимальная длина
  - `email` — валидный email
  - `url` — валидный URL
  - `unique:table,column` — проверка уникальности в БД
  - `exists:table,column` — проверка существования в БД
  - `accepted` — чекбокс должен быть отмечен

### 7. `helpers/breadcrumbs.php`

Функция `build_breadcrumbs(PDO $db, array $items): array`
- Принимает массив элементов крошек: `[['label' => 'Начало', 'url' => '/'], ...]`
- Последний элемент — без ссылки (текущая страница)
- Для разделов: автоматически строит цепочку от корня через `path`

### 8. `helpers/pagination.php`

Функция `render_pagination(int $currentPage, int $totalPages, string $baseUrl): string`
- Возвращает HTML постраничной навигации Bootstrap
- Выводит: [1] [2] ... [N]
- Текущая страница выделена

### 9. `helpers/slug.php` (дополнительно)

Функция `generate_slug(string $name): string`
- Транслитерация кириллицы в латиницу
- Замена пробелов и спецсимволов на дефисы
- Приведение к нижнему регистру

## Контрольные точки

- [ ] Класс Database создан и протестирован
- [ ] Класс Cache создан (graceful degradation при отсутствии Redis, драйвер phpredis)
- [ ] Класс Mailer создан (PHPMailer + SMTP)
- [ ] Flash-сообщения работают (сессия)
- [ ] Функция `h()` для экранирования готова
- [ ] Функция `clean_html()` с HTMLPurifier готова
- [ ] Валидатор принимает правила и возвращает ошибки
- [ ] Хлебные крошки формируются корректно для любой глубины
- [ ] Пагинация рендерит HTML с правильными ссылками
- [ ] Генератор slug корректно транслитерирует кириллицу
