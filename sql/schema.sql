-- Gunneeeers Store schema (utf8mb4)
-- Import: mysql -u root < sql/schema.sql
--
-- SECURITY: No user accounts are seeded here. Create the first admin via /setup
-- (Node app) or `npm run setup:admin`, then disable /setup in production.
-- Coin prices are NOT seeded — admins add them in the dashboard.
-- Pre-built PS/Xbox/PC/Mobile account listings are NOT used; buyers submit a price-range request.

CREATE DATABASE IF NOT EXISTS gunneeeers_store
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE gunneeeers_store;

CREATE TABLE IF NOT EXISTS messages (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(255) NOT NULL,
  whatsapp      VARCHAR(32)  NULL,
  subject       VARCHAR(200) NOT NULL,
  body          TEXT         NOT NULL,
  ip_address    VARCHAR(45)  NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  is_read       TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_messages_created (created_at),
  KEY idx_messages_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sell_requests (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name            VARCHAR(100) NOT NULL,
  whatsapp        VARCHAR(32)  NOT NULL,
  email           VARCHAR(255) NULL,
  platform        ENUM('mobile','ps','xbox','pc') NOT NULL,
  account_level   VARCHAR(50)  NOT NULL,
  coin_balance    VARCHAR(50)  NULL,
  description     TEXT         NOT NULL,
  asking_price    VARCHAR(50)  NULL,
  photo_path      VARCHAR(255) NOT NULL,
  ip_address      VARCHAR(45)  NULL,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status          ENUM('new','contacted','closed') NOT NULL DEFAULT 'new',
  PRIMARY KEY (id),
  KEY idx_sell_created (created_at),
  KEY idx_sell_status (status),
  KEY idx_sell_platform (platform)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Buyers request an account by budget range (no public account catalog).
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

-- Managed only by admin in the dashboard — no seed prices.
CREATE TABLE IF NOT EXISTS coin_packages (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  delivery_method   ENUM('website','in_game') NOT NULL,
  coin_amount       INT UNSIGNED NOT NULL,
  price_label       VARCHAR(50)  NOT NULL,
  sort_order        INT NOT NULL DEFAULT 0,
  is_active         TINYINT(1) NOT NULL DEFAULT 1,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_coins_active_method (is_active, delivery_method, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username      VARCHAR(64)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at DATETIME     NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  KEY idx_users_role (role),
  KEY idx_users_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Explicitly: NO seeded coin prices, NO account listings, NO users.
