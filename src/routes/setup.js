'use strict';

const express = require('express');
const config = require('../config');
const { query } = require('../db');
const { cleanText } = require('../lib/helpers');
const { hashPassword } = require('../middleware/auth');

const router = express.Router();

function setupBlocked(res) {
  return res.status(403).type('text').send('Setup is disabled.');
}

router.use((req, res, next) => {
  // Kill-switch: SETUP_ENABLED must be true in .env — never leave this open in production.
  if (!config.setupEnabled) {
    return setupBlocked(res);
  }
  return next();
});

router.get('/', async (req, res, next) => {
  try {
    const rows = await query('SELECT COUNT(*) AS c FROM users');
    const count = Number(rows[0]?.c || 0);
    if (count > 0) {
      return setupBlocked(res);
    }
    res.render('setup', {
      isAdminSection: true,
      title: 'Create first admin',
      error: '',
      done: false,
    });
  } catch (err) {
    next(err);
  }
});

router.post('/', async (req, res, next) => {
  try {
    const rows = await query('SELECT COUNT(*) AS c FROM users');
    if (Number(rows[0]?.c || 0) > 0) {
      return setupBlocked(res);
    }

    const username = cleanText(req.body.username);
    const password = String(req.body.password || '');
    const confirm = String(req.body.password_confirm || '');
    let error = '';

    if (!username || username.length > 64 || !/^[a-zA-Z0-9._-]+$/.test(username)) {
      error = 'Username must be 1–64 chars (letters, numbers, . _ -).';
    } else if (password.length < 10 || password.length > 128) {
      error = 'Password must be 10–128 characters.';
    } else if (!/[A-Za-z]/.test(password) || !/\d/.test(password)) {
      // Basic complexity — still store only bcrypt hash, never plaintext.
      error = 'Password must include letters and numbers.';
    } else if (password !== confirm) {
      error = 'Passwords do not match.';
    }

    if (error) {
      return res.status(400).render('setup', {
        isAdminSection: true,
        title: 'Create first admin',
        error,
        done: false,
      });
    }

    const hash = await hashPassword(password);
    await query(
      'INSERT INTO users (username, password_hash, role, is_active) VALUES (?, ?, ?, 1)',
      [username, hash, 'admin']
    );

    res.render('setup', {
      isAdminSection: true,
      title: 'Create first admin',
      error: '',
      done: true,
    });
  } catch (err) {
    next(err);
  }
});

module.exports = router;
