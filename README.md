# Gunneeeers Store

eFootball coins & accounts storefront for **XAMPP** (PHP + MySQL). Visitors browse packages/accounts and order via **WhatsApp** or **email** — there is **no online payment** or cart.

Staff use a **role-based admin dashboard** (`admin` / `staff`).

## Requirements

- XAMPP (Apache + MySQL/MariaDB + PHP 8.x with PDO MySQL)
- `mod_headers` / `mod_authz_core` enabled (usual XAMPP defaults)

## Install

1. Place this folder in `htdocs` (or junction it), e.g. `C:\xampp\htdocs\gunneeeers-store`.
2. Start **Apache** and **MySQL** in XAMPP Control Panel.
3. Edit [`config/db.php`](config/db.php): set `DB_NAME`, `DB_USER`, `DB_PASS`.
   - Prefer a dedicated MySQL user with access **only** to this database (not `root` in production).
4. Edit [`config/store.php`](config/store.php): `STORE_NAME`, `STORE_WHATSAPP` (digits + country code), `STORE_EMAIL`.
5. Import schema:

```bat
C:\xampp\mysql\bin\mysql.exe -u root < "C:\xampp\htdocs\gunneeeers-store\sql\schema.sql"
```

Or import `sql/schema.sql` via phpMyAdmin.

6. Create the first **admin** user (one-time):

   Open `http://localhost/gunneeeers-store/setup/generate-admin.php`

7. **Delete the entire `setup/` folder** from the server immediately after.
8. Log in at `http://localhost/gunneeeers-store/admin/login.php`
9. Delete diagnostic pages if present: `phase0-check.php`, `layout-test.php`, `css/phase0-check.css`.

### Create app DB user (example)

```sql
CREATE DATABASE IF NOT EXISTS gunneeeers_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'store_app'@'localhost' IDENTIFIED BY 'CHANGE_ME_DB_PASSWORD';
GRANT SELECT, INSERT, UPDATE, DELETE ON gunneeeers_store.* TO 'store_app'@'localhost';
FLUSH PRIVILEGES;
```

## Roles

| Role | Access |
|------|--------|
| `admin` | Dashboard + **Coin prices** + **Users** |
| `staff` | Dashboard only (messages, buy & sell requests) |

### Coin prices
Admins manage packages at `admin/coins.php`. Nothing is seeded — the public Coins page stays empty until you add prices.

### Buy / sell accounts
- **Buy:** visitors pick platform + **price range** (`accounts.php`) → `buy_requests`
- **Sell:** visitors upload a **photo** + details (`sell-account.php`) → `sell_requests`
- There is **no** public PS/Xbox/PC/Mobile account catalog

Additional staff/admin accounts are created by an admin under **Admin → Users**. There are **no customer logins**.

## Public pages

| Page | Purpose |
|------|---------|
| `index.php` | Hero, services overview, how it works |
| `services.php` | Four services in detail |
| `coins.php` | Packages from DB + WhatsApp / mailto order links |
| `accounts.php` | Accounts from DB, platform filter whitelist |
| `sell-account.php` | Sell request → `sell_requests` |
| `contact.php` | Contact → `messages` |

## Admin pages

| Page | Purpose |
|------|---------|
| `admin/login.php` | Rate-limited, timing-safe login |
| `admin/dashboard.php` | Messages + sell requests |
| `admin/users.php` | Admin-only user CRUD (create / activate) |
| `admin/logout.php` | End session |
| `setup/generate-admin.php` | First admin only — **delete after use** |

## Pre-launch security checklist

- [ ] Real WhatsApp number + email set in `config/store.php`
- [ ] DB password is not the placeholder; app user is least-privilege
- [ ] `setup/` folder removed from the server
- [ ] `phase0-check.php` / `layout-test.php` removed
- [ ] HTTPS enabled in production (`Secure` session cookie auto-enables)
- [ ] Confirmed unauthenticated `/admin/dashboard.php` redirects to login
- [ ] Confirmed `staff` cannot open `/admin/users.php` (403)
- [ ] Confirmed `config/`, `includes/`, `sql/` return 403 over HTTP
- [ ] No real secrets committed to git

## Security requirements map

| Requirement | Where |
|-------------|--------|
| PDO prepared statements, `EMULATE_PREPARES => false` | [`config/db.php`](config/db.php) |
| PDO exceptions logged, generic user message | `db()`, public/admin catch blocks |
| Platform / role / status whitelists before bind | [`includes/bootstrap.php`](includes/bootstrap.php), `accounts.php`, `admin/*` |
| XSS escaping `h()` | [`includes/security.php`](includes/security.php); all templates |
| CSP + frame deny + nosniff | `send_security_headers()`, [`.htaccess`](.htaccess) |
| CSRF token + `hash_equals` | `csrf_*()`; all POST forms |
| Session HttpOnly / SameSite / Secure-on-HTTPS | `security_bootstrap()` |
| `session_regenerate_id` on login + periodic | `auth_login()`, `security_bootstrap()` |
| `password_hash` / `password_verify` | setup, `admin/users.php`, `admin/login.php` |
| Timing-safe login (dummy hash) | `admin/login.php` + `AUTH_DUMMY_HASH` |
| Login + form rate limits | `rate_limit_allow()` |
| Honeypot on public forms | `sell-account.php`, `contact.php` |
| wa.me / mailto CR/LF stripping | `sanitize_link_text()` in `config/store.php` |
| Deny web access to config/includes/sql + dumps | `.htaccess` files |
| No seeded admin passwords | `sql/schema.sql` — empty `users` |
| Role checks server-side only | `auth_require('admin')` in `admin/users.php` |
| Staff cannot self-escalate | Role taken from DB session; create-user role whitelisted |

## License

Private store project — use at your own risk. No payment processing is included by design.
