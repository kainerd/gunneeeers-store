-- Migrate existing XAMPP DB from catalog model → request + admin pricing model.
USE gunneeeers_store;

DROP TABLE IF EXISTS accounts_for_sale;

-- Clear demo coin prices; admin re-enters them.
DELETE FROM coin_packages;

ALTER TABLE sell_requests
  ADD COLUMN IF NOT EXISTS photo_path VARCHAR(255) NULL AFTER asking_price;

-- Older MariaDB may not support IF NOT EXISTS on ADD COLUMN — handled in migrate script.

CREATE TABLE IF NOT EXISTS buy_requests (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name            VARCHAR(100) NOT NULL,
  whatsapp        VARCHAR(32)  NOT NULL,
  email           VARCHAR(255) NULL,
  platform        ENUM('mobile','ps','xbox','pc') NOT NULL,
  price_range     ENUM('under_25','25_50','50_100','100_200','200_plus') NOT NULL,
  notes           TEXT         NULL,
  ip_address      VARCHAR(45)  NULL,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status          ENUM('new','contacted','closed') NOT NULL DEFAULT 'new',
  PRIMARY KEY (id),
  KEY idx_buy_created (created_at),
  KEY idx_buy_status (status),
  KEY idx_buy_range (price_range)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
