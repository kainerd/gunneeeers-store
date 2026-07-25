'use strict';

/**
 * CLI: create first admin if none exist.
 * Usage: npm run setup:admin -- username password
 */
require('dotenv').config();
const { query, pool } = require('../src/db');
const { hashPassword } = require('../src/middleware/auth');

async function main() {
  const username = process.argv[2];
  const password = process.argv[3];
  if (!username || !password || password.length < 10) {
    console.error('Usage: npm run setup:admin -- <username> <password-min-10>');
    process.exit(1);
  }
  const rows = await query('SELECT COUNT(*) AS c FROM users');
  if (Number(rows[0].c) > 0) {
    console.error('Users already exist. Use the admin Users page instead.');
    process.exit(1);
  }
  const hash = await hashPassword(password);
  await query(
    'INSERT INTO users (username, password_hash, role, is_active) VALUES (?, ?, ?, 1)',
    [username, hash, 'admin']
  );
  console.log('Admin created:', username);
  await pool.end();
}

main().catch(async (err) => {
  console.error(err.message);
  try { await pool.end(); } catch (_) {}
  process.exit(1);
});
