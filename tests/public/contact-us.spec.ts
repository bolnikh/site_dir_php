/**
 * Форма обратной связи — JS-валидация и успешная отправка.
 */
import { test, expect } from '@playwright/test';
import {
  assertNoPhpErrors,
  assertFlash,
  assertFieldInvalid,
  submitContactForm,
} from '../fixtures/test-helpers';

test.describe('Свяжитесь с нами — JS-валидация', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/contact_us');
    await assertNoPhpErrors(page);
  });

  test('CNT-JS-01: пустой email → ошибка', async ({ page }) => {
    await page.fill('#message', 'Тестовое сообщение');
    await page.check('#agreement');
    await page.click('button[type="submit"]');
    await assertFieldInvalid(page, '#email');
  });

  test('CNT-JS-02: некорректный email → ошибка', async ({ page }) => {
    await page.fill('#email', 'not-an-email');
    await page.fill('#message', 'Сообщение');
    await page.check('#agreement');
    await page.click('button[type="submit"]');
    await assertFieldInvalid(page, '#email');
  });

  test('CNT-JS-03: пустое сообщение → ошибка', async ({ page }) => {
    await page.fill('#email', 'test@example.com');
    await page.check('#agreement');
    await page.click('button[type="submit"]');
    await assertFieldInvalid(page, '#message');
  });

  test('CNT-JS-04: чекбокс не отмечен → ошибка', async ({ page }) => {
    await page.fill('#email', 'test@example.com');
    await page.fill('#message', 'Сообщение');
    await page.click('button[type="submit"]');
    await assertFieldInvalid(page, '#agreement');
  });
});

test.describe('Свяжитесь с нами — серверная отправка', () => {
  test('CNT-SRV-01: успешная отправка → flash + редирект', async ({ page }) => {
    await page.goto('/contact_us');
    await submitContactForm(page, {
      email: `test-${Date.now()}@example.com`,
      message: 'Тестовое сообщение из Playwright.',
      agreement: true,
    });

    await page.waitForURL('**/');
    await assertFlash(page, 'success', 'отправлено');
  });

  test('CNT-SRV-02: опциональный телефон — успешная отправка', async ({ page }) => {
    await page.goto('/contact_us');
    // Телефон можно не указывать
    await submitContactForm(page, {
      email: `test-${Date.now()}@example.com`,
      message: 'Сообщение без телефона.',
      agreement: true,
    });

    await page.waitForURL('**/');
    await assertFlash(page, 'success', 'отправлено');
  });
});
