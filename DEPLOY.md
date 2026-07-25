# Deploy Gunneeeers Store (shared hosting)

This app is **PHP + MySQL**. It cannot run on GitHub Pages or other static hosts.

Use any host with **PHP 8.x**, **MySQL/MariaDB**, and preferably **Apache** (`.htaccess` hardening). Typical options: cPanel shared hosting, FTP upload to `public_html`, or a small VPS with Apache/Nginx + PHP-FPM.

---

## Recommended free host: alwaysdata

**Chosen host:** [alwaysdata](https://www.alwaysdata.com/) — free Public Cloud plan (1 GB), PHP + MariaDB/MySQL, Apache, FTP/SFTP/SSH, Git over SSH. Site URL form: `https://YOURACCOUNT.alwaysdata.net`.

### Sign up

1. Open **[https://www.alwaysdata.com/en/register/](https://www.alwaysdata.com/en/register/)**.
2. Enter email + password (or Continue with Google/Apple).
3. Accept the privacy policy.
4. **Validate a credit/debit card** (identity check only — alwaysdata states you are **not charged** for the free plan).
5. Create the profile / free account. Pick an account name (this becomes `YOURACCOUNT.alwaysdata.net`).

You cannot finish signup without your email, CAPTCHA/card UI, and password. After the account exists, send FTP + MySQL credentials so files can be uploaded for you (see bottom of this doc).

### After signup — alwaysdata checklist

#### A. Create MySQL database

1. Log in to the admin panel: [https://admin.alwaysdata.com/](https://admin.alwaysdata.com/).
2. Go to **Databases → MySQL → Add a database**.
3. Name example: `store` (panel usually prefixes → full name like `YOURACCOUNT_store`).
4. Note from the panel (or create a DB user under **Databases → MySQL → Users**):
   - **Host:** `mysql-YOURACCOUNT.alwaysdata.net` (not `localhost` unless the panel says so)
   - **Database name:** full prefixed name
   - **User** + **password**
5. Open **phpMyAdmin** from the Databases section → select the DB → **Import** → upload [`sql/schema.sql`](sql/schema.sql).

#### B. Put the code in the web root

Web files live under **`$HOME/www/`** by default (site address `YOURACCOUNT.alwaysdata.net`).

**Option 1 — Git over SSH (preferred if SSH is enabled)**

```bash
ssh YOURACCOUNT@ssh-YOURACCOUNT.alwaysdata.net
cd ~/www
git clone https://github.com/kainerd/gunneeeers-store.git .
```

If `www` is not empty, clone into a temp folder and move contents so `index.php` sits directly in `www/`.

**Option 2 — FTP / SFTP**

| Field | Value |
|-------|--------|
| Host | `ftp-YOURACCOUNT.alwaysdata.net` |
| Port | `990` (FTPS) or `21` (STARTTLS); SFTP often via SSH host |
| User / pass | From **Remote access → FTP** (default user matches account name) |

Upload the repo contents into `www/` so `index.php` is at the site root (not nested in an extra folder).

Confirm **Web → Sites** has a PHP site pointing at that directory (create/add site if needed).

#### C. Edit config on the server only

Edit [`config/db.php`](config/db.php) on the host (do **not** commit real passwords):

```php
const DB_HOST = 'mysql-YOURACCOUNT.alwaysdata.net';
const DB_PORT = '3306';
const DB_NAME = 'YOURACCOUNT_store';   // full name from panel
const DB_USER = 'YOURACCOUNT';          // or dedicated DB user
const DB_PASS = 'your-db-password';
```

Optionally tweak [`config/store.php`](config/store.php) (`STORE_WHATSAPP`, `STORE_EMAIL`).

Ensure `uploads/sell/` is writable by PHP.

#### D. First admin, then lock down

1. Open `https://YOURACCOUNT.alwaysdata.net/setup/generate-admin.php` and create the admin.
2. **Delete the entire `setup/` folder** from the server immediately.
3. Log in at `https://YOURACCOUNT.alwaysdata.net/admin/login.php`.
4. Remove diagnostics if present: `phase0-check.php`, `layout-test.php`, `css/phase0-check.css`.

#### E. HTTPS

alwaysdata provides HTTPS for `*.alwaysdata.net`. Confirm the site loads over `https://` (session `Secure` cookie enables automatically).

---

## No credit card? Alternatives (same PHP+MySQL steps)

| Host | Signup | Notes |
|------|--------|--------|
| **AwardSpace** | [https://www.awardspace.com/free-hosting/](https://www.awardspace.com/free-hosting/) | No card; PHP + 1 MySQL; FTP/File Manager; free subdomain. Upload into the account web root, then same DB/config/admin steps. |
| **InfinityFree** | [https://dash.infinityfree.com/register](https://dash.infinityfree.com/register) | No card; after email verify → **Create Account** (free subdomain). Upload into **`htdocs/`**. MySQL host is **not** `localhost` — copy hostname from **MySQL Databases** (e.g. `sqlXXX.infinityfree.com`). |

Generic flow on any of these: create MySQL DB → upload/clone from GitHub → edit `config/db.php` → import `sql/schema.sql` → run `setup/generate-admin.php` once → delete `setup/`.

---

## Generic shared-hosting steps (cPanel / VPS)

### 1. Get the code onto the server

**Option A — Git (if the host supports it)**

```bash
cd public_html   # or your web root / subdomain folder
git clone https://github.com/kainerd/gunneeeers-store.git .
```

**Option B — Upload ZIP / FTP**

1. On GitHub: **Code → Download ZIP** (or clone locally and zip the project).
2. Upload contents into the web root (`public_html`, `www`, or a subdomain folder).
3. Ensure `index.php` is at the document root you configured.

Do **not** upload a local `.env` or real production passwords from your PC into a public repo. Edit secrets only on the server.

### 2. Create the database

In cPanel → **MySQL Databases** (or phpMyAdmin):

1. Create a database (e.g. `gunneeeers_store` — the host may prefix the name).
2. Create a MySQL user with a strong password.
3. Grant that user **only** this database: `SELECT`, `INSERT`, `UPDATE`, `DELETE` (no `FILE` / `SUPER`).
4. Import [`sql/schema.sql`](sql/schema.sql) via phpMyAdmin **Import**, or:

```bash
mysql -u YOUR_DB_USER -p YOUR_DB_NAME < sql/schema.sql
```

If you previously ran an older schema and need upgrades, also review [`sql/migrate_v2.sql`](sql/migrate_v2.sql).

### 3. Edit production config (on the server only)

### [`config/db.php`](config/db.php)

Set host credentials from the panel:

- `DB_HOST`, `DB_PORT`
- `DB_NAME` (use the full prefixed name if the host added one)
- `DB_USER`, `DB_PASS`

Never commit real production passwords back to GitHub.

### [`config/store.php`](config/store.php)

- `STORE_NAME`
- `STORE_WHATSAPP` — digits only, country code, no `+` or spaces
- `STORE_EMAIL`

### 4. First admin, then lock down

1. Open `https://YOUR-DOMAIN/setup/generate-admin.php` and create the first admin.
2. **Delete the entire `setup/` folder** from the server immediately.
3. Log in at `https://YOUR-DOMAIN/admin/login.php`.
4. Remove diagnostics if present: `phase0-check.php`, `layout-test.php`, `css/phase0-check.css`.

### 5. HTTPS and post-deploy checklist

- [ ] Force HTTPS in the host panel (or Let’s Encrypt / AutoSSL).
- [ ] Confirm the site loads over `https://` (session `Secure` cookie enables automatically).
- [ ] `uploads/sell/` is writable by PHP (for sell-account photo uploads).
- [ ] Unauthenticated `/admin/dashboard.php` redirects to login.
- [ ] `staff` cannot open `/admin/users.php` (403).
- [ ] `config/`, `includes/`, `sql/` return 403 over HTTP.
- [ ] `setup/` is gone from the server.
- [ ] Real WhatsApp + email set; DB password is not the placeholder.

---

## What to send back so upload can be finished for you

After the free account + MySQL DB exist, reply with:

1. **Site URL** (e.g. `https://YOURACCOUNT.alwaysdata.net`)
2. **FTP/SFTP:** host, port, username, password
3. **MySQL:** host, database name, username, password
4. (Optional) SSH host/user if you prefer Git clone on the server

With those, files can be uploaded, `config/db.php` set, schema imported, and you only need to run `setup/generate-admin.php` once then delete `setup/`.

## What this repo does *not* include

- No FTP/cPanel secrets
- No GitHub Actions deploy workflow
- No Docker / PaaS `Procfile`

Live deploy still needs hosting credentials. The site is **not** live on a free host until files are actually uploaded and the DB is configured.
