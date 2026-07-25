# Gunneeeers Store (Node.js + Express + JWT)

eFootball coins & accounts storefront. Visitors browse packages and submit buy/sell requests. Orders go via **WhatsApp / email** — no online payments.

## Stack

- **Node.js 18+** / **Express**
- **MySQL** (same `sql/schema.sql`)
- **JWT** in httpOnly cookie for staff login (`admin` / `staff` roles)
- **EJS** templates, Arsenal red/navy theme

## Setup (local)

1. Install Node.js, start MySQL (XAMPP is fine for the DB only).
2. Copy `.env.example` → `.env` and set `DB_*`, `JWT_SECRET` (32+ chars), WhatsApp/email.
3. Import schema:

```bat
C:\xampp\mysql\bin\mysql.exe -u root < sql\schema.sql
```

4. Install & run:

```bat
npm install
npm start
```

5. Open http://localhost:3000  
6. First admin: http://localhost:3000/setup — then stop exposing `/setup` in production.

## Scripts

| Command | Purpose |
|---------|---------|
| `npm start` | Run server |
| `npm run dev` | Run with `--watch` |

## Roles

| Role | Access |
|------|--------|
| `admin` | Dashboard + coin prices + users |
| `staff` | Dashboard only |

## Security notes

- SQL via `mysql2` prepared `execute()`
- JWT httpOnly + SameSite=Lax (+ Secure in production)
- CSRF double-submit cookie on POSTs
- Helmet CSP, rate limits on forms/login
- Sell photos stored under `uploads/sell` and served only to logged-in staff

## Deploy

See [DEPLOY.md](DEPLOY.md). Use a Node host (Render, Railway, VPS) + managed MySQL — not PHP shared hosting / GitHub Pages.
