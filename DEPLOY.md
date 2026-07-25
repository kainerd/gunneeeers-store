# Deploy Gunneeeers Store (shared hosting)

This app is **PHP + MySQL**. It cannot run on GitHub Pages or other static hosts.

Use any host with **PHP 8.x**, **MySQL/MariaDB**, and preferably **Apache** (`.htaccess` hardening). Typical options: cPanel shared hosting, FTP upload to `public_html`, or a small VPS with Apache/Nginx + PHP-FPM.

## 1. Get the code onto the server

**Option A — Git (if the host supports it)**

```bash
cd public_html   # or your web root / subdomain folder
git clone https://github.com/kainerd/gunneeeers-store.git .
```

If the repo must live in a subfolder, clone into that folder and point the domain/subdomain document root there.

**Option B — Upload ZIP / FTP**

1. On GitHub: **Code → Download ZIP** (or clone locally and zip the project).
2. Upload contents into the web root (`public_html`, `www`, or a subdomain folder).
3. Ensure `index.php` is at the document root you configured (not nested one level deeper unless intentional).

Do **not** upload a local `.env` or real production passwords from your PC into a public repo. Edit secrets only on the server.

## 2. Create the database

In cPanel → **MySQL Databases** (or phpMyAdmin):

1. Create a database (e.g. `gunneeeers_store` — the host may prefix the name).
2. Create a MySQL user with a strong password.
3. Grant that user **only** this database: `SELECT`, `INSERT`, `UPDATE`, `DELETE` (no `FILE` / `SUPER`).
4. Import [`sql/schema.sql`](sql/schema.sql) via phpMyAdmin **Import**, or:

```bash
mysql -u YOUR_DB_USER -p YOUR_DB_NAME < sql/schema.sql
```

If you previously ran an older schema and need upgrades, also review [`sql/migrate_v2.sql`](sql/migrate_v2.sql).

## 3. Edit production config (on the server only)

### [`config/db.php`](config/db.php)

Set host credentials from the panel (often `localhost` or `127.0.0.1`):

- `DB_HOST`, `DB_PORT`
- `DB_NAME` (use the full prefixed name if cPanel added one)
- `DB_USER`, `DB_PASS`

Never commit real production passwords back to GitHub.

### [`config/store.php`](config/store.php)

- `STORE_NAME`
- `STORE_WHATSAPP` — digits only, country code, no `+` or spaces
- `STORE_EMAIL`

## 4. First admin, then lock down

1. Open `https://YOUR-DOMAIN/setup/generate-admin.php` and create the first admin.
2. **Delete the entire `setup/` folder** from the server immediately.
3. Log in at `https://YOUR-DOMAIN/admin/login.php`.
4. Remove diagnostics if present: `phase0-check.php`, `layout-test.php`, `css/phase0-check.css`.

## 5. HTTPS and post-deploy checklist

- [ ] Force HTTPS in the host panel (or Let’s Encrypt / AutoSSL).
- [ ] Confirm the site loads over `https://` (session `Secure` cookie enables automatically).
- [ ] `uploads/sell/` is writable by PHP (for sell-account photo uploads).
- [ ] Unauthenticated `/admin/dashboard.php` redirects to login.
- [ ] `staff` cannot open `/admin/users.php` (403).
- [ ] `config/`, `includes/`, `sql/` return 403 over HTTP.
- [ ] `setup/` is gone from the server.
- [ ] Real WhatsApp + email set; DB password is not the placeholder.

## Free / low-cost PHP hosts (with GitHub)

There is no official “GitHub Pages for PHP.” Practical paths:

| Path | Notes |
|------|--------|
| **cPanel shared hosting** (InfinityFree, AwardSpace, Hostinger trial, etc.) | Create MySQL DB → upload/clone repo → edit `config/*.php` → import schema. Many free tiers are limited or ad-heavy; fine for testing. |
| **AlwaysData / similar free PHP sandboxes** | Git deploy or SFTP; create MySQL in the panel. |
| **Railway / Render / Fly.io** | Possible with a custom PHP + MySQL (or managed DB) setup — more work than cPanel; you still need env/DB credentials. |
| **VPS** (Oracle Cloud free tier, etc.) | Install Apache/Nginx + PHP + MySQL yourself, then clone and configure. |

Whatever you pick, live deploy still needs: **host type**, **SSH/FTP or Git deploy access**, and **MySQL credentials** (or panel login so those can be created).

## What this repo does *not* include

- No FTP/cPanel secrets
- No GitHub Actions deploy workflow
- No Docker / PaaS `Procfile`

After you have hosting credentials, deploy is: upload/clone → import SQL → edit config → create admin → delete `setup/` → enable HTTPS.
