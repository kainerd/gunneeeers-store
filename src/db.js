'use strict';

const mysql = require('mysql2/promise');
const config = require('./config');

// Pool uses prepared statements via execute() — never concatenate user input into SQL.
const poolOptions = {
  host: config.db.host,
  port: config.db.port,
  user: config.db.user,
  password: config.db.password,
  database: config.db.database,
  waitForConnections: true,
  connectionLimit: 10,
  namedPlaceholders: false,
  enableKeepAlive: true,
};

if (config.db.ssl) {
  poolOptions.ssl = config.db.ssl;
}

const pool = mysql.createPool(poolOptions);

async function query(sql, params = []) {
  const [rows] = await pool.execute(sql, params);
  return rows;
}

module.exports = { pool, query };
