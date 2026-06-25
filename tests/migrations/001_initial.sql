-- ============================================
-- Каталог сайтов — начальная миграция (таблицы)
-- ============================================

BEGIN;

-- Кодировка
SET client_encoding = 'UTF8';

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

CREATE INDEX IF NOT EXISTS idx_sections_parent_id ON sections(parent_id);
CREATE INDEX IF NOT EXISTS idx_sections_path ON sections(path);
CREATE INDEX IF NOT EXISTS idx_sections_slug ON sections(slug);

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

CREATE INDEX IF NOT EXISTS idx_sites_section_id ON sites(section_id);
CREATE INDEX IF NOT EXISTS idx_sites_status ON sites(status);
CREATE INDEX IF NOT EXISTS idx_sites_slug ON sites(slug);
CREATE INDEX IF NOT EXISTS idx_sites_created_at ON sites(created_at DESC);

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
DROP TRIGGER IF EXISTS trg_sections_updated_at ON sections;
CREATE TRIGGER trg_sections_updated_at
    BEFORE UPDATE ON sections
    FOR EACH ROW EXECUTE FUNCTION update_timestamp();

-- Триггер для users
DROP TRIGGER IF EXISTS trg_users_updated_at ON users;
CREATE TRIGGER trg_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_timestamp();

COMMIT;
