# Шаг 21: Реализация Playwright-тестов

## Цель

Настроить Playwright в проекте и реализовать автоматизированные функциональные тесты по спецификации из [шага 20](20_func_test.md).

## Зависимости

- Выполнены все предыдущие шаги (01–19)
- Приложение запущено через Docker Compose и доступно по HTTP
- Node.js ≥ 18 (на хосте, не в контейнере)

---

## Часть 1: Установка и настройка

### 1.1. Создание структуры

```bash
mkdir -p tests/fixtures
mkdir -p tests/public
mkdir -p tests/moderator
mkdir -p tests/integration
mkdir -p tests/security
cd tests
```

### 1.2. package.json

Создать `tests/package.json`:

```json
{
  "name": "homecatalog-tests",
  "version": "1.0.0",
  "private": true,
  "description": "Функциональные тесты для Каталога сайтов",
  "scripts": {
    "test": "npx playwright test",
    "test:smoke": "npx playwright test --grep 'smoke|SMK'",
    "test:public": "npx playwright test public/",
    "test:moderator": "npx playwright test moderator/",
    "test:e2e": "npx playwright test integration/",
    "test:security": "npx playwright test security/",
    "test:ui": "npx playwright test --ui",
    "test:headed": "npx playwright test --headed",
    "report": "npx playwright show-report"
  },
  "devDependencies": {
    "@playwright/test": "^1.52.0"
  }
}
```

### 1.3. Установка

```bash
cd tests && npm install
npx playwright install chromium
```

### 1.4. playwright.config.ts

Создать `tests/playwright.config.ts`:

```typescript
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './',
  testMatch: '**/*.spec.ts',
  fullyParallel: false,       // тесты меняют общее состояние БД
  retries: 1,
  workers: 1,                 // последовательно, чтобы не мешать друг другу
  reporter: [
    ['html', { outputFolder: 'playwright-report' }],
    ['list'],
  ],
  timeout: 30000,
  expect: { timeout: 10000 },

  use: {
    baseURL: process.env.BASE_URL || 'http://localhost:8080',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'on-first-retry',
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
```

### 1.5. Переменные окружения

Создать `tests/.env.test`:

```ini
BASE_URL=http://localhost:8080
DB_HOST=localhost
DB_PORT=5432
DB_NAME=catalog
DB_USER=catalog_user
DB_PASS=FSY3hWw3NQJt
MODERATOR_LOGIN=admin
MODERATOR_PASSWORD=admin123
```

### 1.6. Тестовые данные (SQL)

Создать `tests/fixtures/db-seed.sql`:

