/**
 * Модератор — список сайтов на модерации.
 */
import { test, expect } from '@playwright/test';
import { loginAsModerator, resetTestSites } from '../fixtures/test-helpers';

test.describe('Модератор — список сайтов', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsModerator(page);
    await resetTestSites(page);
  });

  test('MOD-LIST-01: страница загружается, заголовок виден', async ({ page }) => {
    await expect(page.locator('h2')).toContainText('МОДЕРАЦИЯ');
  });

  test('MOD-LIST-02: отображаются только сайты со status=0', async ({ page }) => {
    const items = page.locator('.site-list-item');
    const count = await items.count();
    expect(count).toBeGreaterThanOrEqual(1);

    // Проверяем что каждый элемент содержит «ожидает проверки»
    for (let i = 0; i < count; i++) {
      await expect(items.nth(i)).toContainText('ожидает проверки');
    }

    // Опубликованные сайты не должны быть в списке
    await expect(page.locator('.site-list')).not.toContainText('МебельПро');
  });

  test('MOD-LIST-03: клик по сайту → переход на модерацию', async ({ page }) => {
    const firstLink = page.locator('.site-list-item a[href^="/moderator/moderate?id="]').first();
    await firstLink.click();
    await expect(page).toHaveURL(/\/moderator\/moderate\?id=\d+/);
    await expect(page.locator('h2')).toContainText('МОДЕРАЦИЯ САЙТА');
  });

  test('MOD-LIST-04: поиск по названию работает', async ({ page }) => {
    await page.fill('input[name="search"]', 'СайтНаМодерации');
    await page.click('button:has-text("Поиск")');

    // Поиск по подстроке — находит оба сайта, содержащие «СайтНаМодерации» в названии
    const items = page.locator('.site-list-item');
    await expect(items).toHaveCount(2);
    await expect(items.nth(1)).toContainText('ТретийСайтНаМодерации');
    await expect(items.first()).toContainText('СайтНаМодерации');
    await expect(page.locator('.site-list')).not.toContainText('ЕщёОдинСайт');
  });

  test('MOD-LIST-05: поиск — нет результатов', async ({ page }) => {
    await page.fill('input[name="search"]', 'НеСуществуетТакойСайт12345');
    await page.click('button:has-text("Поиск")');
    await expect(page.locator('.alert-info')).toContainText('Нет сайтов');
  });

  test('MOD-LIST-06: сброс поиска', async ({ page }) => {
    await page.fill('input[name="search"]', 'СайтНаМодерации');
    await page.click('button:has-text("Поиск")');
    await page.click('a:has-text("Сброс")');

    // Должны вернуться все сайты на модерации
    const items = page.locator('.site-list-item');
    await expect(items).toHaveCount(3); // 3 сайта со status=0 в сидах
  });
});
