# Шаг 3: Создание и миграция базы данных

## Цель

Создать таблицы PostgreSQL согласно схеме данных и заполнить их начальными данными.

## SQL-миграция

### Файл `migrations/001_initial.sql`

```sql
-- Кодировка
SET client_encoding = 'UTF8';
CREATE COLLATION IF NOT EXISTS ru_RU_UTF8 (LOCALE = 'ru_RU.UTF-8');

-- Таблица разделов (sections)
CREATE TABLE IF NOT EXISTS sections (
    id SERIAL PRIMARY KEY,
    parent_id INTEGER REFERENCES sections(id) ON DELETE SET NULL,
    path VARCHAR(512) NOT NULL DEFAULT '',    -- напр. '2/34/156'
    name VARCHAR(512) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT DEFAULT '',
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_sections_parent_id ON sections(parent_id);
CREATE INDEX idx_sections_path ON sections(path);
CREATE INDEX idx_sections_slug ON sections(slug);

-- Таблица сайтов (sites)
CREATE TABLE IF NOT EXISTS sites (
    id SERIAL PRIMARY KEY,
    section_id INTEGER NOT NULL REFERENCES sections(id) ON DELETE RESTRICT,
    name VARCHAR(512) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    url VARCHAR(512) NOT NULL,
    description TEXT DEFAULT '',
    email VARCHAR(255) DEFAULT NULL,
    status SMALLINT NOT NULL DEFAULT 0,  -- 0=на модерации, 1=принят, 2=отклонён
    created_at TIMESTAMP DEFAULT NOW(),
    moderated_at TIMESTAMP DEFAULT NULL
);

CREATE INDEX idx_sites_section_id ON sites(section_id);
CREATE INDEX idx_sites_status ON sites(status);
CREATE INDEX idx_sites_slug ON sites(slug);
CREATE INDEX idx_sites_created_at ON sites(created_at DESC);

-- Таблица пользователей (модераторов)
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,  -- хеш bcrypt
    active SMALLINT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Функция автообновления updated_at
CREATE OR REPLACE FUNCTION update_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Триггер для sections
CREATE TRIGGER trg_sections_updated_at
    BEFORE UPDATE ON sections
    FOR EACH ROW EXECUTE FUNCTION update_timestamp();

-- Триггер для users
CREATE TRIGGER trg_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_timestamp();
```

### Файл `migrations/002_seed_data.sql`

Начальные данные — 5 корневых разделов + подразделы согласно `catalog_structure.md`:

