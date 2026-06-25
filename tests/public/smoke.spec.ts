/**
 * Дымовые тесты — все публичные страницы открываются без ошибок.
 * Не требуют специфичных тестовых данных кроме минимального наполнения БД.
 */
import { test, expect } from '@playwright/test';
import { assertNoPhpErrors } from '../fixtures/test-helpers';

// ──────────────────────────────────────────────
// Страницы, которые должны открываться (200 OK)
// ──────────────────────────────────────────────

const PUBLIC_OK_PAGES = [
  { name: 'Главная', url: '/' },
  { name: 'Раздел (Дом и интерьер)', url: '/section/dom-i-interer' },
  { name: 'Подраздел (Мебель)', url: '/section/mebel' },
  { name: 'Подподраздел (Кухонные гарнитуры)', url: '/section/kuhonnye-garnitury' },
  { name: 'Раздел (Сад и огород)', url: '/section/sad-i-ogorod' },
  { name: 'Подраздел (Растения)', url: '/section/rasteniya' },
  { name: 'Сайт (МебельПро)', url: '/site/mebelpro' },
  { name: 'Сайт (КухниПремиум)', url: '/site/kuhni-premium' },
  { name: 'О нас', url: '/about' },
  { name: 'Правила', url: '/rules' },
  { name: 'Добавить сайт', url: '/add' },
  { name: 'Свяжитесь с нами', url: '/contact_us' },
  { name: 'Логин модератора', url: '/moderator/login' },
];

for (const { name, url } of PUBLIC_OK_PAGES) {
  test(`SMK: ${name}  →  ${url}`, async ({ page }) => {
    const response = await page.goto(url);
    expect(response?.status()).toBe(200);
    await assertNoPhpErrors(page);
  });
}

// ──────────────────────────────────────────────
// Страницы, которые должны вернуть 404
// ──────────────────────────────────────────────

const PAGES_404 = [
  { name: 'Несуществующий раздел', url: '/section/nonexistent-slug-12345' },
  { name: 'Несуществующий сайт', url: '/site/nonexistent-slug-12345' },
  { name: 'Несуществующий маршрут', url: '/nonexistent-page' },
  { name: 'Отклонённый сайт', url: '/site/otklonjonnyj-sajt' },
];

for (const { name, url } of PAGES_404) {
  test(`SMK-404: ${name}  →  404`, async ({ page }) => {
    const response = await page.goto(url);
    expect(response?.status()).toBe(404);
    await assertNoPhpErrors(page);
  });
}
