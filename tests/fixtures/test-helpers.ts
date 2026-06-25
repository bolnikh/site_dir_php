import { Page, expect } from '@playwright/test';

/**
 * Базовый URL приложения
 */
export const BASE_URL = process.env.BASE_URL || 'http://localhost:8080';

/**
 * Учётные данные модератора (должны совпадать с теми, что в БД)
 */
export const MODERATOR = {
  username: process.env.MODERATOR_USERNAME || 'admin',
  password: process.env.MODERATOR_PASSWORD || 'password',
};

// ──────────────────────────────────────────────
// Проверка отсутствия ошибок
// ──────────────────────────────────────────────

/**
 * Проверить отсутствие PHP-ошибок и других технических ошибок на странице.
 */
export async function assertNoPhpErrors(page: Page): Promise<void> {
  const body = await page.textContent('body');
  expect(body).not.toContain('Fatal error');
  expect(body).not.toContain('Warning:');
  expect(body).not.toContain('Parse error');
  expect(body).not.toContain('Stack trace');
  expect(body).not.toContain('Uncaught');
  expect(body).not.toContain('PDOException');
  expect(body).not.toContain('SQLSTATE');
}

// ──────────────────────────────────────────────
// Хлебные крошки
// ──────────────────────────────────────────────

/**
 * Проверить хлебные крошки.
 *
 * @param page   — страница Playwright
 * @param crumbs — ожидаемые тексты крошек по порядку.
 *                 Префикс 'link:' означает ссылку, иначе — текст без ссылки.
 *                 Пример: ['Начало', 'link:Дом и интерьер', 'Мебель']
 *                 Пустой массив — крошек нет.
 */
export async function assertBreadcrumbs(
  page: Page,
  crumbs: string[],
): Promise<void> {
  const nav = page.locator('nav[aria-label="breadcrumb"]');

  if (crumbs.length === 0) {
    await expect(nav).not.toBeVisible();
    return;
  }

  await expect(nav).toBeVisible();
  const items = nav.locator('ol.breadcrumb > li.breadcrumb-item');
  await expect(items).toHaveCount(crumbs.length);

  for (let i = 0; i < crumbs.length; i++) {
    const raw = crumbs[i];
    const isLink = raw.startsWith('link:');
    const expectedText = isLink ? raw.slice(5) : raw;
    const isLast = i === crumbs.length - 1;

    const item = items.nth(i);
    await expect(item).toContainText(expectedText);

    if (isLast) {
      // Последний — текст без ссылки, имеет класс active
      await expect(item).toHaveClass(/active/);
      await expect(item.locator('a')).not.toBeAttached();
    } else {
      // Предыдущие — ссылки
      await expect(item.locator('a')).toBeAttached();
    }
  }
}

// ──────────────────────────────────────────────
// Flash-сообщения
// ──────────────────────────────────────────────

/**
 * Проверить наличие flash-сообщения нужного типа с нужным текстом.
 * Тип: success, danger, warning, info (Bootstrap классы alert-*).
 */
export async function assertFlash(
  page: Page,
  type: string,
  textContains?: string,
): Promise<void> {
  const sel = `.alert.alert-${type}.alert-dismissible`;
  const flash = page.locator(sel).first();
  await expect(flash).toBeVisible({ timeout: 5000 });
  if (textContains) {
    await expect(flash).toContainText(textContains);
  }
}

// ──────────────────────────────────────────────
// CSRF-токен
// ──────────────────────────────────────────────

/**
 * Извлечь CSRF-токен из скрытого поля формы.
 */
export async function getCsrfToken(page: Page, formSelector = 'form'): Promise<string> {
  const input = page.locator(`${formSelector} input[name="csrf_token"]`);
  return await input.inputValue();
}

/**
 * Выбрать опцию в селекте по тексту (частичное совпадение).
 * Нужно потому что подразделы имеют отступы "— " перед названием.
 */
export async function selectOptionByLabel(
  page: Page,
  selectSelector: string,
  label: string,
): Promise<void> {
  const option = page.locator(`${selectSelector} option`).filter({ hasText: label });
  const value = await option.getAttribute('value');
  if (value) {
    await page.selectOption(selectSelector, value);
  }
}

// ──────────────────────────────────────────────
// Авторизация модератора
// ──────────────────────────────────────────────

/**
 * Залогиниться как модератор. После вызова страница — на /moderator/list.
 */
