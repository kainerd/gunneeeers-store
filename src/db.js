'use strict';

const mysql = require('mysql2/promise');
const config = require('./config');

// Pool uses prepared statements via execute() — never concatenate user input into SQL.
const pool = mysql.createPool({
  host: config.db.host,
  port: config.db.port,
  user: config.db.user,
  password: config.db.password,
  database: config.db.database,
  waitForConnections: true,
  connectionLimit: 10,
  namedPlaceholders: false,
  // Prefer real prepares over client-side emulation where supported.
  enableKeepAlive: true,
});

async function query(sql, params = []) {
  const [rows] = await pool.execute(sql, params);
  return rows;
}

module.exports = { pool, query };
