# Шаг 15: Интеграция Redis-кэширования

## Цель

Реализовать кэширование часто запрашиваемых данных через Redis для ускорения работы сайта.

## Стратегия кэширования

Согласно `technical.md`:

| Данные | Ключ | TTL | Инвалидация |
|---|---|---|---|
| Дерево разделов (главная) | `catalog:sections:tree` | 1 час (3600с) | При изменении разделов |
| Последние 10 сайтов | `catalog:sites:recent:10` | 5 минут (300с) | При публикации нового сайта |
| Сайты раздела (стр. N) | `catalog:sites:section:{id}:page:{N}` | 5 минут | При публикации/удалении в разделе |
| Страница сайта | `catalog:site:{slug}` | 30 минут (1800с) | При редактировании/удалении |
| Подразделы раздела | `catalog:sections:children:{parent_id}` | 1 час | При изменении разделов |

## Класс Cache (`src/Cache.php`)

### Подключение

Использовать расширение `php-redis` (установлено в Dockerfile, шаг 1):

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
            // Redis недоступен — работаем без кэша
            error_log('Redis connection failed: ' . $e->getMessage());
        }
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function get(string $key): mixed
    {
        if (!$this->available) return null;
        
        $value = $this->redis->get($this->prefix . $key);
        if ($value === false) return null;
        
        $decoded = json_decode($value, true);
        return $decoded['data'] ?? null;
    }

    public function set(string $key, mixed $data, int $ttl = 300): bool
    {
        if (!$this->available) return false;
        
        $value = json_encode(['data' => $data]);
        return $this->redis->setex($this->prefix . $key, $ttl, $value);
    }

    public function delete(string $key): bool
    {
        if (!$this->available) return false;
        return (bool)$this->redis->del($this->prefix . $key);
    }

    public function deletePattern(string $pattern): int
    {
        if (!$this->available) return 0;
        
        // Используем SCAN вместо KEYS для production-безопасности
        $count = 0;
        $iterator = null;
        while ($keys = $this->redis->scan($iterator, $this->prefix . $pattern, 100)) {
            if (!empty($keys)) {
                $count += $this->redis->del($keys);
            }
        }
        return $count;
    }

    public function remember(string $key, callable $callback, int $ttl = 300): mixed
    {
        $cached = $this->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $data = $callback();
        
        if ($data !== null) {
            $this->set($key, $data, $ttl);
        }

        return $data;
    }
}
```

## Интеграция в страницы

### Главная страница

```php
// Разделы
$sections = $cache->remember('sections:tree', function() use ($db) {
    return get_sections_tree($db);
}, 3600);

// Последние сайты
$recentSites = $cache->remember('sites:recent:10', function() use ($db) {
    return get_recent_sites($db, 10);
}, 300);
```

### Страница раздела

```php
$page = (int)($_GET['page'] ?? 1);
$cacheKey = "sites:section:{$section['id']}:page:{$page}";

$sitesData = $cache->remember($cacheKey, function() use ($db, $section, $page, $perPage) {
    return [
        'sites' => get_sites_by_section($db, $section['id'], $page, $perPage),
        'total' => get_sites_count($db, $section['id']),
    ];
}, 300);
```

### Страница сайта

```php
$cacheKey = "site:{$slug}";

$site = $cache->remember($cacheKey, function() use ($db, $slug) {
    return get_site_by_slug($db, $slug);
}, 1800);
```

## Инвалидация кэша

### При публикации/редактировании сайта

Функция `invalidate_site_cache(Cache $cache, array $site): void`:
```php
$cache->delete('sites:recent:10');
$cache->deletePattern("sites:section:{$site['section_id']}:page:*");
$cache->delete("site:{$site['slug']}");
// Если slug изменился — удалить и старый
if ($oldSlug && $oldSlug !== $site['slug']) {
    $cache->delete("site:{$oldSlug}");
}
```

### При изменении разделов

Функция `invalidate_section_cache(Cache $cache): void`:
```php
$cache->delete('sections:tree');
$cache->deletePattern('sections:children:*');
```

## Контрольные точки

- [ ] Redis-подключение устанавливается
- [ ] При недоступности Redis сайт работает без ошибок (graceful degradation)
- [ ] Дерево разделов на главной кэшируется
- [ ] Последние 10 сайтов кэшируются
- [ ] Список сайтов раздела кэшируется (каждая страница отдельно)
- [ ] Страница сайта кэшируется
- [ ] Подразделы кэшируются
- [ ] При публикации сайта кэш инвалидируется
- [ ] При редактировании разделов кэш инвалидируется
- [ ] TTL соблюдаются
- [ ] Префикс `catalog:` изолирует ключи от других приложений
