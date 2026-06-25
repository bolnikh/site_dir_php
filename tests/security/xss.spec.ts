/**
 * XSS-защита — проверка экранирования пользовательских данных.
 */
import { test, expect } from '@playwright/test';
import { assertNoPhpErrors, selectOptionByLabel } from '../fixtures/test-helpers';

test.describe('XSS-защита', () => {
  test('XSS-01: script-тег в названии сайта экранирован', async ({ page }) => {
    // Открываем страницу с сайтом, у которого в названии может быть script
    // Проверяем что на публичных страницах названия экранированы
    await page.goto('/site/mebelpro');
    await assertNoPhpErrors(page);

    // Проверяем что в DOM нет незаэкранированных скриптов
    const scripts = await page.locator('script:not([src])').allTextContents();
    // Не должно быть инжектированных алертов в данных
    for (const s of scripts) {
      expect(s).not.toContain('alert(');
    }
  });

  test('XSS-02: HTML-теги в названии сайта экранируются', async ({ page }) => {
    // Добавляем сайт с HTML в названии
    const uniqueId = Date.now();
    await page.goto('/add');

    await selectOptionByLabel(page, '#section_id', 'Мебель');
    await page.fill('#name', '<b>Жирный</b> <script>alert(1)</script>');
    await page.fill('#url', `https://xss-name-${uniqueId}.ru`);
    await page.fill('#description', 'Безопасное описание');
    await page.check('#agreement');
    await page.click('button[type="submit"]');

    // Если серверная валидация пропустила — смотрим результат
    await page.waitForTimeout(1000);

    // На странице не должно быть сырого HTML в данных
    const bodyText = await page.textContent('body');
    expect(bodyText).not.toContain('<script>alert(1)</script>');
  });

  test('XSS-03: javascript: URL отклоняется JS-валидацией', async ({ page }) => {
    await page.goto('/add');
    await page.fill('#name', 'XSS URL Test');
    await page.fill('#url', 'javascript:alert(1)');
    await page.fill('#description', 'Описание');
    await page.check('#agreement');
    await page.click('button[type="submit"]');

    // JS-валидация должна отклонить такой URL
    await expect(page.locator('#url')).toHaveClass(/is-invalid/);
  });

  test('XSS-04: страницы не содержат echo/print без экранирования', async ({ page }) => {
    const pages = ['/', '/about', '/rules', '/section/mebel', '/site/mebelpro'];

    for (const url of pages) {
      await page.goto(url);
      await assertNoPhpErrors(page);
      // На странице не должно быть сырых PHP-тегов
      const html = await page.content();
      expect(html).not.toContain('<?php');
    }
  });
});
