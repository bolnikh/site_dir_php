<?php
/**
 * Шаблон письма об отклонении сайта
 *
 * @param string $siteName Название сайта
 * @return string HTML письма
 */
function email_site_rejected(string $siteName): string
{
    return <<<HTML
    <!DOCTYPE html>
    <html lang="ru">
    <head><meta charset="UTF-8"></head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6;">
        <h2>Ваш сайт "{$siteName}" отклонён</h2>
        <p>Здравствуйте!</p>
        <p>К сожалению, ваш сайт <strong>"{$siteName}"</strong> не прошёл модерацию.</p>
        <p>Если вы считаете это ошибкой, свяжитесь с нами: <a href="mailto:catalog@homecatalog.ru">catalog@homecatalog.ru</a></p>
        <br>
        <p>С уважением,<br>Каталог сайтов</p>
    </body>
    </html>
    HTML;
}
