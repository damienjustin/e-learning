ALTER TABLE courses ADD COLUMN certificate_enabled TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE courses ADD COLUMN certificate_config TEXT NULL;
