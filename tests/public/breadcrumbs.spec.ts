/**
 * Хлебные крошки — проверка правильности цепочки на всех типах страниц.
 */
import { test } from '@playwright/test';
import { assertBreadcrumbs } from '../fixtures/test-helpers';

test.describe('Хлебные крошки (breadcrumbs)', () => {
  test('BC-01: Главная — крошек нет', async ({ page }) => {
    await page.goto('/');
    await assertBreadcrumbs(page, []);
  });

  test('BC-02: Корневой раздел — Начало > Раздел', async ({ page }) => {
    await page.goto('/section/dom-i-interer');
    await assertBreadcrumbs(page, ['Начало', 'Дом и интерьер']);
  });

  test('BC-03: Подраздел (2 уровня) — Начало > Родитель > Подраздел', async ({ page }) => {
    await page.goto('/section/mebel');
    await assertBreadcrumbs(page, [
      'Начало',
      'link:Дом и интерьер',
      'Мебель',
    ]);
  });

  test('BC-04: Подподраздел (3 уровня)', async ({ page }) => {
    await page.goto('/section/kuhonnye-garnitury');
    await assertBreadcrumbs(page, [
      'Начало',
      'link:Дом и интерьер',
      'link:Мебель',
      'Кухонные гарнитуры',
    ]);
  });

  test('BC-05: Страница сайта — полная цепочка', async ({ page }) => {
    await page.goto('/site/mebelpro');
    await assertBreadcrumbs(page, [
      'Начало',
      'link:Дом и интерьер',
      'link:Мебель',
      'МебельПро',
    ]);
  });

  test('BC-06: Страница сайта в подподразделе', async ({ page }) => {
    await page.goto('/site/kuhni-premium');
    await assertBreadcrumbs(page, [
      'Начало',
      'link:Дом и интерьер',
      'link:Мебель',
      'link:Кухонные гарнитуры',
      'КухниПремиум',
    ]);
  });

  test('BC-07: О нас — Начало > О нас', async ({ page }) => {
    await page.goto('/about');
    await assertBreadcrumbs(page, ['Начало', 'О нас']);
  });

  test('BC-08: Правила — Начало > Правила', async ({ page }) => {
    await page.goto('/rules');
    await assertBreadcrumbs(page, ['Начало', 'Правила']);
  });

  test('BC-09: Добавить сайт — Начало > Добавить сайт', async ({ page }) => {
    await page.goto('/add');
    await assertBreadcrumbs(page, ['Начало', 'Добавить сайт']);
  });

  test('BC-10: Свяжитесь с нами — Начало > Свяжитесь с нами', async ({ page }) => {
    await page.goto('/contact_us');
    await assertBreadcrumbs(page, ['Начало', 'Свяжитесь с нами']);
  });

  test('BC-11: Логин модератора — Начало > Вход', async ({ page }) => {
    await page.goto('/moderator/login');
    await assertBreadcrumbs(page, ['Начало', 'Вход']);
  });
});
