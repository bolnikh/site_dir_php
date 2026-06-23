-- ============================================
-- Каталог сайтов — начальная миграция
-- ============================================

BEGIN;

-- ============================================
-- Таблица разделов (многоуровневая)
-- ============================================
CREATE TABLE IF NOT EXISTS sections (
    id          SERIAL PRIMARY KEY,
    parent_id   INT          DEFAULT NULL,
    path        VARCHAR(512) DEFAULT NULL,
    name        VARCHAR(512) NOT NULL,
    slug        VARCHAR(255) NOT NULL UNIQUE,
    description TEXT         DEFAULT '',
    created_at  TIMESTAMP    DEFAULT NOW(),
    updated_at  TIMESTAMP    DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_sections_parent_id ON sections (parent_id);
CREATE INDEX IF NOT EXISTS idx_sections_path     ON sections (path);
CREATE INDEX IF NOT EXISTS idx_sections_slug     ON sections (slug);

-- ============================================
-- Таблица сайтов
-- ============================================
CREATE TABLE IF NOT EXISTS sites (
    id           SERIAL PRIMARY KEY,
    section_id   INT          NOT NULL,
    name         VARCHAR(512) NOT NULL,
    slug         VARCHAR(255) NOT NULL UNIQUE,
    url          VARCHAR(512) NOT NULL,
    description  TEXT         DEFAULT '',
    email        VARCHAR(255) DEFAULT NULL,
    status       SMALLINT     DEFAULT 0,   -- 0=модерация, 1=принят, 2=отклонён
    created_at   TIMESTAMP    DEFAULT NOW(),
    moderated_at TIMESTAMP    DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS idx_sites_section_id ON sites (section_id);
CREATE INDEX IF NOT EXISTS idx_sites_slug       ON sites (slug);
CREATE INDEX IF NOT EXISTS idx_sites_status     ON sites (status);

-- ============================================
-- Таблица пользователей (модераторы)
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id         SERIAL PRIMARY KEY,
    email      VARCHAR(255) UNIQUE NOT NULL,
    username   VARCHAR(255) NOT NULL,
    password   VARCHAR(255) NOT NULL,  -- хеш пароля (bcrypt)
    active     SMALLINT     DEFAULT 1,
    created_at TIMESTAMP    DEFAULT NOW(),
    updated_at TIMESTAMP    DEFAULT NOW()
);

-- ============================================
-- Начальные данные: разделы каталога
-- ============================================

-- 1. Дом и интерьер
INSERT INTO sections (id, parent_id, path, name, slug, description) VALUES
(1, NULL, '1', 'Дом и интерьер', 'dom-i-interyer',
 'Товары и услуги для дома: мебель, декор, текстиль, освещение, ремонт и отделка.');

INSERT INTO sections (id, parent_id, path, name, slug, description) VALUES
(2,  1, '1/2',  'Мебель',          'mebel',           'Диваны, шкафы, кровати, кухонные гарнитуры, стулья.'),
(3,  1, '1/3',  'Декор',           'dekor',           'Картины, вазы, зеркала, свечи, искусственные растения.'),
(4,  1, '1/4',  'Текстиль',        'tekstil',         'Шторы, покрывала, подушки, пледы, скатерти.'),
(5,  1, '1/5',  'Освещение',       'osveshchenie',    'Люстры, торшеры, бра, светодиодные ленты, ночники.'),
(6,  1, '1/6',  'Ремонт и отделка', 'remont-i-otdelka', 'Краски, обои, плитка, ламинат, сантехника.'),
(7,  1, '1/7',  'Другое',          'dom-drugoe',      'Прочие сайты по дому и интерьеру.');

-- 2. Сад и огород
INSERT INTO sections (id, parent_id, path, name, slug, description) VALUES
(8,  NULL, '8',  'Сад и огород', 'sad-i-ogorod',
 'Товары и услуги для садоводов и огородников. Семена, растения, инструменты, ландшафтный дизайн, теплицы.');

INSERT INTO sections (id, parent_id, path, name, slug, description) VALUES
(9,  8, '8/9',  'Растения',             'rasteniya',          'Комнатные цветы, саженцы деревьев, семена овощей, газонная трава.'),
(10, 8, '8/10', 'Инструменты',           'instrumenty',        'Лопаты, триммеры, газонокосилки, секаторы, опрыскиватели.'),
(11, 8, '8/11', 'Ландшафтный дизайн',    'landshaftnyj-dizajn', 'Проекты участков, дренаж, дорожки, искусственные пруды.'),
(12, 8, '8/12', 'Теплицы и парники',     'teplicy-i-parniki',  'Поликарбонат, плёнка, стеллажи, системы полива.'),
(13, 8, '8/13', 'Другое',                'sad-drugoe',         'Прочие сайты по саду и огороду.');

-- 3. Кулинария
INSERT INTO sections (id, parent_id, path, name, slug, description) VALUES
(14, NULL, '14', 'Кулинария', 'kulinariya',
 'Рецепты, посуда и техника, напитки, этикет и сервировка.');

INSERT INTO sections (id, parent_id, path, name, slug, description) VALUES
(15, 14, '14/15', 'Рецепты',             'recepty',             'Салаты, супы, выпечка, вторые блюда, десерты, заготовки на зиму.'),
(16, 14, '14/16', 'Посуда и техника',    'posuda-i-tehnika',    'Сковороды, кастрюли, мультиварки, блендеры, хлебопечки.'),
(17, 14, '14/17', 'Напитки',             'napitki',             'Кофе, чай, коктейли, компоты, домашние лимонады.'),
(18, 14, '14/18', 'Этикет и сервировка', 'etiket-i-servirovka', 'Скатерти, салфетки, украшение блюд, правила подачи.'),
(19, 14, '14/19', 'Другое',              'kulinariya-drugoe',   'Прочие сайты по кулинарии.');

-- 4. Дети и развитие
INSERT INTO sections (id, parent_id, path, name, slug, description) VALUES
(20, NULL, '20', 'Дети и развитие', 'deti-i-razvitie',
 'Игрушки, детская комната, образование, одежда и обувь для детей.');

INSERT INTO sections (id, parent_id, path, name, slug, description) VALUES
(21, 20, '20/21', 'Игрушки',          'igrushki',          'Конструкторы, куклы, мягкие игрушки, настольные игры, машинки.'),
(22, 20, '20/22', 'Детская комната',  'detskaya-komnata',   'Кроватки, столы, шкафы, коврики, системы хранения.'),
(23, 20, '20/23', 'Образование',      'obrazovanie',        'Кружки, онлайн-школы, учебные пособия, развивашки для малышей.'),
(24, 20, '20/24', 'Одежда и обувь',   'odezhda-i-obuv',     'От 0 до 3 лет, от 3 до 7 лет, от 7 до 12, для подростков.'),
(25, 20, '20/25', 'Другое',           'deti-drugoe',        'Прочие сайты по детям и развитию.');

-- 5. Здоровье и спорт
INSERT INTO sections (id, parent_id, path, name, slug, description) VALUES
(26, NULL, '26', 'Здоровье и спорт', 'zdorove-i-sport',
 'Фитнес и тренировки, правильное питание, медицина, спортивный инвентарь.');

INSERT INTO sections (id, parent_id, path, name, slug, description) VALUES
(27, 26, '26/27', 'Фитнес и тренировки',  'fitnes-i-trenirovki',  'Упражнения дома, йога, кардио, программы для похудения.'),
(28, 26, '26/28', 'Правильное питание',   'pravilnoe-pitanie',    'Рецепты ПП, счётчики калорий, БЖУ, доставка здоровой еды.'),
(29, 26, '26/29', 'Медицина',             'medicina',             'Клиники, врачи, диагностика, стоматология, аптеки.'),
(30, 26, '26/30', 'Инвентарь',           'inventar',             'Гантели, коврики, велотренажёры, массажёры, фитнес-браслеты.'),
(31, 26, '26/31', 'Другое',              'sport-drugoe',         'Прочие сайты по здоровью и спорту.');

-- Сброс автоинкремента после ручного указания ID
SELECT setval('sections_id_seq', 31, true);

-- ============================================
-- Начальные данные: модератор (по умолчанию)
-- Логин: admin / Пароль: password
-- ВНИМАНИЕ: смените пароль после первого входа!
-- Хеш сгенерирован через password_hash('password', PASSWORD_BCRYPT)
-- ============================================
INSERT INTO users (email, username, password, active) VALUES
('admin@homecatalog.ru', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

COMMIT;
