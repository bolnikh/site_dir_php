<?php
/**
 * Сброс тестовых данных в исходное состояние.
 * Доступен только при APP_DEBUG=true.
 * GET /moderator/reset-test-data
 */

require_moderator();

if (empty($appConfig['debug'])) {
    http_response_code(403);
    exit('Forbidden: test reset only available in debug mode');
}

// Сбрасываем сайты 12-14 в исходное состояние
$db->execute("
    UPDATE sites SET
        section_id = CASE id
            WHEN 12 THEN 6
            WHEN 13 THEN 9
            WHEN 14 THEN 6
        END,
        name = CASE id
            WHEN 12 THEN 'СайтНаМодерации'
            WHEN 13 THEN 'ЕщёОдинСайт'
            WHEN 14 THEN 'ТретийСайтНаМодерации'
        END,
        description = CASE id
            WHEN 12 THEN 'Описание сайта на модерации. Содержит важную информацию. Ждёт проверки модератором.'
            WHEN 13 THEN 'Ещё одно описание сайта. Тоже ждёт модерацию. Без указания email автора.'
            WHEN 14 THEN 'Третий сайт для тестов модерации. Можно редактировать перед публикацией.'
        END,
        email = CASE id
            WHEN 12 THEN 'author@test.ru'
            WHEN 13 THEN NULL
            WHEN 14 THEN 'third@test.ru'
        END,
        status = 0,
        moderated_at = NULL
    WHERE id IN (12, 13, 14)
");

// Удаляем тестовые сайты, созданные в ADD-* тестах (все status=0 кроме сидов 12-14)
$db->execute("DELETE FROM sites WHERE status = 0 AND id NOT IN (12, 13, 14)");

// Инвалидация кэша сайтов
$cache->delete('sites:recent:10');
$cache->deletePattern('sites:section:*');

// Удаляем тестовые разделы, созданные в SEC-ADD-* тестах
// (только пустые — без сайтов и подразделов)
$db->execute("
    DELETE FROM sections
    WHERE (slug LIKE 'testovyj-razdel-%' OR slug LIKE 'podrazdel-%')
      AND id NOT IN (SELECT DISTINCT section_id FROM sites WHERE section_id IS NOT NULL)
      AND id NOT IN (SELECT DISTINCT parent_id FROM sections WHERE parent_id IS NOT NULL)
");
// Инвалидация кэша после удаления разделов
$cache->delete('sections:tree');
$cache->delete('sections:children:all');
$cache->deletePattern('sections:children:*');

flash_set('success', 'Тестовые данные сброшены (сайты 12-14, тестовые разделы).');
header('Location: /moderator/list');
exit;
