<?php

/**
 * Серверная валидация данных
 */

/**
 * Валидация массива данных по набору правил
 *
 * @param array      $data  Данные для проверки (ключ => значение)
 * @param array      $rules Правила (поле => 'rule1|rule2|...')
 * @param \App\Database|null $db Для правил unique/exists
 * @return array Массив ошибок: [['field' => '...', 'message' => '...'], ...]
 */
function validate(array $data, array $rules, ?\App\Database $db = null): array
{
    $errors = [];

    foreach ($rules as $field => $ruleString) {
        $value = $data[$field] ?? null;
        $ruleList = explode('|', $ruleString);

        foreach ($ruleList as $rule) {
            $params = [];

            // Разбор правила с параметрами: unique:table,column
            if (str_contains($rule, ':')) {
                [$rule, $paramStr] = explode(':', $rule, 2);
                $params = explode(',', $paramStr);
            }

            switch ($rule) {
                case 'required':
                    if (empty($value) && $value !== '0' && $value !== 0) {
                        $errors[] = [
                            'field' => $field,
                            'message' => "Поле «{$field}» обязательно для заполнения.",
                        ];
                    }
                    break;

                case 'string':
                    if (!empty($value) && !is_string($value)) {
                        $errors[] = [
                            'field' => $field,
                            'message' => "Поле «{$field}» должно быть строкой.",
                        ];
                    }
                    break;

                case 'integer':
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
                        $errors[] = [
                            'field' => $field,
                            'message' => "Поле «{$field}» должно быть целым числом.",
                        ];
                    }
                    break;

                case 'email':
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = [
                            'field' => $field,
                            'message' => 'Некорректный email-адрес.',
                        ];
                    }
                    break;

                case 'url':
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                        $errors[] = [
                            'field' => $field,
                            'message' => 'Некорректный URL.',
                        ];
                    }
                    break;

                case 'accepted':
                    if (empty($value)) {
                        $errors[] = [
                            'field' => $field,
                            'message' => 'Необходимо принять условия.',
                        ];
                    }
                    break;

                case 'max':
                    $max = (int) ($params[0] ?? 255);
                    if (!empty($value) && is_string($value) && mb_strlen($value) > $max) {
                        $errors[] = [
                            'field' => $field,
                            'message' => "Поле «{$field}» не должно превышать {$max} символов (сейчас " . mb_strlen($value) . ").",
                        ];
                    }
                    break;

                case 'unique':
                    if ($db !== null && !empty($value)) {
                        $table = $params[0] ?? '';
                        $column = $params[1] ?? $field;
                        $excludeField = $params[2] ?? null;
                        $excludeValue = $params[3] ?? null;

                        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
                        $bindings = [$value];

                        if ($excludeField && $excludeValue) {
                            $sql .= " AND {$excludeField} != ?";
                            $bindings[] = $excludeValue;
                        }

                        $count = $db->fetchColumn($sql, $bindings);
                        if ((int) $count > 0) {
                            $errors[] = [
                                'field' => $field,
                                'message' => "Такое значение поля «{$field}» уже существует.",
                            ];
                        }
                    }
                    break;

                case 'exists':
                    if ($db !== null && !empty($value)) {
                        $table = $params[0] ?? '';
                        $column = $params[1] ?? 'id';

                        $count = $db->fetchColumn(
                            "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?",
                            [$value]
                        );
                        if ((int) $count === 0) {
                            $errors[] = [
                                'field' => $field,
                                'message' => "Выбранное значение для «{$field}» не существует.",
                            ];
                        }
                    }
                    break;
            }
        }
    }

    return $errors;
}

/**
 * Удобная функция: первая ошибка для поля
 */
function validation_first_error(array $errors, string $field): ?string
{
    foreach ($errors as $error) {
        if ($error['field'] === $field) {
            return $error['message'];
        }
    }
    return null;
}

/**
 * Есть ли ошибки?
 */
function validation_passed(array $errors): bool
{
    return empty($errors);
}