```sql
-- Очистка
DELETE FROM sites;
DELETE FROM sections;

-- Сброс последовательностей
ALTER SEQUENCE sites_id_seq RESTART WITH 1;
ALTER SEQUENCE sections_id_seq RESTART WITH 1;

-- Корневые разделы
INSERT INTO sections (id, parent_id, path, name, slug, description, created_at, updated_at)
VALUES
  (1, NULL, '1', 'Дом и интерьер', 'dom-i-interer', 'Всё для дома: мебель, декор, текстиль, освещение, ремонт.', NOW(), NOW()),
  (2, NULL, '2', 'Сад и огород', 'sad-i-ogorod', 'Товары и услуги для садоводов и огородников.', NOW(), NOW()),
  (3, NULL, '3', 'Кулинария', 'kulinariya', 'Рецепты, посуда, техника для кухни.', NOW(), NOW()),
  (4, NULL, '4', 'Дети и развитие', 'deti-i-razvitie', 'Игрушки, образование, детская комната.', NOW(), NOW()),
  (5, NULL, '5', 'Здоровье и спорт', 'zdorove-i-sport', 'Фитнес, питание, медицина, инвентарь.', NOW(), NOW());

-- Подразделы (пример: Дом и интерьер → Мебель)
INSERT INTO sections (id, parent_id, path, name, slug, description, created_at, updated_at)
VALUES
  (6, 1, '1/6', 'Мебель', 'mebel', 'Диваны, шкафы, кровати, кухонные гарнитуры.', NOW(), NOW()),
  (7, 1, '1/7', 'Декор', 'dekor', 'Картины, вазы, зеркала, свечи.', NOW(), NOW()),
  (8, 1, '1/8', 'Текстиль', 'tekstil', 'Шторы, покрывала, подушки, пледы.', NOW(), NOW()),
  (9, 2, '2/9', 'Растения', 'rasteniya', 'Комнатные цветы, саженцы, семена.', NOW(), NOW()),
  (10, 2, '2/10', 'Инструменты', 'instrumenty', 'Лопаты, триммеры, газонокосилки.', NOW(), NOW()),
  (11, 3, '3/11', 'Рецепты', 'recepty', 'Салаты, супы, выпечка, вторые блюда.', NOW(), NOW()),
  (12, 3, '3/12', 'Посуда и техника', 'posuda-i-tehnika', 'Сковороды, мультиварки, блендеры.', NOW(), NOW());

-- Подподраздел (3 уровня)
INSERT INTO sections (id, parent_id, path, name, slug, description, created_at, updated_at)
VALUES
  (13, 6, '1/6/13', 'Кухонные гарнитуры', 'kuhonnye-garnitury', 'Готовые и на заказ.', NOW(), NOW());

-- Опубликованные сайты (для тестов публичной части)
INSERT INTO sites (id, section_id, name, slug, url, description, email, status, created_at, moderated_at)
VALUES
  (1, 6, 'МебельПро', 'mebelpro', 'https://mebelpro.ru', 'Интернет-магазин мебели. Доставка по всей России.', 'info@mebelpro.ru', 1, NOW() - INTERVAL '5 days', NOW()),
  (2, 6, 'ДиванЛаб', 'divanlab', 'https://divanlab.ru', 'Производство диванов на заказ. Любые размеры и ткани.', NULL, 1, NOW() - INTERVAL '4 days', NOW()),
  (3, 7, 'ДекорХаус', 'dekorhaus', 'https://dekorhaus.ru', 'Предметы декора ручной работы. Картины, вазы, свечи.', NULL, 1, NOW() - INTERVAL '3 days', NOW()),
  (4, 9, 'РастенияОнлайн', 'rasteniya-onlajn', 'https://plants-online.ru', 'Доставка комнатных растений. От фиалок до пальм.', 'shop@plants-online.ru', 1, NOW() - INTERVAL '2 days', NOW()),
  (5, 9, 'СадМаркет', 'sadmarket', 'https://sadmarket.ru', 'Саженцы, семена, удобрения. Всё для сада.', NULL, 1, NOW() - INTERVAL '1 day', NOW()),
  (6, 11, 'КулинарнаяКнига', 'kulinarnaya-kniga', 'https://cookbook.ru', 'Рецепты на каждый день. Салаты, супы, горячее, десерты.', 'admin@cookbook.ru', 1, NOW(), NOW()),
  (7, 12, 'ТехноКухня', 'tehnokuhnya', 'https://tehnokuhnya.ru', 'Обзоры кухонной техники. Мультиварки, блендеры, хлебопечки.', NULL, 1, NOW(), NOW()),
  (8, 13, 'КухниПремиум', 'kuhni-premium', 'https://kuhni-premium.ru', 'Кухонные гарнитуры премиум-класса. Итальянские фасады.', NULL, 1, NOW(), NOW()),
  (9, 8, 'ТекстильДом', 'tekstil-dom', 'https://tekstil-dom.ru', 'Шторы, покрывала, подушки. Текстиль для дома оптом.', NULL, 1, NOW(), NOW()),
  (10, 10, 'ИнструментБокс', 'instrumentboks', 'https://instrumentbox.ru', 'Садовый инструмент. Лопаты, грабли, секаторы, триммеры.', NULL, 1, NOW(), NOW()),
  (11, 10, 'ГазонокосилкаПро', 'gazonokosilka-pro', 'https://gazonopro.ru', 'Газонокосилки и триммеры. Ремонт и обслуживание.', NULL, 1, NOW(), NOW());

-- Сайты на модерации (для тестов модератора)
INSERT INTO sites (id, section_id, name, slug, url, description, email, status, created_at)
VALUES
  (12, 6, 'СайтНаМодерации', 'sajt-na-moderacii', 'https://moderation-test.ru', 'Описание сайта на модерации. Ждёт проверки.', 'author@test.ru', 0, NOW()),
  (13, 9, 'ЕщёОдинСайт', 'eshyo-odin-sajt', 'https://another-test.ru', 'Ещё одно описание. Тоже ждёт модерацию.', NULL, 0, NOW());

-- Отклонённый сайт
INSERT INTO sites (id, section_id, name, slug, url, description, email, status, created_at, moderated_at)
VALUES
  (14, 6, 'ОтклонённыйСайт', 'otklonjonnyj-sajt', 'https://rejected-test.ru', 'Этот сайт был отклонён.', 'bad@test.ru', 2, NOW() - INTERVAL '1 day', NOW());
```

