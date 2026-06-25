/**
 * Модерация сайта — одобрить, отклонить, редактировать и опубликовать.
 */
import { test, expect } from '@playwright/test';
import {
  loginAsModerator,
  clearSession,
  assertFlash,
  selectOptionByLabel,
  fillSummernote,
  resetTestSites,
} from '../fixtures/test-helpers';

test.describe('Модерация сайта', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsModerator(page);
    await resetTestSites(page);
  });

  test('MOD-SITE-01: данные сайта отображаются', async ({ page }) => {
    await page.goto('/moderator/moderate?id=12');
    await expect(page.locator('body')).toContainText('СайтНаМодерации');
    await expect(page.locator('body')).toContainText('https://moderation-test.ru');
    await expect(page.locator('body')).toContainText('author@test.ru');
  });

  test('MOD-SITE-02: одобрить сайт — статус меняется, сайт виден публично', async ({ page }) => {
    await page.goto('/moderator/moderate?id=12');

    // Нажимаем «Одобрить»
    page.on('dialog', (dialog) => dialog.accept()); // confirm()
    await page.click('button:has-text("ОДОБРИТЬ")');

    // Редирект на список
    await page.waitForURL('**/moderator/list');
    await assertFlash(page, 'success', 'одобрен');

    // Сайт больше не в списке модерации (ссылка на id=12 исчезла)
    await expect(page.locator('a[href="/moderator/moderate?id=12"]')).not.toBeAttached();

    // Сайт виден публично
    await clearSession(page);
    await page.goto('/site/sajt-na-moderacii');
    expect(page.url()).toContain('/site/sajt-na-moderacii');
    await expect(page.locator('h2')).toContainText('СайтНаМодерации');
  });

  test('MOD-SITE-03: отклонить сайт — статус 2, не виден публично', async ({ page }) => {
    await page.goto('/moderator/moderate?id=13');

    // Нажимаем «Отклонить»
    page.on('dialog', (dialog) => dialog.accept());
    await page.click('button:has-text("ОТКЛОНИТЬ")');

    await page.waitForURL('**/moderator/list');
    await assertFlash(page, 'success', 'отклонён');

    // Сайт не виден публично
    await clearSession(page);
    const resp = await page.goto('/site/eshyo-odin-sajt');
    expect(resp?.status()).toBe(404);
  });

  test('MOD-SITE-04: отредактировать и опубликовать', async ({ page }) => {
    await page.goto('/moderator/moderate?id=14');

    const newName = 'Переименованный Сайт';
    await page.fill('#edit_name', newName);
    await selectOptionByLabel(page, '#edit_section_id', 'Декор');

    // Заполняем описание
    await fillSummernote(page, '#edit_description',
      '<p>Обновлённое описание после редактирования модератором.</p>');

    page.on('dialog', (dialog) => dialog.accept());
    await page.click('button:has-text("СОХРАНИТЬ И ОПУБЛИКОВАТЬ")');

    await page.waitForURL('**/moderator/list');
    await assertFlash(page, 'success');

    // Проверяем публичную страницу
    await clearSession(page);
    await page.goto('/site/tretij-sajt-na-moderacii');
    await expect(page.locator('h2')).toContainText(newName);
    await expect(page.locator('.site-description')).toContainText('Обновлённое описание');
  });

  test('MOD-SITE-05: кнопки действий только для status=0', async ({ page }) => {
    // Уже одобренный сайт (после теста MOD-SITE-02 id=12 одобрен)
    // Проверим одобренный сайт (id=1 — МебельПро)
    await page.goto('/moderator/moderate?id=1');

    // Кнопок «Одобрить» и «Отклонить» быть не должно
    await expect(page.locator('button:has-text("ОДОБРИТЬ")')).not.toBeVisible();
    await expect(page.locator('button:has-text("ОТКЛОНИТЬ")')).not.toBeVisible();
  });
});
