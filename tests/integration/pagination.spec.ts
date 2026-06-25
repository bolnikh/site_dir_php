/**
 * Постраничная навигация — страницы разделов и модератора.
 * Используем константы slug/name из сидов, чтобы тесты были самодокументированы.
 */
import { test, expect } from '@playwright/test';
import { assertNoPhpErrors, loginAsModerator } from '../fixtures/test-helpers';

const SECTION_SLUG = 'dom-i-interer';
const SECTION_NAME = 'Дом и интерьер';

test.describe('Пагинация — публичная часть', () => {
  test('PAG-01: раздел с ≤20 сайтов — пагинации нет', async ({ page }) => {
    await page.goto(`/section/${SECTION_SLUG}`);
    console.log(`/section/${SECTION_SLUG}`)
    await assertNoPhpErrors(page);
    // Раздел загрузился (не 404)
    await expect(page.locator('h2')).toContainText(SECTION_NAME);
    // 6 сайтов, 20 на страницу → 1 страница, пагинация не нужна
    await expect(page.locator('.pagination-nav')).not.toBeVisible();
  });

  test('PAG-02: pg=1 — раздел загружается', async ({ page }) => {
    await page.goto(`/section/${SECTION_SLUG}?pg=1`);
    await assertNoPhpErrors(page);
    await expect(page.locator('h2')).toContainText(SECTION_NAME);
  });

  test('PAG-03: pg=0, pg=-1, pg=abc не ломают страницу', async ({ page }) => {
    for (const pg of ['0', '-1', 'abc']) {
      await page.goto(`/section/${SECTION_SLUG}?pg=${pg}`);
      await assertNoPhpErrors(page);
      await expect(page.locator('h2')).toContainText(SECTION_NAME);
    }
  });

  test('PAG-04: pg=999999 — нет fatal error', async ({ page }) => {
    await page.goto(`/section/${SECTION_SLUG}?pg=999999`);
    await assertNoPhpErrors(page);
  });
});

test.describe('Пагинация — модератор', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsModerator(page);
  });

  test('PAG-MOD-01: 3 сайта на модерации — пагинации нет', async ({ page }) => {
    await expect(page.locator('.pagination-nav')).not.toBeVisible();
  });
});
