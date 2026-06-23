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
     * Получить значение из кэша.
     * Данные хранятся в виде ['data' => ..., 'ts' => ...] для корректной обработки null.
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
        return $decoded['data'] ?? null;
    }

    /**
     * Сохранить значение в кэш.
     * Оборачивается в ['data' => ..., 'ts' => ...] чтобы null не терялся.
     */
    public function set(string $key, mixed $value, int $ttl = 300): bool
    {
        if (!$this->available) {
            return false;
        }

        $encoded = json_encode([
            'data' => $value,
            'ts' => time(),
        ], JSON_UNESCAPED_UNICODE);

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
     * Удалить ключи по паттерну.
     * Использует SCAN вместо KEYS для production-безопасности.
     */
    public function deletePattern(string $pattern): int
    {
        if (!$this->available) {
            return 0;
        }

        $count = 0;
        $iterator = null;
        $fullPattern = $this->prefix . $pattern;

        while ($keys = $this->redis->scan($iterator, $fullPattern, 100)) {
            if (!empty($keys)) {
                $count += $this->redis->del($keys);
            }
        }

        return $count;
    }

    /**
     * Получить из кэша или вычислить, сохранить и вернуть.
     * Не кэширует null-результаты.
     */
    public function remember(string $key, callable $callback, int $ttl = 300): mixed
    {
        if ($this->available) {
            $cached = $this->get($key);
            if ($cached !== null) {
                return $cached;
            }
        }

        $value = $callback();

        if ($value !== null && $this->available) {
            $this->set($key, $value, $ttl);
        }

        return $value;
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