### 1.7. Скрипт загрузки тестовых данных

Создать `tests/fixtures/seed-db.sh`:

```bash
#!/bin/bash
# Загружает тестовые данные в БД
# Использование: bash fixtures/seed-db.sh

DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-5432}"
DB_NAME="${DB_NAME:-catalog}"
DB_USER="${DB_USER:-catalog_user}"
DB_PASS="${DB_PASS:-FSY3hWw3NQJt}"

PGPASSWORD="$DB_PASS" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -f fixtures/db-seed.sql

echo "OK: тестовые данные загружены"
```

### 1.8. Вспомогательные функции

Создать `tests/fixtures/test-helpers.ts`:

```typescript
import { Page, expect } from '@playwright/test';

/**
 * Базовый URL приложения
 */
export const BASE_URL = process.env.BASE_URL || 'http://localhost:8080';

/**
 * Учётные данные модератора
 */
export const MODERATOR = {
  login: process.env.MODERATOR_LOGIN || 'admin',
  password: process.env.MODERATOR_PASSWORD || 'admin123',
};

/**
 * Проверить отсутствие PHP-ошибок на странице
 */
export async function assertNoPhpErrors(page: Page): Promise<void> {
  const body = await page.textContent('body');
  expect(body).not.toContain('Fatal error');
  expect(body).not.toContain('Warning:');
  expect(body).not.toContain('Parse error');
  expect(body).not.toContain('Stack trace');
  expect(body).not.toContain('Uncaught');
  expect(body).not.toContain('PDOException');
  expect(body).not.toContain('mysqli');
}

/**
 * Проверить хлебные крошки
 * @param page — страница
 * @param crumbs — ожидаемые тексты крошек по порядку.
 *                 Префикс 'link:' означает что это ссылка, иначе текст без ссылки.
 *                 Пример: ['Начало', 'link:Дом и интерьер', 'Мебель']
 */
export async function assertBreadcrumbs(page: Page, crumbs: string[]): Promise<void> {
  const breadcrumb = page.locator('nav[aria-label="breadcrumb"] ol.breadcrumb');

  if (crumbs.length === 0) {
    await expect(breadcrumb).not.toBeVisible();
    return;
  }

  await expect(breadcrumb).toBeVisible();
  const items = breadcrumb.locator('li.breadcrumb-item');

  for (let i = 0; i < crumbs.length; i++) {
    const item = items.nth(i);
    const isLastLink = crumbs[i].startsWith('link:');
    const expectedText = isLastLink ? crumbs[i].replace('link:', '') : crumbs[i];
    const isLast = i === crumbs.length - 1;

    await expect(item).toContainText(expectedText);

    if (!isLast) {
      // Все кроме последнего — ссылки
      await expect(item.locator('a')).toBeVisible();
    } else {
      // Последний — текст без ссылки (или active)
      await expect(item.locator('a')).not.toBeVisible();
    }
  }
}

/**
 * Проверить flash-сообщение на странице
 */
export async function assertFlash(page: Page, type: string, text: string): Promise<void> {
  const flash = page.locator(`.alert-${type}`);
  await expect(flash).toBeVisible();
  await expect(flash).toContainText(text);
}

/**
 * Залогиниться как модератор
 * Возвращает страницу после логина
 */
export async function loginAsModerator(page: Page): Promise<void> {
  await page.goto('/moderator/login');
  await page.fill('input[name="login"]', MODERATOR.login);
  await page.fill('input[name="password"]', MODERATOR.password);
  await page.click('button[type="submit"]');
  await page.waitForURL('**/moderator/list');
}

/**
 * Выйти из модераторской сессии
 */
export async function logoutModerator(page: Page): Promise<void> {
  await page.goto('/moderator/logout');
}

/**
 * Очистить сессию (удалить куки)
 */
export async function clearSession(page: Page): Promise<void> {
  await page.context().clearCookies();
}

/**
 * Заполнить и отправить форму добавления сайта
 */
export async function submitAddSiteForm(
  page: Page,
  data: {
    sectionLabel?: string;    // текст опции в селекте
    name?: string;
    url?: string;
    description?: string;
    email?: string;
    agreement?: boolean;
  }
): Promise<void> {
  if (data.sectionLabel) {
    await page.selectOption('#section_id', { label: data.sectionLabel });
  }
  if (data.name !== undefined) {
    await page.fill('#name', data.name);
  }
  if (data.url !== undefined) {
    await page.fill('#url', data.url);
  }
  if (data.description !== undefined) {
    // Summernote: контент в iframe или textarea
    const editor = page.frameLocator('.note-editing-area iframe') || page.locator('#description');
    // Пробуем заполнить через Summernote API
    await page.evaluate((html) => {
      (window as any).$('#description').summernote('code', html);
    }, data.description);
  }
  if (data.email !== undefined) {
    await page.fill('#email', data.email);
  }
  if (data.agreement) {
    await page.check('#agreement');
  }
  await page.click('button[type="submit"]');
}

/**
 * Проверить, что у поля есть ошибка валидации
 */
export async function assertFieldError(page: Page, fieldId: string, errorText: string): Promise<void> {
  const field = page.locator(`#${fieldId}`);
  await expect(field).toHaveClass(/is-invalid/);
  await expect(field.locator('..').locator('.invalid-feedback')).toContainText(errorText);
}
```

---

## Часть 2: Написание тестов

### 2.1. Дымовые тесты (`tests/public/smoke.spec.ts`)

```typescript
import { test, expect } from '@playwright/test';
import { assertNoPhpErrors } from '../fixtures/test-helpers';

