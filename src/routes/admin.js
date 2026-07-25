'use strict';

const express = require('express');
const path = require('path');
const fs = require('fs');
const rateLimit = require('express-rate-limit');
const { query } = require('../db');
const config = require('../config');
const { cleanText } = require('../lib/helpers');
const {
  requireAuth,
  authenticate,
  signToken,
  setAuthCookie,
  clearAuthCookie,
  hashPassword,
  passwordStrongEnough,
} = require('../middleware/auth');
const { isSafePhotoFilename, UPLOAD_DIR } = require('../middleware/upload');

const router = express.Router();

const loginLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 20,
  standardHeaders: true,
  legacyHeaders: false,
  message: 'Too many login attempts. Try again later.',
});

router.get('/login', (req, res) => {
  if (res.locals.user) return res.redirect('/admin');
  res.render('admin/login', {
    isAdminSection: true,
    title: `Staff login — ${config.store.name}`,
    error: '',
    username: '',
  });
});

router.post('/login', loginLimiter, async (req, res, next) => {
  try {
    const username = cleanText(req.body.username);
    const password = String(req.body.password || '');
    const user = await authenticate(username, password);
    if (!user) {
      return res.status(401).render('admin/login', {
        isAdminSection: true,
        title: 'Staff login',
        error: 'Invalid username or password.',
        username,
      });
    }
    const token = signToken(user);
    setAuthCookie(res, token);
    await query('UPDATE users SET last_login_at = NOW() WHERE id = ?', [user.id]);
    res.redirect('/admin');
  } catch (err) {
    next(err);
  }
});

router.post('/logout', requireAuth(), (req, res) => {
  clearAuthCookie(res);
  res.redirect('/admin/login');
});

router.get('/logout', requireAuth(), (req, res) => {
  clearAuthCookie(res);
  res.redirect('/admin/login');
});

router.get('/', requireAuth(), async (req, res, next) => {
  try {
    const messages = await query(
      `SELECT id, name, email, whatsapp, subject, body, created_at, is_read
       FROM messages ORDER BY created_at DESC LIMIT 100`
    );
    const sells = await query(
      `SELECT id, name, whatsapp, email, platform, account_level, coin_balance,
              description, asking_price, photo_path, status, created_at
       FROM sell_requests ORDER BY created_at DESC LIMIT 100`
    );
    const buys = await query(
      `SELECT id, name, whatsapp, email, platform, price_range, notes, status, created_at
       FROM buy_requests ORDER BY created_at DESC LIMIT 100`
    );
    res.render('admin/dashboard', {
      isAdminSection: true,
      title: `Dashboard — ${config.store.name}`,
      messages,
      sells,
      buys,
      flash: req.query.ok || '',
    });
  } catch (err) {
    next(err);
  }
});

router.post('/messages/:id/read', requireAuth(), async (req, res, next) => {
  try {
    const id = Number(req.params.id);
    if (!Number.isInteger(id) || id < 1) return res.status(400).send('Invalid id');
    await query('UPDATE messages SET is_read = 1 WHERE id = ?', [id]);
    res.redirect('/admin?ok=Message+marked+as+read');
  } catch (err) {
    next(err);
  }
});

router.post('/sell/:id/status', requireAuth(), async (req, res, next) => {
  try {
    const id = Number(req.params.id);
    const status = cleanText(req.body.status);
    if (!Number.isInteger(id) || id < 1 || !['new', 'contacted', 'closed'].includes(status)) {
      return res.status(400).send('Invalid update');
    }
    await query('UPDATE sell_requests SET status = ? WHERE id = ?', [status, id]);
    res.redirect('/admin?ok=Sell+request+updated');
  } catch (err) {
    next(err);
  }
});

router.post('/buy/:id/status', requireAuth(), async (req, res, next) => {
  try {
    const id = Number(req.params.id);
    const status = cleanText(req.body.status);
    if (!Number.isInteger(id) || id < 1 || !['new', 'contacted', 'closed'].includes(status)) {
      return res.status(400).send('Invalid update');
    }
    await query('UPDATE buy_requests SET status = ? WHERE id = ?', [status, id]);
    res.redirect('/admin?ok=Buy+request+updated');
  } catch (err) {
    next(err);
  }
});

router.get('/photo/:filename', requireAuth(), (req, res) => {
  const name = cleanText(req.params.filename);
  if (!isSafePhotoFilename(name)) return res.status(404).send('Not found');
  const full = path.join(UPLOAD_DIR, name);
  if (!fs.existsSync(full)) return res.status(404).send('Not found');
  res.setHeader('Cache-Control', 'private, no-store');
  res.sendFile(full);
});

