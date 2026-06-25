-- ============================================================
-- Тестовые данные для Playwright-тестов
-- Загружается перед запуском тестов
-- ============================================================

-- Очистка
DELETE FROM sites;
DELETE FROM sections;

-- Сброс последовательностей
ALTER SEQUENCE sites_id_seq RESTART WITH 1;
ALTER SEQUENCE sections_id_seq RESTART WITH 1;

-- ============================================================
-- РАЗДЕЛЫ (трёхуровневое дерево)
-- ============================================================

-- Уровень 1: Корневые разделы
INSERT INTO sections (id, parent_id, path, name, slug, description, created_at, updated_at)
VALUES
  (1, NULL, '1', 'Дом и интерьер', 'dom-i-interer',
   'Всё для дома: мебель, декор, текстиль, освещение, ремонт и отделка.',
   NOW(), NOW()),
  (2, NULL, '2', 'Сад и огород', 'sad-i-ogorod',
   'Товары и услуги для садоводов и огородников. Семена, растения, инструменты, ландшафтный дизайн, теплицы.',
   NOW(), NOW()),
  (3, NULL, '3', 'Кулинария', 'kulinariya',
   'Рецепты, посуда, техника для кухни, напитки, этикет и сервировка.',
   NOW(), NOW()),
  (4, NULL, '4', 'Дети и развитие', 'deti-i-razvitie',
   'Игрушки, детская комната, образование, одежда и обувь для детей.',
   NOW(), NOW()),
  (5, NULL, '5', 'Здоровье и спорт', 'zdorove-i-sport',
   'Фитнес и тренировки, правильное питание, медицина, спортивный инвентарь.',
   NOW(), NOW());

-- Уровень 2: Подразделы (Дом и интерьер)
INSERT INTO sections (id, parent_id, path, name, slug, description, created_at, updated_at)
VALUES
  (6, 1, '1/6', 'Мебель', 'mebel',
   'Диваны, шкафы, кровати, кухонные гарнитуры, стулья.',
   NOW(), NOW()),
  (7, 1, '1/7', 'Декор', 'dekor',
   'Картины, вазы, зеркала, свечи, искусственные растения.',
   NOW(), NOW()),
  (8, 1, '1/8', 'Текстиль', 'tekstil',
   'Шторы, покрывала, подушки, пледы, скатерти.',
   NOW(), NOW());

-- Уровень 2: Подразделы (Сад и огород)
INSERT INTO sections (id, parent_id, path, name, slug, description, created_at, updated_at)
VALUES
  (9, 2, '2/9', 'Растения', 'rasteniya',
   'Комнатные цветы, саженцы деревьев, семена овощей, газонная трава.',
   NOW(), NOW()),
  (10, 2, '2/10', 'Инструменты', 'instrumenty',
   'Лопаты, триммеры, газонокосилки, секаторы, опрыскиватели.',
   NOW(), NOW());

-- Уровень 2: Подразделы (Кулинария)
INSERT INTO sections (id, parent_id, path, name, slug, description, created_at, updated_at)
VALUES
  (11, 3, '3/11', 'Рецепты', 'recepty',
   'Салаты, супы, выпечка, вторые блюда, десерты, заготовки на зиму.',
   NOW(), NOW()),
  (12, 3, '3/12', 'Посуда и техника', 'posuda-i-tehnika',
   'Сковороды, кастрюли, мультиварки, блендеры, хлебопечки.',
   NOW(), NOW());

-- Уровень 3: Подподраздел (Мебель → Кухонные гарнитуры)
INSERT INTO sections (id, parent_id, path, name, slug, description, created_at, updated_at)
VALUES
  (13, 6, '1/6/13', 'Кухонные гарнитуры', 'kuhonnye-garnitury',
   'Готовые гарнитуры и изготовление на заказ. Итальянские и немецкие фасады.',
   NOW(), NOW());

-- ============================================================
-- САЙТЫ
-- ============================================================

