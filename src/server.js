'use strict';

const app = require('./app');
const config = require('./config');
const { pool } = require('./db');

async function start() {
  try {
    const conn = await pool.getConnection();
    await conn.ping();
    conn.release();
    console.log('MySQL connected.');
  } catch (err) {
    console.error('MySQL connection failed:', err.message);
    console.error('Check .env DB_* settings and that the schema is imported.');
    process.exit(1);
  }

  app.listen(config.port, () => {
    console.log(`${config.store.name} listening on http://localhost:${config.port}`);
  });
}

start();