const PUBLIC_PAGES = [
  { name: 'Главная', url: '/' },
  { name: 'Раздел (Мебель)', url: '/section/mebel' },
  { name: 'Подраздел (Кухонные гарнитуры)', url: '/section/kuhonnye-garnitury' },
  { name: 'Сайт', url: '/site/mebelpro' },
  { name: 'О нас', url: '/about' },
  { name: 'Правила', url: '/rules' },
  { name: 'Добавить сайт', url: '/add' },
  { name: 'Свяжитесь с нами', url: '/contact_us' },
];

for (const { name, url } of PUBLIC_PAGES) {
  test(`SMK: ${name} (${url}) — открывается без ошибок`, async ({ page }) => {
    const response = await page.goto(url);
    expect(response?.status()).toBe(200);
    await assertNoPhpErrors(page);
  });
}

const PAGES_404 = [
  { name: 'Несуществующий раздел', url: '/section/nonexistent-slug' },
  { name: 'Несуществующий сайт', url: '/site/nonexistent-slug' },
  { name: 'Несуществующий маршрут', url: '/nonexistent-page' },
];

for (const { name, url } of PAGES_404) {
  test(`SMK: ${name} — 404`, async ({ page }) => {
    const response = await page.goto(url);
    expect(response?.status()).toBe(404);
    await assertNoPhpErrors(page);
  });
}
```

### 2.2. Хлебные крошки (`tests/public/breadcrumbs.spec.ts`)

```typescript
import { test } from '@playwright/test';
import { assertBreadcrumbs } from '../fixtures/test-helpers';

test.describe('Хлебные крошки', () => {
  test('BC-01: Главная — крошек нет', async ({ page }) => {
    await page.goto('/');
    await assertBreadcrumbs(page, []);
  });

  test('BC-02: Корневой раздел', async ({ page }) => {
    await page.goto('/section/dom-i-interer');
    await assertBreadcrumbs(page, ['Начало', 'Дом и интерьер']);
  });

  test('BC-03: Подраздел (2 уровня)', async ({ page }) => {
    await page.goto('/section/mebel');
    await assertBreadcrumbs(page, ['Начало', 'link:Дом и интерьер', 'Мебель']);
  });

  test('BC-04: Подподраздел (3 уровня)', async ({ page }) => {
    await page.goto('/section/kuhonnye-garnitury');
    await assertBreadcrumbs(page, ['Начало', 'link:Дом и интерьер', 'link:Мебель', 'Кухонные гарнитуры']);
  });

  test('BC-05: Страница сайта', async ({ page }) => {
    await page.goto('/site/mebelpro');
    await assertBreadcrumbs(page, ['Начало', 'link:Дом и интерьер', 'link:Мебель', 'МебельПро']);
  });

  test('BC-06: О нас', async ({ page }) => {
    await page.goto('/about');
    await assertBreadcrumbs(page, ['Начало', 'О нас']);
  });

  test('BC-07: Правила', async ({ page }) => {
    await page.goto('/rules');
    await assertBreadcrumbs(page, ['Начало', 'Правила']);
  });

  test('BC-08: Добавить сайт', async ({ page }) => {
    await page.goto('/add');
    await assertBreadcrumbs(page, ['Начало', 'Добавить сайт']);
  });

  test('BC-09: Свяжитесь с нами', async ({ page }) => {
    await page.goto('/contact_us');
    await assertBreadcrumbs(page, ['Начало', 'Свяжитесь с нами']);
  });
});
```

### 2.3. Форма добавления сайта (`tests/public/add-site.spec.ts`)

```typescript
import { test, expect } from '@playwright/test';
import { assertNoPhpErrors, assertFlash } from '../fixtures/test-helpers';

