/**
 * Сквозные сценарии: подача сайта → модерация → публикация.
 *
 * ВАЖНО: используем ТОЛЬКО латиницу в названиях сайтов,
 * потому что slug генерируется на сервере через транслитерацию (generate_slug),
 * и клиент не может вычислить его для русских букв.
 */
import { test, expect } from '@playwright/test';
import {
  loginAsModerator,
  clearSession,
  assertFlash,
  submitAddSiteForm,
  selectOptionByLabel,
  fillSummernote,
} from '../fixtures/test-helpers';

/**
 * Вычислить slug из латинского названия так же, как это делает серверный generate_slug().
 * Для ЛАТИНИЦЫ: lowercase + пробелы→дефисы + удалить не-[a-z0-9-].
 */
function latinSlug(name: string): string {
  return name
    .toLowerCase()
    .replace(/\s+/g, '-')
    .replace(/[^a-z0-9-]/g, '')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '');
}

test.describe('Сквозной сценарий', () => {
  test('E2E: подача сайта → модератор одобряет → сайт виден всем', async ({ page }) => {
    const ts = Date.now();
    const siteName = `E2E Approved ${ts}`;
    const siteUrl = `https://e2e-approved-${ts}.ru`;
    const siteSlug = latinSlug(siteName); // e.g. "e2e-approved-1234567890"

    // ── Шаг 1: Гость добавляет сайт ──
    await page.goto('/add');
    await submitAddSiteForm(page, {
      sectionLabel: 'Мебель',
      name: siteName,
      url: siteUrl,
      description: '<p>Описание для сквозного теста (принятие).</p>',
      agreement: true,
    });
    await page.waitForURL('**/');
    await assertFlash(page, 'success', 'отправлен');

    // ── Шаг 2: Модератор входит ──
    await loginAsModerator(page);

    // ── Шаг 3: Находит сайт в списке на модерации ──
    await expect(page.locator('.site-list')).toContainText(siteName);

    // ── Шаг 4: Переходит на страницу модерации сайта ──
    await page.click(`.site-list-item a:has-text("${siteName}")`);
    await page.waitForURL('**/moderator/moderate*');

    // ── Шаг 5: Одобряет ──
    page.on('dialog', (dialog) => dialog.accept());
    await page.click('button:has-text("ОДОБРИТЬ")');
    await page.waitForURL('**/moderator/list');
    await assertFlash(page, 'success');

    // ── Шаг 6: Гость видит сайт на главной ──
    await clearSession(page);
    await page.goto('/');
    await expect(page.locator('.recent-sites')).toContainText(siteName);

    // ── Шаг 7: Гость видит сайт в разделе ──
    await page.goto('/section/mebel');
    await expect(page.locator('.site-list')).toContainText(siteName);

    // ── Шаг 8: Гость открывает страницу сайта ──
    await page.goto(`/site/${siteSlug}`);
    await expect(page.locator('.site-detail h2')).toContainText(siteName);
    await expect(page.locator('.site-description')).toContainText('сквозного теста');
  });

  test('E2E: подача сайта → модератор отклоняет → сайт не виден', async ({ page }) => {
    const ts = Date.now();
    const siteName = `E2E Rejected ${ts}`;
    const siteUrl = `https://e2e-rejected-${ts}.ru`;
    const siteSlug = latinSlug(siteName);

    // ── Гость добавляет ──
    await page.goto('/add');
    await submitAddSiteForm(page, {
      sectionLabel: 'Инструменты',
      name: siteName,
      url: siteUrl,
      description: '<p>Этот сайт будет отклонён.</p>',
      agreement: true,
    });
    await page.waitForURL('**/');

    // ── Модератор отклоняет ──
    await loginAsModerator(page);
    await page.click(`.site-list-item a:has-text("${siteName}")`);
    await page.waitForURL('**/moderator/moderate*');

    page.on('dialog', (dialog) => dialog.accept());
    await page.click('button:has-text("ОТКЛОНИТЬ")');
    await page.waitForURL('**/moderator/list');

    // ── Гость НЕ видит сайт ──
    await clearSession(page);

    // На главной
    await page.goto('/');
    await expect(page.locator('.recent-sites')).not.toContainText(siteName);

    // В разделе — сайта нет
    await page.goto('/section/instrumenty');
    await expect(page.locator('body')).not.toContainText(siteName);

    // По прямой ссылке — 404
    const resp = await page.goto(`/site/${siteSlug}`);
    expect(resp?.status()).toBe(404);
  });

  test('E2E: модератор редактирует и публикует → видна обновлённая версия', async ({ page }) => {
    const ts = Date.now();
    const originalName = `E2E PreEdit ${ts}`;
    const siteUrl = `https://e2e-edit-${ts}.ru`;
    const updatedName = `E2E PostEdit ${ts}`;
    // Slug вычисляется один раз из оригинального имени при создании
    const siteSlug = latinSlug(originalName);

    // ── Гость добавляет ──
    await page.goto('/add');
    await submitAddSiteForm(page, {
      sectionLabel: 'Мебель',
      name: originalName,
      url: siteUrl,
      description: '<p>Оригинальное описание.</p>',
      agreement: true,
    });
    await page.waitForURL('**/');

    // ── Модератор редактирует и публикует ──
    await loginAsModerator(page);
    await page.click(`.site-list-item a:has-text("${originalName}")`);
    await page.waitForURL('**/moderator/moderate*');

    // Меняем название и описание
    await page.fill('#edit_name', updatedName);
    await selectOptionByLabel(page, '#edit_section_id', 'Декор');
    await fillSummernote(page, '#edit_description',
      '<p>Обновлённое описание после модерации.</p>');

    page.on('dialog', (dialog) => dialog.accept());
    await page.click('button:has-text("СОХРАНИТЬ И ОПУБЛИКОВАТЬ")');
    await page.waitForURL('**/moderator/list');

    // ── Гость видит обновлённую версию ──
    await clearSession(page);

    // В разделе Декор (новый раздел)
    await page.goto('/section/dekor');
    await expect(page.locator('.site-list')).toContainText(updatedName);

    // На странице сайта (slug от оригинального имени)
    await page.goto(`/site/${siteSlug}`);
    await expect(page.locator('.site-detail h2')).toContainText(updatedName);
    await expect(page.locator('.site-description')).toContainText('Обновлённое описание');
  });
});
