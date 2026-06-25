/**
 * Страница сайта — название, дата, описание, URL.
 */
import { test, expect } from '@playwright/test';
import { assertNoPhpErrors, assertBreadcrumbs } from '../fixtures/test-helpers';

test.describe('Страница сайта', () => {
  test('SITE-01: Заголовок — название сайта', async ({ page }) => {
    await page.goto('/site/mebelpro');
    await assertNoPhpErrors(page);
    await expect(page.locator('.site-detail h2')).toContainText('МебельПро');
  });

  test('SITE-02: Дата добавления', async ({ page }) => {
    await page.goto('/site/mebelpro');
    const dateEl = page.locator('.site-date');
    await expect(dateEl).toBeVisible();
    // Формат: «добавлен: ДД.ММ.ГГГГ»
    const text = await dateEl.textContent();
    expect(text).toMatch(/добавлен:\s*\d{2}\.\d{2}\.\d{4}/);
  });

  test('SITE-03: URL сайта отображается', async ({ page }) => {
    await page.goto('/site/mebelpro');
    const urlLink = page.locator('.site-url a');
    await expect(urlLink).toBeVisible();
    await expect(urlLink).toContainText('mebelpro.ru');
  });

  test('SITE-04: Описание сайта', async ({ page }) => {
    await page.goto('/site/mebelpro');
    const desc = page.locator('.site-description');
    await expect(desc).toBeVisible();
    await expect(desc).toContainText('Интернет-магазин мебели');
  });

  test('SITE-05: Хлебные крошки для сайта', async ({ page }) => {
    await page.goto('/site/rasteniya-onlajn');
    await assertBreadcrumbs(page, [
      'Начало',
      'link:Сад и огород',
      'link:Растения',
      'РастенияОнлайн',
    ]);
  });

  test('SITE-06: Сайт в подподразделе — крошки полные', async ({ page }) => {
    await page.goto('/site/kuhni-premium');
    await assertBreadcrumbs(page, [
      'Начало',
      'link:Дом и интерьер',
      'link:Мебель',
      'link:Кухонные гарнитуры',
      'КухниПремиум',
    ]);
  });

  test('SITE-07: Сайт после модерации доступен публично', async ({ page }) => {
    // СайтНаМодерации (id=12) одобрен в MOD-SITE-02 → status=1 → 200
    const resp = await page.goto('/site/sajt-na-moderacii');
    expect(resp?.status()).toBe(200);
    await expect(page.locator('h2')).toContainText('СайтНаМодерации');
  });

  test('SITE-08: Отклонённый сайт → 404', async ({ page }) => {
    const resp = await page.goto('/site/otklonjonnyj-sajt');
    expect(resp?.status()).toBe(404);
  });

  test('SITE-09: Несуществующий slug → 404', async ({ page }) => {
    const resp = await page.goto('/site/absolutely-fake-slug-99999');
    expect(resp?.status()).toBe(404);
  });
});
