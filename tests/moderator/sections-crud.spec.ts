/**
 * Модератор — CRUD управления разделами.
 */
import { test, expect } from '@playwright/test';
import { loginAsModerator, assertFlash, resetTestSites } from '../fixtures/test-helpers';

test.describe('Модератор — дерево разделов', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsModerator(page);
    await page.goto('/moderator/sections');
  });

  test('SEC-CRUD-01: дерево разделов отображается', async ({ page }) => {
    // Корневые разделы
    await expect(page.locator('.section-tree')).toContainText('Дом и интерьер');
    await expect(page.locator('.section-tree')).toContainText('Сад и огород');
    await expect(page.locator('.section-tree')).toContainText('Кулинария');
    await expect(page.locator('.section-tree')).toContainText('Дети и развитие');
    await expect(page.locator('.section-tree')).toContainText('Здоровье и спорт');

    // Подразделы (с отступом)
    await expect(page.locator('.section-tree')).toContainText('Мебель');
    await expect(page.locator('.section-tree')).toContainText('Декор');
    await expect(page.locator('.section-tree')).toContainText('Текстиль');
  });

  test('SEC-CRUD-02: счётчик сайтов отображается', async ({ page }) => {
    // Раздел «Мебель» содержит сайты
    const mebelRow = page.locator('.tree-item').filter({ hasText: 'Мебель' });
    await expect(mebelRow).toContainText('сайтов');
  });

  test('SEC-CRUD-03: клик «ред.» → страница редактирования', async ({ page }) => {
    const editLink = page.locator('.tree-item').filter({ hasText: 'Мебель' }).locator('a:has-text("ред.")');
    await editLink.click();
    await expect(page).toHaveURL(/\/moderator\/sections\/edit\?id=6/);
  });

  test('SEC-CRUD-04: нельзя удалить раздел с сайтами', async ({ page }) => {
    // «Мебель» (id=6) содержит сайты — кнопка удаления disabled
    const mebelRow = page.locator('.tree-item').filter({ hasText: 'Мебель' });
    const deleteBtn = mebelRow.locator('button[disabled]:has-text("удал.")');
    await expect(deleteBtn).toBeVisible();
  });

  test('SEC-CRUD-05: можно удалить пустой раздел', async ({ page }) => {
    // «Дети и развитие» (id=4) — нет сайтов
    const childRow = page.locator('.tree-item').filter({ hasText: 'Дети и развитие' });
    const deleteBtn = childRow.locator('button:not([disabled]):has-text("удал.")');
    await expect(deleteBtn).toBeVisible();
  });

  test('SEC-CRUD-06: фильтр по имени', async ({ page }) => {
    await page.fill('input[name="search"]', 'Мебель');
    await page.click('button:has-text("Найти")');

    // Должен найти Мебель и возможно Кухонные гарнитуры (если фильтр включает дочерние)
    await expect(page.locator('.section-tree')).toContainText('Мебель');
    // Другие корневые разделы могут отсутствовать
  });

  test('SEC-CRUD-07: сброс фильтра', async ({ page }) => {
    await page.fill('input[name="search"]', 'Мебель');
    await page.click('button:has-text("Найти")');
    await page.click('a:has-text("Сбросить")');

    // Должны вернуться все разделы
    await expect(page.locator('.section-tree')).toContainText('Дом и интерьер');
    await expect(page.locator('.section-tree')).toContainText('Кулинария');
  });
});

test.describe('Модератор — добавление раздела', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsModerator(page);
    await resetTestSites(page); // удаляет тестовые разделы от предыдущих прогонов
  });

  test('SEC-ADD-01: форма добавления открывается', async ({ page }) => {
    await page.goto('/moderator/sections/add');
    await expect(page.locator('#name')).toBeVisible();
    await expect(page.locator('#slug')).toBeVisible();
    await expect(page.locator('#parent_id')).toBeVisible();
  });

  test('SEC-ADD-02: создание корневого раздела', async ({ page }) => {
    const uniqueName = `Тестовый раздел ${Date.now()}`;
    await page.goto('/moderator/sections/add');
    await page.fill('#name', uniqueName);
    // slug генерируется автоматически при вводе имени (на клиенте)
    await page.fill('#slug', 'testovyj-razdel-' + Date.now());
    await page.click('button[type="submit"]');

    await page.waitForURL('**/moderator/sections');
    await assertFlash(page, 'success', 'добавлен');
  });

  test('SEC-ADD-03: создание подраздела', async ({ page }) => {
    const uniqueName = `Подраздел ${Date.now()}`;
    const uniqueSlug = `podrazdel-${Date.now()}`;

    await page.goto('/moderator/sections/add?parent_id=4'); // Дети и развитие
    await page.fill('#name', uniqueName);
    await page.fill('#slug', uniqueSlug);
    await page.click('button[type="submit"]');

    await page.waitForURL('**/moderator/sections');
    await assertFlash(page, 'success', 'добавлен');
  });

  test('SEC-ADD-04: пустое название → ошибка', async ({ page }) => {
    await page.goto('/moderator/sections/add');
    await page.fill('#slug', 'test-slug');
    await page.click('button[type="submit"]');
    await expect(page.locator('.alert-danger')).toBeVisible();
  });

  test('SEC-ADD-05: дубликат slug → ошибка', async ({ page }) => {
    await page.goto('/moderator/sections/add');
    await page.fill('#name', 'Дубликат слага');
    await page.fill('#slug', 'mebel'); // уже существует
    await page.click('button[type="submit"]');
    await expect(page.locator('.alert-danger')).toContainText('уже существует');
  });
});