-- Опубликованные сайты (status = 1)
INSERT INTO sites (id, section_id, name, slug, url, description, email, status, created_at, moderated_at)
VALUES
  (1, 6, 'МебельПро', 'mebelpro',
   'https://mebelpro.ru',
   'Интернет-магазин мебели. Доставка по всей России. Более 5000 моделей.',
   'info@mebelpro.ru', 1,
   NOW() - INTERVAL '5 days', NOW()),

  (2, 6, 'ДиванЛаб', 'divanlab',
   'https://divanlab.ru',
   'Производство диванов на заказ. Любые размеры и ткани. Гарантия 5 лет.',
   NULL, 1,
   NOW() - INTERVAL '4 days', NOW()),

  (3, 7, 'ДекорХаус', 'dekorhaus',
   'https://dekorhaus.ru',
   'Предметы декора ручной работы. Картины, вазы, свечи. Уникальный дизайн.',
   NULL, 1,
   NOW() - INTERVAL '3 days', NOW()),

  (4, 9, 'РастенияОнлайн', 'rasteniya-onlajn',
   'https://plants-online.ru',
   'Доставка комнатных растений. От фиалок до пальм. Горшки и удобрения.',
   'shop@plants-online.ru', 1,
   NOW() - INTERVAL '2 days', NOW()),

  (5, 9, 'СадМаркет', 'sadmarket',
   'https://sadmarket.ru',
   'Саженцы, семена, удобрения. Всё для сада и огорода оптом и в розницу.',
   NULL, 1,
   NOW() - INTERVAL '1 day', NOW()),

  (6, 11, 'КулинарнаяКнига', 'kulinarnaya-kniga',
   'https://cookbook.ru',
   'Рецепты на каждый день. Салаты, супы, горячее, десерты. Более 10000 рецептов.',
   'admin@cookbook.ru', 1,
   NOW(), NOW()),

  (7, 12, 'ТехноКухня', 'tehnokuhnya',
   'https://tehnokuhnya.ru',
   'Обзоры кухонной техники. Мультиварки, блендеры, хлебопечки. Сравнение и цены.',
   NULL, 1,
   NOW(), NOW()),

  (8, 13, 'КухниПремиум', 'kuhni-premium',
   'https://kuhni-premium.ru',
   'Кухонные гарнитуры премиум-класса. Итальянские фасады, немецкая фурнитура.',
   NULL, 1,
   NOW(), NOW()),

  (9, 8, 'ТекстильДом', 'tekstil-dom',
   'https://tekstil-dom.ru',
   'Шторы, покрывала, подушки. Текстиль для дома оптом. Доставка по РФ.',
   NULL, 1,
   NOW(), NOW()),

  (10, 10, 'ИнструментБокс', 'instrumentboks',
   'https://instrumentbox.ru',
   'Садовый инструмент. Лопаты, грабли, секаторы, триммеры. Профессиональный инвентарь.',
   NULL, 1,
   NOW(), NOW()),

  (11, 10, 'ГазонокосилкаПро', 'gazonokosilka-pro',
   'https://gazonopro.ru',
   'Газонокосилки и триммеры. Продажа, ремонт и обслуживание. Все бренды.',
   NULL, 1,
   NOW(), NOW());

-- Сайты на модерации (status = 0)
INSERT INTO sites (id, section_id, name, slug, url, description, email, status, created_at)
VALUES
  (12, 6, 'СайтНаМодерации', 'sajt-na-moderacii',
   'https://moderation-test.ru',
   'Описание сайта на модерации. Содержит важную информацию. Ждёт проверки модератором.',
   'author@test.ru', 0,
   NOW()),

  (13, 9, 'ЕщёОдинСайт', 'eshyo-odin-sajt',
   'https://another-test.ru',
   'Ещё одно описание сайта. Тоже ждёт модерацию. Без указания email автора.',
   NULL, 0,
   NOW()),

  (14, 6, 'ТретийСайтНаМодерации', 'tretij-sajt-na-moderacii',
   'https://third-moderation.ru',
   'Третий сайт для тестов модерации. Можно редактировать перед публикацией.',
   'third@test.ru', 0,
   NOW());

-- Отклонённый сайт (status = 2)
INSERT INTO sites (id, section_id, name, slug, url, description, email, status, created_at, moderated_at)
VALUES
  (15, 6, 'ОтклонённыйСайт', 'otklonjonnyj-sajt',
   'https://rejected-test.ru',
   'Этот сайт был отклонён модератором и не должен показываться в каталоге.',
   'bad@test.ru', 2,
   NOW() - INTERVAL '1 day', NOW());


-- ============================================
-- Обновление последовательностей после вставок с явными ID
-- (без этого новые INSERT без ID падают с duplicate key)
-- ============================================
SELECT setval('sites_id_seq', (SELECT MAX(id) FROM sites));
SELECT setval('sections_id_seq', (SELECT MAX(id) FROM sections));


-- ============================================
-- Администратор по умолчанию
-- Логин: admin / Пароль: password
-- ВНИМАНИЕ: смените пароль после первого входа!
-- Хеш сгенерирован: password_hash('password', PASSWORD_BCRYPT)
-- ============================================

DELETE FROM users;
ALTER SEQUENCE users_id_seq RESTART WITH 1;

INSERT INTO users (email, username, password, active) VALUES
('admin@homecatalog.ru', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1)
ON CONFLICT (email) DO NOTHING;