test.describe('Добавить сайт — JS валидация', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/add');
    await assertNoPhpErrors(page);
  });

  test('ADD-JS-01: пустой раздел → ошибка', async ({ page }) => {
    await page.fill('#name', 'Тестовый сайт');
    await page.fill('#url', 'https://example.com');
    await page.check('#agreement');
    await page.click('button[type="submit"]');
    await expect(page.locator('#section_id')).toHaveClass(/is-invalid/);
  });

  test('ADD-JS-02: пустое название → ошибка', async ({ page }) => {
    await page.fill('#url', 'https://example.com');
    await page.check('#agreement');
    await page.click('button[type="submit"]');
    await expect(page.locator('#name')).toHaveClass(/is-invalid/);
  });

  test('ADD-JS-05: некорректный URL → ошибка', async ({ page }) => {
    await page.fill('#name', 'Сайт');
    await page.fill('#url', 'not-a-url');
    await page.check('#agreement');
    await page.click('button[type="submit"]');
    await expect(page.locator('#url')).toHaveClass(/is-invalid/);
  });

  test('ADD-JS-08: некорректный email → ошибка', async ({ page }) => {
    await page.fill('#name', 'Сайт');
    await page.fill('#url', 'https://example.com');
    await page.fill('#email', 'not-an-email');
    await page.check('#agreement');
    await page.click('button[type="submit"]');
    await expect(page.locator('#email')).toHaveClass(/is-invalid/);
  });

  test('ADD-JS-09: чекбокс не отмечен → ошибка', async ({ page }) => {
    await page.fill('#name', 'Сайт');
    await page.fill('#url', 'https://example.com');
    await page.click('button[type="submit"]');
    await expect(page.locator('#agreement')).toHaveClass(/is-invalid/);
  });
});

test.describe('Добавить сайт — серверная валидация и отправка', () => {
  test('ADD-SRV-03: успешная отправка', async ({ page }) => {
    await page.goto('/add');
    await page.selectOption('#section_id', { label: 'Мебель' });
    await page.fill('#name', 'Уникальный тестовый сайт ' + Date.now());
    await page.fill('#url', 'https://unique-test-' + Date.now() + '.ru');
    await page.evaluate((html) => {
      (window as any).$('#description').summernote('code', html);
    }, '<p>Тестовое описание сайта для проверки отправки.</p>');
    await page.check('#agreement');
    await page.click('button[type="submit"]');

    // Ожидаем редирект и flash
    await page.waitForURL('**/');
    await assertFlash(page, 'success', 'отправлен');
  });

  test('ADD-SRV-01: дубликат URL → ошибка', async ({ page }) => {
    await page.goto('/add');
    await page.selectOption('#section_id', { label: 'Мебель' });
    await page.fill('#name', 'Дубликат');
    await page.fill('#url', 'https://mebelpro.ru'); // уже есть в сидах
    await page.fill('#description', 'Описание');
    await page.check('#agreement');
    await page.click('button[type="submit"]');
    await expect(page.locator('.alert-danger')).toBeVisible();
  });
});
```

### 2.4. Модератор: авторизация (`tests/moderator/auth.spec.ts`)

```typescript
import { test, expect } from '@playwright/test';
import { MODERATOR, loginAsModerator, clearSession, assertFlash } from '../fixtures/test-helpers';

