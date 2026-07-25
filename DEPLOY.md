# Deploy Gunneeeers Store (Node + Express + MySQL)

## One-click (Render)

1. Create a free MySQL DB (e.g. [Railway MySQL](https://railway.app) or [AlwaysData](https://www.alwaysdata.com/en/register/)).
2. Import `sql/schema.sql` into that database.
3. Open: https://render.com/deploy?repo=https://github.com/kainerd/gunneeeers-store
4. Set env vars: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` (JWT can be auto-generated).
5. Keep `SETUP_ENABLED=false` (admin already exists in your local DB — create admin on the **production** DB with `SETUP_ENABLED=true` once, then set false again).

## Railway

1. Add a **MySQL** plugin/service (keep the service name `MySQL`, or edit the refs below).
2. Deploy this repo as a **Node** web service (`railway.toml` → `npm start`).
3. In the **web** service → Variables, paste from `railway.variables.example`, or:

```text
MYSQL_URL=${{ MySQL.MYSQL_URL }}
JWT_SECRET=<run: openssl rand -hex 32>
NODE_ENV=production
SETUP_ENABLED=false
STORE_NAME=Gunneeeers Store
STORE_WHATSAPP=249112780717
STORE_EMAIL=emadsesko@gmail.com
```

If `MySQL.MYSQL_URL` is empty, use this instead:

```text
MYSQL_URL=mysql://${{ MySQL.MYSQLUSER }}:${{ MySQL.MYSQLPASSWORD }}@${{ MySQL.MYSQLHOST }}:${{ MySQL.MYSQLPORT }}/${{ MySQL.MYSQLDATABASE }}
```

Do **not** use `RAILWAY_PRIVATE_DOMAIN` or `MYSQL_ROOT_PASSWORD` from the web service — that points at the wrong host/password.

The app reads `MYSQL_URL` (or `DATABASE_URL`) automatically — no need for separate `DB_HOST` / `DB_USER` / `DB_PASS` when the URL is set.

Import `sql/schema.sql` into the Railway MySQL service (Query / mysql CLI), then create the first admin with `SETUP_ENABLED=true` once.

## After deploy

- [ ] HTTPS works
- [ ] `/setup` returns 403
- [ ] Admin login works on production DB
- [ ] Coin prices added in admin
