# Deploy Gunneeeers Store (Node + Express + MySQL)

## One-click (Render)

1. Create a free MySQL DB (e.g. [Railway MySQL](https://railway.app) or [AlwaysData](https://www.alwaysdata.com/en/register/)).
2. Import `sql/schema.sql` into that database.
3. Open: https://render.com/deploy?repo=https://github.com/kainerd/gunneeeers-store
4. Set env vars: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` (JWT can be auto-generated).
5. Keep `SETUP_ENABLED=false` (admin already exists in your local DB — create admin on the **production** DB with `SETUP_ENABLED=true` once, then set false again).

## Railway

```bash
npm i -g @railway/cli
railway login
railway init
railway add --database mysql
railway up
```

Then set the same env vars from `.env.example` (never commit `.env`).

## After deploy

- [ ] HTTPS works
- [ ] `/setup` returns 403
- [ ] Admin login works on production DB
- [ ] Coin prices added in admin
