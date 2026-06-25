/**
 * Модератор — авторизация, логаут, защита страниц.
 */
import { test, expect } from '@playwright/test';
import {
  MODERATOR,
  loginAsModerator,
  clearSession,
  assertFlash,
} from '../fixtures/test-helpers';

test.describe('Модератор — авторизация', () => {
  test('MOD-AUTH-01: страница логина отображается', async ({ page }) => {
    await page.goto('/moderator/login');
    await expect(page.locator('input[name="username"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('MOD-AUTH-02: неверный логин/пароль → ошибка', async ({ page }) => {
    await page.goto('/moderator/login');
    await page.fill('input[name="username"]', 'wrong_user');
    await page.fill('input[name="password"]', 'wrong_pass');
    await page.click('button[type="submit"]');

    // Ошибка — остаёмся на странице логина
    await expect(page).toHaveURL(/\/moderator\/login/);
    await expect(page.locator('.alert-danger')).toBeVisible();
    await expect(page.locator('.alert-danger')).toContainText('Неверный');
  });

  test('MOD-AUTH-03: пустой логин → ошибка', async ({ page }) => {
    await page.goto('/moderator/login');
    await page.fill('input[name="password"]', 'somepass');
    await page.click('button[type="submit"]');
    await expect(page.locator('.alert-danger')).toBeVisible();
  });

  test('MOD-AUTH-04: верные данные → редирект на /moderator/list', async ({ page }) => {
    await loginAsModerator(page);
    await expect(page).toHaveURL(/\/moderator\/list/);
  });

  test('MOD-AUTH-05: логаут — сессия очищается', async ({ page }) => {
    await loginAsModerator(page);
    await page.goto('/moderator/logout');

    // После логаута попытка зайти на защищённую страницу → редирект
    await clearSession(page);
    await page.goto('/moderator/list');
    await expect(page).toHaveURL(/\/moderator\/login/);
  });
});

test.describe('Модератор — защита страниц', () => {
  test.beforeEach(async ({ page }) => {
    await clearSession(page);
  });

  const PROTECTED_URLS = [
    { name: 'список на модерации', url: '/moderator/list' },
    { name: 'модерация сайта', url: '/moderator/moderate?id=1' },
    { name: 'управление разделами', url: '/moderator/sections' },
    { name: 'добавление раздела', url: '/moderator/sections/add' },
    { name: 'редактирование раздела', url: '/moderator/sections/edit?id=1' },
    { name: 'сообщения обратной связи', url: '/moderator/contact_us' },
  ];

  for (const { name, url } of PROTECTED_URLS) {
    test(`ACC: ${name} без логина → редирект`, async ({ page }) => {
      await page.goto(url);
      await expect(page).toHaveURL(/\/moderator\/login/);
    });
  }
});
