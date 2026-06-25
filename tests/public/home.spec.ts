/**
 * Главная страница — сетка разделов, последние сайты, кнопка «Добавить сайт».
 */
import { test, expect } from '@playwright/test';
import { assertNoPhpErrors } from '../fixtures/test-helpers';

test.describe('Главная страница (/)', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
    await assertNoPhpErrors(page);
  });

  test('HOME-01: Сетка разделов — 5 (или больше) корневых разделов', async ({ page }) => {
    const cards = page.locator('.section-card');
    // Минимум 5 из сидов; тесты CRUD разделов могут добавить новые
    await expect(cards).not.toHaveCount(0);
    const count = await cards.count();
    expect(count).toBeGreaterThanOrEqual(5);
  });

  test('HOME-02: Каждый раздел — ссылка на /section/{slug}', async ({ page }) => {
    const expectedSections = [
      { name: 'Дом и интерьер', slug: 'dom-i-interer' },
      { name: 'Сад и огород', slug: 'sad-i-ogorod' },
      { name: 'Кулинария', slug: 'kulinariya' },
      { name: 'Дети и развитие', slug: 'deti-i-razvitie' },
      { name: 'Здоровье и спорт', slug: 'zdorove-i-sport' },
    ];

    for (const { name, slug } of expectedSections) {
      const link = page.locator('.section-title a', { hasText: name });
      await expect(link).toBeVisible();
      const href = await link.getAttribute('href');
      expect(href).toBe(`/section/${slug}`);
    }
  });

  test('HOME-03: Подразделы перечислены под названиями разделов', async ({ page }) => {
    // Дом и интерьер: должны быть Мебель, Декор, Текстиль
    const homeSection = page.locator('.section-card').filter({ hasText: 'Дом и интерьер' });
    await expect(homeSection.locator('.subsections')).toContainText('Мебель');
    await expect(homeSection.locator('.subsections')).toContainText('Декор');
    await expect(homeSection.locator('.subsections')).toContainText('Текстиль');
  });

  test('HOME-04: Кнопка «Добавить сайт» ведёт на /add', async ({ page }) => {
    // На странице две кнопки (шапка + основной блок), обе ведут на /add
    const btn = page.locator('a.btn:has-text("Добавить сайт")').first();
    await expect(btn).toBeVisible();
    const href = await btn.getAttribute('href');
    expect(href).toBe('/add');
  });

  test('HOME-05: Список последних 10 сайтов', async ({ page }) => {
    const heading = page.locator('h3:has-text("10 последних сайтов")');
    await expect(heading).toBeVisible();

    const items = page.locator('.recent-sites .site-list-item');
    // В сидах 11 опубликованных сайтов, должно быть 10
    await expect(items).toHaveCount(10);
  });

  test('HOME-06: Формат элемента списка — название (ссылка), раздел, дата', async ({ page }) => {
    const firstItem = page.locator('.recent-sites .site-list-item').first();

    // Название со ссылкой на /site/{slug}
    const nameLink = firstItem.locator('a[href^="/site/"]').first();
    await expect(nameLink).toBeVisible();

    // Раздел со ссылкой
    const sectionLink = firstItem.locator('.site-meta a[href^="/section/"]');
    await expect(sectionLink).toBeVisible();

    // Дата в формате ДД.ММ.ГГГГ
    const dateText = await firstItem.locator('.site-date').textContent();
    expect(dateText).toMatch(/\d{2}\.\d{2}\.\d{4}/);
  });

  test('HOME-07: Хлебных крошек нет на главной', async ({ page }) => {
    await expect(page.locator('nav[aria-label="breadcrumb"]')).not.toBeVisible();
  });
});