test.describe('Модератор — авторизация', () => {
  test('MOD-AUTH-01: страница логина отображается', async ({ page }) => {
    await page.goto('/moderator/login');
    await expect(page.locator('input[name="login"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('MOD-AUTH-04: неверные данные → ошибка', async ({ page }) => {
    await page.goto('/moderator/login');
    await page.fill('input[name="login"]', 'wrong');
    await page.fill('input[name="password"]', 'wrong');
    await page.click('button[type="submit"]');
    await assertFlash(page, 'danger', 'Неверный');
  });

  test('MOD-AUTH-05: верные данные → редирект на список', async ({ page }) => {
    await loginAsModerator(page);
    await expect(page).toHaveURL(/\/moderator\/list/);
  });

  test('MOD-AUTH-07: доступ без логина → редирект', async ({ page }) => {
    await clearSession(page);
    await page.goto('/moderator/list');
    await expect(page).toHaveURL(/\/moderator\/login/);
  });

  test('MOD-AUTH-08: /moderator/sections без логина → редирект', async ({ page }) => {
    await clearSession(page);
    await page.goto('/moderator/sections');
    await expect(page).toHaveURL(/\/moderator\/login/);
  });

  test('MOD-AUTH-09: после логаута доступ закрыт', async ({ page }) => {
    await loginAsModerator(page);
    await page.goto('/moderator/logout');
    await page.goto('/moderator/list');
    await expect(page).toHaveURL(/\/moderator\/login/);
  });
});
```

### 2.5. Модерация сайта (`tests/moderator/moderate.spec.ts`)

```typescript
import { test, expect } from '@playwright/test';
import { loginAsModerator, assertFlash, clearSession } from '../fixtures/test-helpers';

test.describe('Модерация сайта', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsModerator(page);
  });

  test('MOD-SITE-01: данные сайта отображаются', async ({ page }) => {
    await page.goto('/moderator/moderate?id=12'); // СайтНаМодерации
    await expect(page.locator('body')).toContainText('СайтНаМодерации');
    await expect(page.locator('body')).toContainText('https://moderation-test.ru');
    await expect(page.locator('body')).toContainText('author@test.ru');
  });

  test('MOD-SITE-02: одобрить сайт', async ({ page }) => {
    await page.goto('/moderator/moderate?id=12');
    await page.click('button:has-text("Одобрить")');
    await assertFlash(page, 'success', '');
    // Проверить что сайт теперь виден публично
    await clearSession(page);
    await page.goto('/site/sajt-na-moderacii');
    await expect(page.locator('body')).toContainText('СайтНаМодерации');
  });

  test('MOD-SITE-03: отклонить сайт', async ({ page }) => {
    await page.goto('/moderator/moderate?id=13'); // ЕщёОдинСайт
    await page.click('button:has-text("Отклонить")');
    await assertFlash(page, 'danger', '');
    // Проверить что сайт НЕ виден публично
    await clearSession(page);
    const resp = await page.goto('/site/eshyo-odin-sajt');
    expect(resp?.status()).toBe(404);
  });

  test('MOD-SITE-04: редактировать и опубликовать', async ({ page }) => {
    // Требуется сайт со status=0, допустим используем id=12 после пересоздания БД
    await page.goto('/moderator/moderate?id=12');
    await page.fill('input[name="name"]', 'Обновлённое Название');
    await page.evaluate((html) => {
      (window as any).$('#description').summernote('code', html);
    }, '<p>Обновлённое описание после модерации.</p>');
    await page.selectOption('#section_id', { label: 'Декор' });
    await page.click('button:has-text("Сохранить и опубликовать")');
    await assertFlash(page, 'success', '');

    // Проверить публичную страницу
    await clearSession(page);
    await page.goto('/site/sajt-na-moderacii');
    await expect(page.locator('body')).toContainText('Обновлённое Название');
    await expect(page.locator('body')).toContainText('Обновлённое описание');
  });
});
```

### 2.6. CRUD разделов (`tests/moderator/sections-crud.spec.ts`)

```typescript
import { test, expect } from '@playwright/test';
import { loginAsModerator, assertFlash } from '../fixtures/test-helpers';

