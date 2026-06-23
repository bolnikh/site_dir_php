-- ============================================
-- Таблица обратной связи
-- ============================================

BEGIN;

CREATE TABLE IF NOT EXISTS contact_us (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(64) DEFAULT '',
    message TEXT NOT NULL,
    is_read SMALLINT NOT NULL DEFAULT 0,   -- 0=новое, 1=прочитано
    created_at TIMESTAMP DEFAULT NOW()
);

COMMIT;
