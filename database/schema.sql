-- Country and Currency API Database Schema
-- SQLite Version

-- Countries table
CREATE TABLE IF NOT EXISTS countries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE COLLATE NOCASE,
    capital TEXT,
    region TEXT,
    population INTEGER NOT NULL,
    currency_code TEXT,
    exchange_rate REAL,
    estimated_gdp REAL,
    flag_url TEXT,
    last_refreshed_at TEXT NOT NULL DEFAULT (datetime('now')),
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_countries_name ON countries(name COLLATE NOCASE);
CREATE INDEX IF NOT EXISTS idx_countries_region ON countries(region);
CREATE INDEX IF NOT EXISTS idx_countries_currency ON countries(currency_code);
CREATE INDEX IF NOT EXISTS idx_countries_gdp ON countries(estimated_gdp DESC);

-- API Status table (single row for global metadata)
CREATE TABLE IF NOT EXISTS api_status (
    id INTEGER PRIMARY KEY CHECK (id = 1),
    total_countries INTEGER DEFAULT 0,
    last_refreshed_at TEXT,
    updated_at TEXT DEFAULT (datetime('now'))
);

-- Insert initial status record
INSERT OR IGNORE INTO api_status (id, total_countries) VALUES (1, 0);

-- Trigger to update updated_at timestamp
CREATE TRIGGER IF NOT EXISTS update_countries_timestamp 
AFTER UPDATE ON countries
BEGIN
    UPDATE countries SET updated_at = datetime('now') WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS update_api_status_timestamp 
AFTER UPDATE ON api_status
BEGIN
    UPDATE api_status SET updated_at = datetime('now') WHERE id = NEW.id;
END;
