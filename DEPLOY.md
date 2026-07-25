# Deploy (Node.js)

This app is **Node + Express + MySQL**. It will **not** run on PHP-only hosts (InfinityFree/AwardSpace) or GitHub Pages.

## Option A — Render / Railway / Fly.io

1. Create a MySQL database (PlanetScale, Railway MySQL, or AlwaysData MySQL).
2. Import `sql/schema.sql`.
3. Set env vars from `.env.example` (`DB_*`, `JWT_SECRET`, `STORE_*`, `NODE_ENV=production`).
4. Deploy from GitHub: start command `npm start`, Node 18+.
5. Visit `https://your-app/setup` once → create admin → then block `/setup` (or remove the route).

## Option B — VPS

1. Install Node 18+, nginx, MySQL.
2. `git clone` repo, `npm install --omit=dev`, copy `.env`.
3. Import schema, `npm start` under systemd/pm2.
4. Reverse-proxy with nginx + HTTPS (Let's Encrypt).

## After deploy checklist

- [ ] Strong `JWT_SECRET` and DB password
- [ ] HTTPS enabled (`Secure` cookies)
- [ ] `/setup` disabled after first admin
- [ ] Uploads directory writable by the Node process
- [ ] WhatsApp + email set in env
