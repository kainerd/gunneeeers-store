'use strict';

require('dotenv').config();

function requireEnv(name, minLen = 1) {
  const v = process.env[name];
  if (!v || String(v).length < minLen) {
    throw new Error(`[security] Missing or too short env: ${name}`);
  }
  return v;
}

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

const config = {
  port: Number(process.env.PORT) || 3000,
  nodeEnv,
  isProd,
  // Setup route off by default once you have an admin (set SETUP_ENABLED=true only for first boot).
  setupEnabled: String(process.env.SETUP_ENABLED || '').toLowerCase() === 'true',
  db: {
    host: process.env.DB_HOST || '127.0.0.1',
    port: Number(process.env.DB_PORT) || 3306,
    database: process.env.DB_NAME || 'gunneeeers_store',
    user: process.env.DB_USER || 'store_app',
    password: process.env.DB_PASS || '',
  },
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

if (isProd && (!config.db.password || /CHANGE_ME/i.test(config.db.password))) {
  throw new Error('[security] Refusing to start: set a real DB_PASS in production.');
}

module.exports = config;