export async function loginAsModerator(page: Page): Promise<void> {
  await page.goto('/moderator/login');
  await page.fill('input[name="username"]', MODERATOR.username);
  await page.fill('input[name="password"]', MODERATOR.password);
  await page.click('button[type="submit"]');
  await page.waitForURL('**/moderator/list');
}

/**
 * Сбросить тестовые сайты (id 12-14) в исходное состояние (status=0).
 * Требует авторизации модератора.
 */
export async function resetTestSites(page: Page): Promise<void> {
  await page.goto('/moderator/reset-test-data');
  // После сброса редирект на /moderator/list
  await page.waitForURL('**/moderator/list');
}

/**
 * Выйти из модераторской сессии.
 */
export async function logoutModerator(page: Page): Promise<void> {
  await page.goto('/moderator/logout');
  // После логаута должны быть на главной или странице логина
  await page.waitForTimeout(300);
}

/**
 * Удалить все куки (сбросить сессию).
 */
export async function clearSession(page: Page): Promise<void> {
  await page.context().clearCookies();
}

// ──────────────────────────────────────────────
// Валидация полей формы
// ──────────────────────────────────────────────

/**
 * Проверить что поле имеет ошибку валидации (класс is-invalid).
 */
export async function assertFieldInvalid(
  page: Page,
  fieldSelector: string,
): Promise<void> {
  await expect(page.locator(fieldSelector)).toHaveClass(/is-invalid/);
}

/**
 * Проверить что после сабмита формы поле не имеет ошибки.
 */
export async function assertFieldValid(
  page: Page,
  fieldSelector: string,
): Promise<void> {
  await expect(page.locator(fieldSelector)).not.toHaveClass(/is-invalid/);
}

// ──────────────────────────────────────────────
// Заполнение редактора (Summernote или textarea)
// ──────────────────────────────────────────────

/**
 * Заполнить редактор описания.
 * Пробует Summernote API (если jQuery загружен), иначе — прямой fill textarea.
 */
export async function fillSummernote(
  page: Page,
  editorSelector: string,
  html: string,
): Promise<void> {
  await page.waitForTimeout(500);

  // Пробуем через Summernote API
  const hasJQuery = await page.evaluate(() => typeof (window as any).$ === 'function');

  if (hasJQuery) {
    const sel = editorSelector.startsWith('#') ? editorSelector : '#' + editorSelector;
    await page.evaluate(
      ({ sel, html }) => {
        (window as any).$(sel).summernote('code', html);
      },
      { sel, html },
    );
  } else {
    // jQuery не загружен (Bootstrap 5) — работаем с textarea напрямую
    await page.fill(editorSelector, html);
  }
}

// ──────────────────────────────────────────────
// Подача сайта (публичная форма)
// ──────────────────────────────────────────────

export interface AddSiteData {
  sectionLabel?: string;
  name?: string;
  url?: string;
  description?: string;
  email?: string;
  agreement?: boolean;
}

/**
 * Заполнить и отправить форму добавления сайта.
 */
export async function submitAddSiteForm(
  page: Page,
  data: AddSiteData,
): Promise<void> {
  if (data.sectionLabel !== undefined) {
    await selectOptionByLabel(page, '#section_id', data.sectionLabel);
  }
  if (data.name !== undefined) {
    await page.fill('#name', data.name);
  }
  if (data.url !== undefined) {
    await page.fill('#url', data.url);
  }
  if (data.description !== undefined) {
    await fillSummernote(page, '#description', data.description);
  }
  if (data.email !== undefined) {
    await page.fill('#email', data.email);
  }
  if (data.agreement) {
    await page.check('#agreement');
  }
  await page.click('button[type="submit"]');
}

// ──────────────────────────────────────────────
// Подача формы обратной связи
// ──────────────────────────────────────────────

export interface ContactData {
  email?: string;
  phone?: string;
  message?: string;
  agreement?: boolean;
}

/**
 * Заполнить и отправить форму обратной связи.
 */
export async function submitContactForm(
  page: Page,
  data: ContactData,
): Promise<void> {
  if (data.email !== undefined) {
    await page.fill('#email', data.email);
  }
  if (data.phone !== undefined) {
    await page.fill('#phone', data.phone);
  }
  if (data.message !== undefined) {
    await page.fill('#message', data.message);
  }
  if (data.agreement) {
    await page.check('#agreement');
  }
  await page.click('button[type="submit"]');
}
