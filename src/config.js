'use strict';

require('dotenv').config();

const config = {
  port: Number(process.env.PORT) || 3000,
  nodeEnv: process.env.NODE_ENV || 'development',
  db: {
    host: process.env.DB_HOST || '127.0.0.1',
    port: Number(process.env.DB_PORT) || 3306,
    database: process.env.DB_NAME || 'gunneeeers_store',
    user: process.env.DB_USER || 'store_app',
    password: process.env.DB_PASS || '',
  },
  jwt: {
    secret: process.env.JWT_SECRET || '',
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

if (!config.jwt.secret || config.jwt.secret.length < 32) {
  console.warn('[security] JWT_SECRET should be at least 32 characters. Set it in .env');
}

module.exports = config;
