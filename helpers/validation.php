<?php

/**
 * Серверная валидация данных
 */

/**
 * Результат валидации одного поля
 */
function validation_error(string $field, string $message): array
{
    return ['field' => $field, 'message' => $message];
}

/**
 * Валидация обязательного поля
 */
function validate_required(mixed $value, string $field, array &$errors): void
{
    if (empty($value) && $value !== '0') {
        $errors[] = validation_error($field, "Поле «{$field}» обязательно для заполнения.");
    }
}

/**
 * Валидация максимальной длины строки
 */
function validate_max_length(string $value, int $max, string $field, array &$errors): void
{
    if (mb_strlen($value) > $max) {
        $errors[] = validation_error($field, "Поле «{$field}» не должно превышать {$max} символов.");
    }
}

/**
 * Валидация email
 */
function validate_email(string $value, string $field, array &$errors): void
{
    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        $errors[] = validation_error($field, 'Некорректный email-адрес.');
    }
}

/**
 * Валидация URL
 */
function validate_url(string $value, string $field, array &$errors): void
{
    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
        $errors[] = validation_error($field, 'Некорректный URL.');
    }
}

/**
 * Валидация чекбокса (должен быть принят)
 */
function validate_accepted(mixed $value, string $field, array &$errors): void
{
    if (empty($value)) {
        $errors[] = validation_error($field, "Необходимо принять условия.");
    }
}

/**
 * Валидация целого числа
 */
function validate_integer(mixed $value, string $field, array &$errors): void
{
    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
        $errors[] = validation_error($field, "Поле «{$field}» должно быть целым числом.");
    }
}

/**
 * Проверка существования записи в таблице
 */
function validate_exists(int $value, string $table, string $column, \App\Database $db, string $field, array &$errors): void
{
    $count = $db->fetchColumn(
        "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?",
        [$value]
    );

    if ((int) $count === 0) {
        $errors[] = validation_error($field, "Выбранное значение для «{$field}» не существует.");
    }
}

/**
 * Проверка уникальности значения в таблице
 */
function validate_unique(string $value, string $table, string $column, \App\Database $db, string $field, array &$errors, ?int $excludeId = null): void
{
    $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
    $params = [$value];

    if ($excludeId !== null) {
        $sql .= ' AND id != ?';
        $params[] = $excludeId;
    }

    $count = $db->fetchColumn($sql, $params);

    if ((int) $count > 0) {
        $errors[] = validation_error($field, "Такое значение поля «{$field}» уже существует.");
    }
}
