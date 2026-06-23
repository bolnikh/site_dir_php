<?php

namespace App;

use Redis;
use RuntimeException;

/**
 * Класс для работы с Redis через расширение phpredis
 */
class Cache
{
    private ?Redis $redis = null;
    private string $prefix;
    private bool $available = false;

    public function __construct(array $config)
    {
        $this->prefix = $config['prefix'] ?? 'catalog:';

        try {
            $this->redis = new Redis();
            $this->redis->connect($config['host'], (int) ($config['port'] ?? 6379), 1.0);
            $this->available = true;
        } catch (\Exception $e) {
            // Redis недоступен — работаем без кэша (graceful degradation)
            $this->available = false;
        }
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    /**
     * Получить значение из кэша
     */
    public function get(string $key): mixed
    {
        if (!$this->available) {
            return null;
        }

        $value = $this->redis->get($this->prefix . $key);
        if ($value === false) {
            return null;
        }

        $decoded = json_decode($value, true);
        return $decoded !== null ? $decoded : $value;
    }

    /**
     * Сохранить значение в кэш
     */
    public function set(string $key, mixed $value, int $ttl = 300): bool
    {
        if (!$this->available) {
            return false;
        }

        $encoded = is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE);
        return $this->redis->setex($this->prefix . $key, $ttl, $encoded);
    }

    /**
     * Удалить ключ из кэша
     */
    public function delete(string $key): bool
    {
        if (!$this->available) {
            return false;
        }

        return (bool) $this->redis->del($this->prefix . $key);
    }

    /**
     * Удалить ключи по паттерну
     */
    public function deletePattern(string $pattern): int
    {
        if (!$this->available) {
            return 0;
        }

        $keys = $this->redis->keys($this->prefix . $pattern);
        if (empty($keys)) {
            return 0;
        }

        return $this->redis->del($keys);
    }

    /**
     * Проверить существование ключа
     */
    public function has(string $key): bool
    {
        if (!$this->available) {
            return false;
        }

        return (bool) $this->redis->exists($this->prefix . $key);
    }
}