```sql
-- Корневые разделы
INSERT INTO sections (id, parent_id, path, name, slug, description) VALUES
(1, NULL, '1', 'Дом и интерьер', 'dom-i-interier',
 'Товары и услуги для дома. Мебель, декор, текстиль, освещение, ремонт и отделка.'),

(2, NULL, '2', 'Сад и огород', 'sad-i-ogorod',
 'Товары и услуги для садоводов и огородников. Семена, растения, инструменты, ландшафтный дизайн, теплицы.'),

(3, NULL, '3', 'Кулинария', 'kulinariya',
 'Рецепты, посуда, техника для кухни, напитки, этикет и сервировка.'),

(4, NULL, '4', 'Дети и развитие', 'deti-i-razvitie',
 'Игрушки, детская комната, образование, одежда и обувь для детей.'),

(5, NULL, '5', 'Здоровье и спорт', 'zdorovie-i-sport',
 'Фитнес, правильное питание, медицина, спортивный инвентарь.');

-- Подразделы: Дом и интерьер (parent_id=1)
INSERT INTO sections (parent_id, path, name, slug, description) VALUES
(1, '1/6', 'Мебель', 'mebel', 'Диваны, шкафы, кровати, кухонные гарнитуры, стулья.'),
(1, '1/7', 'Декор', 'dekor', 'Картины, вазы, зеркала, свечи, искусственные растения.'),
(1, '1/8', 'Текстиль', 'tekstil', 'Шторы, покрывала, подушки, пледы, скатерти.'),
(1, '1/9', 'Освещение', 'osveschenie', 'Люстры, торшеры, бра, светодиодные ленты, ночники.'),
(1, '1/10', 'Ремонт и отделка', 'remont-i-otdelka', 'Краски, обои, плитка, ламинат, сантехника.'),
(1, '1/11', 'Другое', 'dom-drugoe', 'Прочие сайты по теме дома и интерьера.');

-- Подразделы: Сад и огород (parent_id=2)
INSERT INTO sections (parent_id, path, name, slug, description) VALUES
(2, '2/12', 'Растения', 'rasteniya', 'Комнатные цветы, саженцы деревьев, семена овощей, газонная трава.'),
(2, '2/13', 'Инструменты', 'instrumenty', 'Лопаты, триммеры, газонокосилки, секаторы, опрыскиватели.'),
(2, '2/14', 'Ландшафтный дизайн', 'landshaftnyi-dizain', 'Проекты участков, дренаж, дорожки, искусственные пруды.'),
(2, '2/15', 'Теплицы и парники', 'teplicy-i-parniki', 'Поликарбонат, пленка, стеллажи, системы полива.'),
(2, '2/16', 'Другое', 'sad-drugoe', 'Прочие сайты по теме сада и огорода.');

-- Подразделы: Кулинария (parent_id=3)
INSERT INTO sections (parent_id, path, name, slug, description) VALUES
(3, '3/17', 'Рецепты', 'recepty', 'Салаты, супы, выпечка, вторые блюда, десерты, заготовки на зиму.'),
(3, '3/18', 'Посуда и техника', 'posuda-i-tehnika', 'Сковороды, кастрюли, мультиварки, блендеры, хлебопечки.'),
(3, '3/19', 'Напитки', 'napitki', 'Кофе, чай, коктейли, компоты, домашние лимонады.'),
(3, '3/20', 'Этикет и сервировка', 'etiket-i-servirovka', 'Скатерти, салфетки, украшение блюд, правила подачи.'),
(3, '3/21', 'Другое', 'kulinariya-drugoe', 'Прочие сайты по теме кулинарии.');

-- Подразделы: Дети и развитие (parent_id=4)
INSERT INTO sections (parent_id, path, name, slug, description) VALUES
(4, '4/22', 'Игрушки', 'igrushki', 'Конструкторы, куклы, мягкие игрушки, настольные игры, машинки.'),
(4, '4/23', 'Детская комната', 'detskaya-komnata', 'Кроватки, столы, шкафы, коврики, системы хранения.'),
(4, '4/24', 'Образование', 'obrazovanie', 'Кружки, онлайн-школы, учебные пособия, развивашки для малышей.'),
(4, '4/25', 'Одежда и обувь', 'odezhda-i-obuv', 'От 0 до 3 лет, от 3 до 7 лет, от 7 до 12, для подростков.'),
(4, '4/26', 'Другое', 'deti-drugoe', 'Прочие сайты по теме детей и развития.');

-- Подразделы: Здоровье и спорт (parent_id=5)
INSERT INTO sections (parent_id, path, name, slug, description) VALUES
(5, '5/27', 'Фитнес и тренировки', 'fitnes-i-trenirovki', 'Упражнения дома, йога, кардио, программы для похудения.'),
(5, '5/28', 'Правильное питание', 'pravilnoe-pitanie', 'Рецепты ПП, счетчики калорий, БЖУ, доставка здоровой еды.'),
(5, '5/29', 'Медицина', 'medicina', 'Клиники, врачи, диагностика, стоматология, аптеки.'),
(5, '5/30', 'Инвентарь', 'inventar', 'Гантели, коврики, велотренажеры, массажеры, фитнес-браслеты.'),
(5, '5/31', 'Другое', 'zdorovie-drugoe', 'Прочие сайты по теме здоровья и спорта.');

-- Администратор по умолчанию (пароль: admin123, хеш bcrypt)
INSERT INTO users (email, username, password, active) VALUES
('admin@homecatalog.ru', 'admin', '$2y$10$...', 1);
```

## Класс для работы с БД

### `src/Database.php`
- Конструктор принимает массив конфигурации
- Создаёт PDO-соединение с PostgreSQL
- Устанавливает режим ошибок `ERRMODE_EXCEPTION`
- Метод `getConnection(): PDO`

## Применение миграции

```bash
psql -U catalog_user -d catalog -f migrations/001_initial.sql
psql -U catalog_user -d catalog -f migrations/002_seed_data.sql
```

## Контрольные точки

- [ ] Таблица `sections` создана, индексы на месте
- [ ] Таблица `sites` создана, индексы на месте
- [ ] Таблица `users` создана
- [ ] Триггеры `updated_at` работают
- [ ] Начальные данные (31 раздел) добавлены
- [ ] Пользователь-администратор создан (пароль захэширован через bcrypt)
- [ ] PDO-соединение проверено — тестовый запрос выполняется
