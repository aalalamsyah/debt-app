-- Skema database debt_db. Jalankan sekali:
--   psql -U postgres -d debt_db -f database/schema.sql
-- atau: php database/migrate.php

CREATE TABLE IF NOT EXISTS members (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    phone      VARCHAR(30),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS debts (
    id          SERIAL PRIMARY KEY,
    member_id   INT NOT NULL REFERENCES members(id) ON DELETE CASCADE,
    amount      BIGINT NOT NULL CHECK (amount > 0),
    note        TEXT,
    recorded_by VARCHAR(100) NOT NULL,
    debt_date   DATE NOT NULL DEFAULT CURRENT_DATE,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
    cancelled_at TIMESTAMPTZ,
    cancel_reason TEXT
);

CREATE TABLE IF NOT EXISTS payments (
    id           SERIAL PRIMARY KEY,
    member_id    INT NOT NULL REFERENCES members(id) ON DELETE CASCADE,
    amount       BIGINT NOT NULL CHECK (amount > 0),
    note         TEXT,
    recorded_by  VARCHAR(100) NOT NULL,
    created_at   TIMESTAMPTZ NOT NULL DEFAULT now(),
    cancelled_at TIMESTAMPTZ
);

CREATE INDEX IF NOT EXISTS idx_debts_member ON debts(member_id, created_at);
CREATE INDEX IF NOT EXISTS idx_payments_member ON payments(member_id);

-- Migrasi untuk database lama (aman dijalankan berulang): tambah tanggal hutang
ALTER TABLE debts ADD COLUMN IF NOT EXISTS debt_date DATE;
UPDATE debts SET debt_date = created_at::date WHERE debt_date IS NULL;
ALTER TABLE debts ALTER COLUMN debt_date SET DEFAULT CURRENT_DATE;
ALTER TABLE debts ALTER COLUMN debt_date SET NOT NULL;
ALTER TABLE debts ADD COLUMN IF NOT EXISTS cancelled_at TIMESTAMPTZ;
ALTER TABLE debts ADD COLUMN IF NOT EXISTS cancel_reason TEXT;