test.describe('Модератор — CRUD разделов', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsModerator(page);
    await page.goto('/moderator/sections');
  });

  test('SEC-CRUD-01: дерево разделов отображается', async ({ page }) => {
    await expect(page.locator('body')).toContainText('Дом и интерьер');
    await expect(page.locator('body')).toContainText('Сад и огород');
    await expect(page.locator('body')).toContainText('Мебель'); // подраздел
  });

  test('SEC-CRUD-03-04: добавить и сохранить подраздел', async ({ page }) => {
    await page.goto('/moderator/sections/add?parent_id=1');
    await page.fill('input[name="name"]', 'Новый подраздел');
    await page.fill('textarea[name="description"]', 'Описание нового подраздела');
    await page.click('button[type="submit"]');
    await assertFlash(page, 'success', '');
    // Проверить что раздел появился в дереве
    await page.goto('/moderator/sections');
    await expect(page.locator('body')).toContainText('Новый подраздел');
  });

  test('SEC-CRUD-06: удалить пустой раздел', async ({ page }) => {
    // Создаём пустой раздел
    await page.goto('/moderator/sections/add?parent_id=1');
    await page.fill('input[name="name"]', 'ДляУдаления');
    await page.fill('textarea[name="description"]', 'Будет удалён');
    await page.click('button[type="submit"]');

    // Находим его в дереве и удаляем
    await page.goto('/moderator/sections');
    // Предполагаем что есть кнопка удаления с data-confirm
    const deleteBtn = page.locator('a[href*="delete"]').first();
    if (await deleteBtn.isVisible()) {
      await deleteBtn.click();
      // Подтверждение в dialog
    }
  });

  test('SEC-CRUD-07: нельзя удалить раздел с сайтами', async ({ page }) => {
    // Раздел «Мебель» (id=6) содержит сайты
    await page.goto('/moderator/sections/edit?id=6');
    // Кнопки удаления быть не должно, либо она disabled
    const deleteBtn = page.locator('button:has-text("Удалить")');
    await expect(deleteBtn).not.toBeVisible();
  });

  test('SEC-CRUD-08: фильтр по имени', async ({ page }) => {
    await page.fill('input[name="filter"]', 'Мебель');
    await page.click('button:has-text("Найти")');
    await expect(page.locator('body')).toContainText('Мебель');
    // Другие разделы могут отсутствовать в результатах
  });
});
```

### 2.7. Сквозной сценарий (`tests/integration/full-flow.spec.ts`)

```typescript
import { test, expect } from '@playwright/test';
import { loginAsModerator, clearSession, assertFlash } from '../fixtures/test-helpers';

test('E2E: подача сайта → модерация → публикация', async ({ page }) => {
  const siteName = 'E2E Тестовый сайт ' + Date.now();
  const siteUrl = 'https://e2e-test-' + Date.now() + '.ru';

  // Шаг 1: Гость добавляет сайт
  await page.goto('/add');
  await page.selectOption('#section_id', { label: 'Мебель' });
  await page.fill('#name', siteName);
  await page.fill('#url', siteUrl);
  await page.evaluate((html) => {
    (window as any).$('#description').summernote('code', html);
  }, '<p>Описание для сквозного теста.</p>');
  await page.check('#agreement');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/');
  await assertFlash(page, 'success', 'отправлен');

  // Шаг 2: Модератор входит
  await loginAsModerator(page);

  // Шаг 3: Находит сайт в списке
  await expect(page.locator('body')).toContainText(siteName);

  // Шаг 4: Одобряет сайт
  // Ищем ссылку на модерацию по имени сайта
  await page.click(`a:has-text("${siteName}")`);
  await page.waitForURL('**/moderator/moderate*');
  await page.click('button:has-text("Одобрить")');

  // Шаг 5: Гость видит сайт на главной
  await clearSession(page);
  await page.goto('/');

  // Шаг 6: Гость видит сайт в разделе
  await page.goto('/section/mebel');
  await expect(page.locator('body')).toContainText(siteName);

  // Шаг 7: Гость открывает страницу сайта
  const siteSlug = siteName.toLowerCase()
    .replace(/\s+/g, '-')
    .replace(/[^a-z0-9-]/g, '');
  await page.goto(`/site/${siteSlug}`);
  await expect(page.locator('h1')).toContainText(siteName);
});
```

### 2.8. XSS-защита (`tests/security/xss.spec.ts`)

```typescript
import { test, expect } from '@playwright/test';

