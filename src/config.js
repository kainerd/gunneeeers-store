'use strict';

require('dotenv').config();

const nodeEnv = process.env.NODE_ENV || 'development';
const isProd = nodeEnv === 'production';

// Never hardcode secrets — fail closed if JWT is weak.
const jwtSecret = process.env.JWT_SECRET || '';
if (jwtSecret.length < 32) {
  throw new Error('[security] JWT_SECRET must be set in .env and be at least 32 characters.');
}
if (/CHANGE_ME|dev_only/i.test(jwtSecret) && isProd) {
  throw new Error('[security] Refusing to start: JWT_SECRET still looks like a placeholder.');
}

/**
 * Prefer MYSQL_URL / DATABASE_URL (Railway/Render style).
 * Example: mysql://user:pass@host:3306/dbname
 * Railway template ref: ${{ MySQL.MYSQL_URL }}
 */
function parseMysqlUrl(raw) {
  if (!raw || typeof raw !== 'string') return null;
  try {
    const u = new URL(raw);
    if (!/^mysql(s)?:$/i.test(u.protocol)) return null;
    const database = decodeURIComponent((u.pathname || '').replace(/^\//, ''));
    if (!database) return null;
    return {
      host: u.hostname || '127.0.0.1',
      port: Number(u.port) || 3306,
      user: decodeURIComponent(u.username || ''),
      password: decodeURIComponent(u.password || ''),
      database,
      // mysql2 supports ssl object when needed (Railway often needs it off by default).
      ssl: u.searchParams.get('ssl') === 'true' ? {} : undefined,
    };
  } catch {
    return null;
  }
}

const urlDb =
  parseMysqlUrl(process.env.MYSQL_URL) ||
  parseMysqlUrl(process.env.DATABASE_URL) ||
  parseMysqlUrl(process.env.MYSQL_PUBLIC_URL);

const db = urlDb || {
  host: process.env.DB_HOST || '127.0.0.1',
  port: Number(process.env.DB_PORT) || 3306,
  database: process.env.DB_NAME || 'gunneeeers_store',
  user: process.env.DB_USER || 'store_app',
  password: process.env.DB_PASS || '',
};

const config = {
  port: Number(process.env.PORT) || 3000,
  nodeEnv,
  isProd,
  // Setup route off by default once you have an admin (set SETUP_ENABLED=true only for first boot).
  setupEnabled: String(process.env.SETUP_ENABLED || '').toLowerCase() === 'true',
  db,
  mysqlUrl: process.env.MYSQL_URL || process.env.DATABASE_URL || null,
  jwt: {
    secret: jwtSecret,
    expiresIn: process.env.JWT_EXPIRES_IN || '8h',
    cookieName: 'gs_token',
  },
  store: {
    name: process.env.STORE_NAME || 'Gunneeeers Store',
    whatsapp: (process.env.STORE_WHATSAPP || '').replace(/\D+/g, ''),
    email: process.env.STORE_EMAIL || 'orders@example.com',
  },
  roles: Object.freeze(['admin', 'staff']),
  platforms: Object.freeze(['mobile', 'ps', 'xbox', 'pc']),
  priceRanges: Object.freeze(['under_25', '25_50', '50_100', '100_200', '200_plus']),
  deliveries: Object.freeze(['website', 'in_game']),
};

if (isProd && !urlDb && (!config.db.password || /CHANGE_ME/i.test(config.db.password))) {
  throw new Error('[security] Refusing to start: set MYSQL_URL or a real DB_PASS in production.');
}

module.exports = config;
