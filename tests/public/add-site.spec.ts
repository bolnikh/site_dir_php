/**
 * Форма добавления сайта — JS-валидация, серверная валидация, успешная отправка.
 */
import { test, expect } from '@playwright/test';
import {
  assertNoPhpErrors,
  assertFlash,
  assertFieldInvalid,
  submitAddSiteForm,
} from '../fixtures/test-helpers';

test.describe('Добавить сайт — JS-валидация', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/add');
    await assertNoPhpErrors(page);
  });

  test('ADD-JS-01: пустой раздел → ошибка', async ({ page }) => {
    await page.fill('#name', 'Тестовый сайт');
    await page.fill('#url', 'https://example.com');
    await page.fill('#description', 'Описание');
    await page.check('#agreement');
    await page.click('button[type="submit"]');
    await assertFieldInvalid(page, '#section_id');
  });

  test('ADD-JS-02: пустое название → ошибка', async ({ page }) => {
    await page.fill('#url', 'https://example.com');
    await page.fill('#description', 'Описание');
    await page.check('#agreement');
    await page.click('button[type="submit"]');
    await assertFieldInvalid(page, '#name');
  });

  // не достижим, в поле столько не вставить
  /*
  test('ADD-JS-03: слишком длинное название → ошибка', async ({ page }) => {
    await page.fill('#name', 'A'.repeat(513));
    await page.fill('#url', 'https://example.com');
    await page.fill('#description', 'Описание');
    await page.check('#agreement');
    await page.click('button[type="submit"]');
    await assertFieldInvalid(page, '#name');
  });*/

  test('ADD-JS-04: пустой URL → ошибка', async ({ page }) => {
    await page.fill('#name', 'Сайт');
    await page.fill('#description', 'Описание');
    await page.check('#agreement');
    await page.click('button[type="submit"]');
    await assertFieldInvalid(page, '#url');
  });

  test('ADD-JS-05: некорректный URL → ошибка', async ({ page }) => {
    await page.fill('#name', 'Сайт');
    await page.fill('#url', 'not-a-url');
    await page.fill('#description', 'Описание');
    await page.check('#agreement');
    await page.click('button[type="submit"]');
    await assertFieldInvalid(page, '#url');
  });

  test('ADD-JS-06: URL без протокола → ошибка', async ({ page }) => {
    await page.fill('#name', 'Сайт');
    await page.fill('#url', 'example.com');
    await page.fill('#description', 'Описание');
    await page.check('#agreement');
    await page.click('button[type="submit"]');
    await assertFieldInvalid(page, '#url');
  });

  test('ADD-JS-07: пустое описание → ошибка', async ({ page }) => {
    await page.fill('#name', 'Сайт');
    await page.fill('#url', 'https://example.com');
    await page.check('#agreement');
    await page.click('button[type="submit"]');
    await assertFieldInvalid(page, '#description');
  });

  test('ADD-JS-08: некорректный email → ошибка', async ({ page }) => {
    await page.fill('#name', 'Сайт');
    await page.fill('#url', 'https://example.com');
    await page.fill('#email', 'not-an-email');
    await page.fill('#description', 'Описание');
    await page.check('#agreement');
    await page.click('button[type="submit"]');
    await assertFieldInvalid(page, '#email');
  });

  test('ADD-JS-09: чекбокс не отмечен → ошибка', async ({ page }) => {
    await page.fill('#name', 'Сайт');
    await page.fill('#url', 'https://example.com');
    await page.fill('#description', 'Описание');
    // agreement НЕ отмечаем
    await page.click('button[type="submit"]');
    await assertFieldInvalid(page, '#agreement');
  });

  test('ADD-JS-10: все поля пустые — ошибка на первом поле, скролл', async ({ page }) => {
    await page.click('button[type="submit"]');
    // Должна быть хотя бы одна ошибка
    const invalidFields = page.locator('.is-invalid');
    await expect(invalidFields.first()).toBeVisible();
  });

  test('ADD-JS-11: ошибка исчезает после исправления', async ({ page }) => {
    // Сначала вызываем ошибку на URL
    await page.fill('#name', 'Сайт');
    await page.fill('#url', 'bad-url');
    await page.fill('#description', 'Описание');
    await page.check('#agreement');
    await page.click('button[type="submit"]');
    await assertFieldInvalid(page, '#url');

    // Исправляем и снова сабмитим
    await page.fill('#url', 'https://correct-url.com');
    await page.click('button[type="submit"]');
    // Ошибка на URL должна исчезнуть (форма ушла на сервер)
    await expect(page.locator('#url')).not.toHaveClass(/is-invalid/);
  });
});

test.describe('Добавить сайт — серверная валидация и отправка', () => {
  test('ADD-SRV-01: дубликат URL → ошибка сервера', async ({ page }) => {
    await page.goto('/add');
    await submitAddSiteForm(page, {
      sectionLabel: 'Мебель',
      name: 'Дубликат URL',
      url: 'https://mebelpro.ru', // уже существует в сидах
      description: '<p>Попытка дубликата URL</p>',
      agreement: true,
    });
    // Должна быть ошибка валидации (alert-danger)
    await expect(page.locator('.alert-danger')).toBeVisible();
  });

  test('ADD-SRV-02: успешная отправка → flash + редирект', async ({ page }) => {
    const uniqueId = Date.now();
    await page.goto('/add');
    await submitAddSiteForm(page, {
      sectionLabel: 'Мебель',
      name: `Уникальный тест ${uniqueId}`,
      url: `https://unique-test-${uniqueId}.ru`,
      description: '<p>Тестовое описание для проверки отправки формы.</p>',
      agreement: true,
    });

    // Редирект на главную
    await page.waitForURL('**/');
    await assertFlash(page, 'success', 'отправлен');
  });

  test('ADD-SRV-03: необязательный email — успешная отправка без email', async ({ page }) => {
    const uniqueId = Date.now() + 1;
    await page.goto('/add');
    await submitAddSiteForm(page, {
      sectionLabel: 'Растения',
      name: `Без Email ${uniqueId}`,
      url: `https://no-email-${uniqueId}.ru`,
      description: '<p>Сайт без указания email автора.</p>',
      email: '',
      agreement: true,
    });

    await page.waitForURL('**/');
    await assertFlash(page, 'success', 'отправлен');
  });
});
