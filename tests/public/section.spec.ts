/**
 * Страница раздела — заголовок, описание, подразделы, список сайтов, пагинация.
 */
import { test, expect } from '@playwright/test';
import { assertNoPhpErrors, assertBreadcrumbs } from '../fixtures/test-helpers';

test.describe('Страница раздела', () => {
  test('SEC-01: Заголовок и описание раздела', async ({ page }) => {
    await page.goto('/section/dom-i-interer');
    await assertNoPhpErrors(page);
    await expect(page.locator('h2')).toContainText('Дом и интерьер');
    await expect(page.locator('body')).toContainText('Всё для дома');
  });

  test('SEC-02: Подразделы отображаются', async ({ page }) => {
    await page.goto('/section/dom-i-interer');
    const badges = page.locator('.subsection-badges a');
    // Должны быть Мебель, Декор, Текстиль
    await expect(badges).toHaveCount(3);
    await expect(badges.nth(0)).toContainText('Декор');
    await expect(badges.nth(1)).toContainText('Мебель');
    await expect(badges.nth(2)).toContainText('Текстиль');
  });

  test('SEC-03: Подразделы являются ссылками', async ({ page }) => {
    await page.goto('/section/dom-i-interer');
    const mebelLink = page.locator('.subsection-badges a:has-text("Мебель")');
    await expect(mebelLink).toHaveAttribute('href', '/section/mebel');
  });

  test('SEC-04: Конечный раздел не показывает блок подразделов', async ({ page }) => {
    await page.goto('/section/mebel'); // У Мебели есть дочерний «Кухонные гарнитуры»
    // На странице «Мебель» подраздел «Кухонные гарнитуры» должен быть
    await expect(page.locator('.subsection-badges a')).toHaveCount(1);

    // А у «Кухонные гарнитуры» нет дочерних
    await page.goto('/section/kuhonnye-garnitury');
    await expect(page.locator('.subsection-badges')).not.toBeVisible();
  });

  test('SEC-05: Счётчик сайтов', async ({ page }) => {
    await page.goto('/section/dom-i-interer');
    // В разделе «Дом и интерьер» (id=1) + дочерние: Мебель(3 сайта), Декор(1), Текстиль(1), Кух.гарнитуры(1) = 6
    // Плюс может быть в самом корневом разделе
    const counter = page.locator('text=Сайтов в разделе:');
    await expect(counter).toBeVisible();
  });

  test('SEC-06: Сайты из дочерних разделов включаются', async ({ page }) => {
    await page.goto('/section/dom-i-interer');
    // МебельПро (раздел Мебель id=6) должен быть виден
    await expect(page.locator('.site-list')).toContainText('МебельПро');
    // ДекорХаус (раздел Декор id=7) должен быть виден
    await expect(page.locator('.site-list')).toContainText('ДекорХаус');
    // КухниПремиум (раздел Кухонные гарнитуры id=13) должен быть виден
    await expect(page.locator('.site-list')).toContainText('КухниПремиум');
  });

  test('SEC-07: Формат элемента списка сайтов', async ({ page }) => {
    await page.goto('/section/mebel');
    const firstItem = page.locator('.site-list-item').first();

    // Название — ссылка на /site/{slug}
    const siteLink = firstItem.locator('a[href^="/site/"]');
    await expect(siteLink).toBeVisible();

    // URL сайта
    await expect(firstItem.locator('.site-url')).toBeVisible();

    // Дата
    const urlText = await firstItem.locator('.site-url').textContent();
    expect(urlText).toMatch(/\d{2}\.\d{2}\.\d{4}/);
  });

  test('SEC-08: Хлебные крошки на странице раздела', async ({ page }) => {
    await page.goto('/section/rasteniya');
    await assertBreadcrumbs(page, [
      'Начало',
      'link:Сад и огород',
      'Растения',
    ]);
  });

  test('SEC-09: Пустой раздел — сообщение', async ({ page }) => {
    // Раздел «Дети и развитие» (id=4) не имеет сайтов
    await page.goto('/section/deti-i-razvitie');
    await expect(page.locator('body')).toContainText('В этом разделе пока нет сайтов');
  });

  test('SEC-10: Некорректный page не ломает страницу', async ({ page }) => {
    // pg=0, pg=-1, pg=abc должны показать страницу 1
    await page.goto('/section/dom-i-interer?pg=0');
    await assertNoPhpErrors(page);

    await page.goto('/section/dom-i-interer?pg=-1');
    await assertNoPhpErrors(page);

    await page.goto('/section/dom-i-interer?pg=abc');
    await assertNoPhpErrors(page);
  });
});
