/**
 * Статические страницы: О нас, Правила.
 */
import { test, expect } from '@playwright/test';
import { assertNoPhpErrors, assertBreadcrumbs } from '../fixtures/test-helpers';

test.describe('Статические страницы', () => {
  test('STAT-01: О нас — заголовок и контент', async ({ page }) => {
    await page.goto('/about');
    await assertNoPhpErrors(page);
    await expect(page.locator('h2')).toContainText('О НАС');
    await expect(page.locator('main')).toContainText('Добро пожаловать');
  });

  test('STAT-02: О нас — хлебные крошки', async ({ page }) => {
    await page.goto('/about');
    await assertBreadcrumbs(page, ['Начало', 'О нас']);
  });

  test('STAT-03: Правила — заголовок и контент', async ({ page }) => {
    await page.goto('/rules');
    await assertNoPhpErrors(page);
    await expect(page.locator('h2')).toContainText('ПРАВИЛА');
    await expect(page.locator('main')).toContainText('Добавление сайтов');
  });

  test('STAT-04: Правила — хлебные крошки', async ({ page }) => {
    await page.goto('/rules');
    await assertBreadcrumbs(page, ['Начало', 'Правила']);
  });
});