router.get('/coins', requireAuth('admin'), async (req, res, next) => {
  try {
    const packages = await query(
      `SELECT id, delivery_method, coin_amount, price_label, sort_order, is_active
       FROM coin_packages ORDER BY delivery_method ASC, sort_order ASC, coin_amount ASC`
    );
    res.render('admin/coins', {
      isAdminSection: true,
      title: `Coin prices — ${config.store.name}`,
      packages,
      flash: req.query.ok || '',
      error: req.query.err || '',
    });
  } catch (err) {
    next(err);
  }
});

router.post('/coins', requireAuth('admin'), async (req, res, next) => {
  try {
    const action = cleanText(req.body.action);
    const delivery = cleanText(req.body.delivery_method);
    const amount = Number(req.body.coin_amount);
    const price = cleanText(req.body.price_label);
    const sort = Number(req.body.sort_order || 0);
    const active = req.body.is_active ? 1 : 0;

    if (action === 'delete') {
      const id = Number(req.body.id);
      if (!Number.isInteger(id) || id < 1) return res.redirect('/admin/coins?err=Invalid+id');
      await query('DELETE FROM coin_packages WHERE id = ?', [id]);
      return res.redirect('/admin/coins?ok=Package+removed');
    }

    if (!config.deliveries.includes(delivery)) return res.redirect('/admin/coins?err=Invalid+delivery');
    if (!Number.isInteger(amount) || amount < 1) return res.redirect('/admin/coins?err=Invalid+amount');
    if (!price || price.length > 50) return res.redirect('/admin/coins?err=Invalid+price');
    if (!Number.isInteger(sort) || sort < 0 || sort > 9999) return res.redirect('/admin/coins?err=Invalid+sort');

    if (action === 'create') {
      await query(
        `INSERT INTO coin_packages (delivery_method, coin_amount, price_label, sort_order, is_active)
         VALUES (?, ?, ?, ?, ?)`,
        [delivery, amount, price, sort, active]
      );
      return res.redirect('/admin/coins?ok=Package+added');
    }

    if (action === 'update') {
      const id = Number(req.body.id);
      if (!Number.isInteger(id) || id < 1) return res.redirect('/admin/coins?err=Invalid+id');
      await query(
        `UPDATE coin_packages
         SET delivery_method = ?, coin_amount = ?, price_label = ?, sort_order = ?, is_active = ?
         WHERE id = ?`,
        [delivery, amount, price, sort, active, id]
      );
      return res.redirect('/admin/coins?ok=Package+updated');
    }

    res.redirect('/admin/coins?err=Unknown+action');
  } catch (err) {
    next(err);
  }
});

router.get('/users', requireAuth('admin'), async (req, res, next) => {
  try {
    const users = await query(
      `SELECT id, username, role, is_active, created_at, last_login_at
       FROM users ORDER BY role ASC, username ASC`
    );
    res.render('admin/users', {
      isAdminSection: true,
      title: `Users — ${config.store.name}`,
      users,
      flash: req.query.ok || '',
      error: req.query.err || '',
    });
  } catch (err) {
    next(err);
  }
});

router.post('/users', requireAuth('admin'), async (req, res, next) => {
  try {
    const action = cleanText(req.body.action);
    if (action === 'create') {
      const username = cleanText(req.body.username);
      const password = String(req.body.password || '');
      const role = cleanText(req.body.role);
      if (!username || username.length > 64 || !/^[a-zA-Z0-9._-]+$/.test(username)) {
        return res.redirect('/admin/users?err=Invalid+username');
      }
      if (!passwordStrongEnough(password)) {
        return res.redirect('/admin/users?err=Password+must+be+10%2B+chars+with+letters+and+numbers');
      }
      if (!config.roles.includes(role)) {
        return res.redirect('/admin/users?err=Invalid+role');
      }
      const hash = await hashPassword(password);
      try {
        await query(
          'INSERT INTO users (username, password_hash, role, is_active) VALUES (?, ?, ?, 1)',
          [username, hash, role]
        );
      } catch (e) {
        if (e && e.code === 'ER_DUP_ENTRY') return res.redirect('/admin/users?err=Username+taken');
        throw e;
      }
      return res.redirect('/admin/users?ok=User+created');
    }

    if (action === 'toggle') {
      const id = Number(req.body.id);
      if (!Number.isInteger(id) || id < 1) return res.redirect('/admin/users?err=Invalid+id');
      if (id === req.user.id) return res.redirect('/admin/users?err=Cannot+deactivate+yourself');
      await query('UPDATE users SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?', [id]);
      return res.redirect('/admin/users?ok=User+updated');
    }

    res.redirect('/admin/users?err=Unknown+action');
  } catch (err) {
    next(err);
  }
});

module.exports = router;
