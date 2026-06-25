/**
 * Контроль доступа — проверка всех модераторских страниц без сессии.
 */
import { test, expect } from '@playwright/test';
import { clearSession, loginAsModerator } from '../fixtures/test-helpers';

test.describe('Контроль доступа', () => {
  test.describe('Без авторизации', () => {
    test.beforeEach(async ({ page }) => {
      await clearSession(page);
    });

    const PROTECTED = [
      '/moderator/list',
      '/moderator/moderate?id=1',
      '/moderator/moderate?id=12',
      '/moderator/sections',
      '/moderator/sections/add',
      '/moderator/sections/add?parent_id=1',
      '/moderator/sections/edit?id=1',
      '/moderator/contact_us',
    ];

    for (const url of PROTECTED) {
      test(`ACC: ${url} → редирект на /moderator/login`, async ({ page }) => {
        await page.goto(url);
        // Должен быть редирект на страницу логина (require_moderator())
        await expect(page).toHaveURL(/\/moderator\/login/);
      });
    }
  });

  test.describe('С авторизацией', () => {
    test('ACC: после логаута доступ закрыт', async ({ page }) => {
      await loginAsModerator(page);
      await page.goto('/moderator/logout');
      await clearSession(page);

      await page.goto('/moderator/list');
      await expect(page).toHaveURL(/\/moderator\/login/);
    });

    test('ACC: после логина доступ открыт', async ({ page }) => {
      await loginAsModerator(page);
      await expect(page).toHaveURL(/\/moderator\/list/);

      await page.goto('/moderator/sections');
      await expect(page).toHaveURL(/\/moderator\/sections/);
      // Не должно быть редиректа на логин
      expect(page.url()).not.toContain('/moderator/login');
    });
  });

  test.describe('Flash-сообщения', () => {
    test('FL-01: flash виден после редиректа', async ({ page }) => {
      // Используем форму обратной связи для генерации flash
      await page.goto('/contact_us');
      await page.fill('#email', `flash-test-${Date.now()}@example.com`);
      await page.fill('#message', 'Проверка flash-сообщений');
      await page.check('#agreement');
      await page.click('button[type="submit"]');

      await page.waitForURL('**/');
      // Flash должен быть виден
      const flash = page.locator('.alert.alert-success.alert-dismissible');
      await expect(flash).toBeVisible({ timeout: 5000 });
    });

    test('FL-02: flash исчезает после обновления', async ({ page }) => {
      // Сначала генерируем flash
      await page.goto('/contact_us');
      await page.fill('#email', `flash-disappear-${Date.now()}@example.com`);
      await page.fill('#message', 'Сообщение для проверки исчезновения flash');
      await page.check('#agreement');
      await page.click('button[type="submit"]');
      await page.waitForURL('**/');

      // Обновляем страницу
      await page.reload();
      // Flash не должен появиться снова
      await expect(page.locator('.alert.alert-success.alert-dismissible')).not.toBeVisible();
    });
  });
});