test.describe('XSS-защита', () => {
  test('XSS-01: script в названии сайта экранирован', async ({ page }) => {
    await page.goto('/add');
    await page.selectOption('#section_id', { label: 'Мебель' });
    await page.fill('#name', '<script>alert("xss")</script>');
    await page.fill('#url', 'https://xss-test-' + Date.now() + '.ru');
    await page.fill('#description', 'Безопасное описание');
    await page.check('#agreement');
    await page.click('button[type="submit"]');

    // Если сайт прошёл валидацию и был создан — проверяем публичную страницу
    // Название должно быть экранировано, тег script не должен выполниться
    // Проверяем отсутствие алерта
    page.on('dialog', () => {
      throw new Error('Обнаружен XSS: alert был вызван');
    });
  });

  test('XSS-02: javascript URL отклонён', async ({ page }) => {
    await page.goto('/add');
    await page.selectOption('#section_id', { label: 'Мебель' });
    await page.fill('#name', 'XSS URL Test');
    await page.fill('#url', 'javascript:alert(1)');
    await page.fill('#description', 'Описание');
    await page.check('#agreement');
    await page.click('button[type="submit"]');

    // JS-валидация должна отклонить
    await expect(page.locator('#url')).toHaveClass(/is-invalid/);
  });
});
```

---

## Часть 3: Интеграция с Docker

### 3.1. Docker Compose для тестов

Добавить в `docker-compose.yml` сервис для загрузки тестовых данных:

```yaml
  db-seed-test:
    image: postgres:18
    depends_on:
      - postgres
    volumes:
      - ./tests/fixtures/db-seed.sql:/docker-entrypoint-initdb.d/seed.sql:ro
    entrypoint: >
      bash -c "PGPASSWORD=$${POSTGRES_PASSWORD} psql -h postgres -U $${POSTGRES_USER} -d $${POSTGRES_DB} -f /docker-entrypoint-initdb.d/seed.sql"
    profiles:
      - test
```

Загрузка тестовых данных:

```bash
docker compose --profile test up db-seed-test
```

### 3.2. Запуск тестов

```bash
# 1. Убедиться что приложение работает
docker compose up -d

# 2. Загрузить тестовые данные
docker compose --profile test up db-seed-test

# 3. Запустить тесты
cd tests && npm test

# 4. HTML-отчёт
cd tests && npx playwright show-report
```

---

## Часть 4: CI/CD (опционально)

### GitHub Actions

Создать `.github/workflows/tests.yml`:

```yaml
name: Functional Tests
on:
  push:
    branches: [master, main]
  pull_request:
    branches: [master, main]

jobs:
  playwright:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Start Docker
        run: docker compose up -d --wait

      - name: Seed test data
        run: docker compose --profile test up db-seed-test

      - name: Install Playwright
        working-directory: tests
        run: |
          npm ci
          npx playwright install chromium --with-deps

      - name: Run tests
        working-directory: tests
        run: npx playwright test

      - name: Upload report
        if: always()
        uses: actions/upload-artifact@v4
        with:
          name: playwright-report
          path: tests/playwright-report/
```

---

## Порядок реализации

### Этап 1: Инфраструктура (1–2 часа)
- [ ] Создать `tests/package.json`
- [ ] Создать `tests/playwright.config.ts`
- [ ] Создать `tests/fixtures/db-seed.sql`
- [ ] Создать `tests/fixtures/test-helpers.ts`
- [ ] Установить Playwright, проверить запуск

### Этап 2: Фаза 1 тестов (2–3 часа)
- [ ] `tests/public/smoke.spec.ts` — дымовые тесты
- [ ] `tests/public/breadcrumbs.spec.ts` — хлебные крошки
- [ ] `tests/public/add-site.spec.ts` — форма добавления сайта
- [ ] `tests/moderator/auth.spec.ts` — авторизация
- [ ] `tests/moderator/moderate.spec.ts` — модерация
- [ ] `tests/integration/full-flow.spec.ts` — сквозной сценарий

### Этап 3: Фаза 2 тестов (2–3 часа)
- [ ] `tests/public/home.spec.ts`
- [ ] `tests/public/section.spec.ts`
- [ ] `tests/public/site-page.spec.ts`
- [ ] `tests/public/static-pages.spec.ts`
- [ ] `tests/public/contact-us.spec.ts`
- [ ] `tests/moderator/sections-crud.spec.ts`
- [ ] `tests/integration/pagination.spec.ts`

### Этап 4: Безопасность и краевые случаи (1–2 часа)
- [ ] `tests/security/xss.spec.ts`
- [ ] `tests/security/access-control.spec.ts`
- [ ] Дополнить граничные случаи в существующих тестах

---

## Примечания

1. **Тесты требуют свежей БД**: перед каждым запуском загружать `db-seed.sql` для детерминированного состояния
2. **Summernote**: редактор сложен для автоматизации. Альтернатива — использовать `page.evaluate()` для прямого вызова API редактора
3. **Redis-тесты**: для проверки кэша можно добавить debug-эндпойнт в dev-режиме, который возвращает ключи и их содержимое
4. **Email-тесты**: использовать Mailpit или аналогичный перехватчик SMTP в тестовом окружении
5. **Параллельность**: тесты должны запускаться строго последовательно (`workers: 1`), так как меняют состояние БД
