<?php
/**
 * Шаблон письма о принятии сайта
 *
 * @param string $siteName Название сайта
 * @param int    $siteId   ID сайта
 * @return string HTML письма
 */
function email_site_approved(string $siteName, int $siteId): string
{
    $url = 'https://homecatalog.ru/site/' . $siteId;

    return <<<HTML
    <!DOCTYPE html>
    <html lang="ru">
    <head><meta charset="UTF-8"></head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6;">
        <h2>Ваш сайт "{$siteName}" опубликован!</h2>
        <p>Здравствуйте!</p>
        <p>Ваш сайт <strong>"{$siteName}"</strong> прошёл модерацию и опубликован в каталоге.</p>
        <p>Посмотреть: <a href="{$url}">{$url}</a></p>
        <br>
        <p>С уважением,<br>Каталог сайтов</p>
    </body>
    </html>
    HTML;
}
