'use strict';

const express = require('express');
const rateLimit = require('express-rate-limit');
const multer = require('multer');
const { query } = require('../db');
const config = require('../config');
const { cleanText, clientIp, whatsappUrl, mailtoUrl, timingSafeEqualStr } = require('../lib/helpers');
const { upload, persistSellPhoto } = require('../middleware/upload');
const { verifyCsrf } = require('../middleware/csrf');

const router = express.Router();

const formLimiter = rateLimit({
  windowMs: 45 * 1000,
  max: 1,
  standardHeaders: true,
  legacyHeaders: false,
  message: 'Please wait before submitting again.',
});

router.get('/', (req, res) => {
  res.render('home', { activeNav: 'home', title: `${config.store.name} — eFootball Coins & Accounts` });
});

router.get('/services', (req, res) => {
  res.render('services', { activeNav: 'services', title: `Services — ${config.store.name}` });
});

router.get('/coins', async (req, res, next) => {
  try {
    const packages = await query(
      `SELECT id, delivery_method, coin_amount, price_label
       FROM coin_packages WHERE is_active = 1
       ORDER BY delivery_method ASC, sort_order ASC, coin_amount ASC`
    );
    const grouped = { website: [], in_game: [] };
    for (const row of packages) {
      if (grouped[row.delivery_method]) grouped[row.delivery_method].push(row);
    }
    res.render('coins', {
      activeNav: 'coins',
      title: `Coin packages — ${config.store.name}`,
      packages,
      grouped,
    });
  } catch (err) {
    next(err);
  }
});

router.get('/accounts', (req, res) => {
  res.render('accounts', {
    activeNav: 'accounts',
    title: `Buy an account — ${config.store.name}`,
    success: false,
  });
});

router.post('/accounts', verifyCsrf, formLimiter, async (req, res, next) => {
  try {
    const errors = [];
    const old = {
      name: cleanText(req.body.name),
      whatsapp: cleanText(req.body.whatsapp),
      email: cleanText(req.body.email),
      platform: cleanText(req.body.platform),
      price_range: cleanText(req.body.price_range),
      notes: cleanText(req.body.notes),
    };

    if (cleanText(req.body.website)) {
      return res.render('accounts', { activeNav: 'accounts', title: 'Buy an account', success: true, old: {} });
    }
    if (!old.name || old.name.length > 100) errors.push('Name is required (max 100 characters).');
    const wa = old.whatsapp.replace(/\D+/g, '');
    if (wa.length < 8 || wa.length > 15) errors.push('Enter a valid WhatsApp number (8–15 digits).');
    if (old.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(old.email)) errors.push('Email format looks invalid.');
    if (!config.platforms.includes(old.platform)) errors.push('Please select a valid platform.');
    if (!config.priceRanges.includes(old.price_range)) errors.push('Please select a price range.');
    if (old.notes.length > 2000) errors.push('Notes must be 2000 characters or fewer.');

    if (errors.length) {
      return res.status(400).render('accounts', {
        activeNav: 'accounts',
        title: 'Buy an account',
        errors,
        old,
        success: false,
      });
    }

    await query(
      `INSERT INTO buy_requests (name, whatsapp, email, platform, price_range, notes, ip_address)
       VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [old.name, wa, old.email || null, old.platform, old.price_range, old.notes || null, clientIp(req)]
    );

    res.render('accounts', { activeNav: 'accounts', title: 'Buy an account', success: true, old: {} });
  } catch (err) {
    next(err);
  }
});

router.get('/sell-account', (req, res) => {
  res.render('sell', {
    activeNav: 'sell',
    title: `Sell your account — ${config.store.name}`,
    success: false,
  });
});

router.post('/sell-account', formLimiter, upload.single('photo'), (req, res, next) => {
  // Multipart: CSRF must be checked after multer parses fields.
  const cookieToken = req.cookies?.gs_csrf || '';
  const bodyToken = req.body?._csrf || '';
  if (!cookieToken || !bodyToken || !timingSafeEqualStr(cookieToken, bodyToken)) {
    return res.status(403).render('error', {
      title: 'Security check failed',
      message: 'Invalid or missing CSRF token. Reload the page and try again.',
      status: 403,
      isAdminSection: false,
    });
  }
  return next();
}, async (req, res, next) => {
  try {
    const errors = [];
    const old = {
      name: cleanText(req.body.name),
      whatsapp: cleanText(req.body.whatsapp),
      email: cleanText(req.body.email),
      platform: cleanText(req.body.platform),
      account_level: cleanText(req.body.account_level),
      coin_balance: cleanText(req.body.coin_balance),
      description: cleanText(req.body.description),
      asking_price: cleanText(req.body.asking_price),
    };

    if (cleanText(req.body.website)) {
      return res.render('sell', { activeNav: 'sell', title: 'Sell your account', success: true, old: {} });
    }
    if (!old.name || old.name.length > 100) errors.push('Name is required (max 100 characters).');
    const wa = old.whatsapp.replace(/\D+/g, '');
    if (wa.length < 8 || wa.length > 15) errors.push('Enter a valid WhatsApp number (8–15 digits).');
    if (old.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(old.email)) errors.push('Email format looks invalid.');
    if (!config.platforms.includes(old.platform)) errors.push('Please select a valid platform.');
    if (!old.account_level || old.account_level.length > 50) errors.push('Account level is required.');
    if (old.coin_balance.length > 50) errors.push('Coin balance must be 50 characters or fewer.');
    if (!old.description || old.description.length > 5000) errors.push('Description is required.');
    if (old.asking_price.length > 50) errors.push('Asking price must be 50 characters or fewer.');

    let uploadResult = { ok: false };
    if (!errors.length) {
      uploadResult = await persistSellPhoto(req.file);
      if (!uploadResult.ok) errors.push(uploadResult.error);
    }

    if (errors.length) {
      return res.status(400).render('sell', {
        activeNav: 'sell',
        title: 'Sell your account',
        errors,
        old,
        success: false,
      });
    }

    await query(
      `INSERT INTO sell_requests
        (name, whatsapp, email, platform, account_level, coin_balance, description, asking_price, photo_path, ip_address)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [
        old.name,
        wa,
        old.email || null,
        old.platform,
        old.account_level,
        old.coin_balance || null,
        old.description,
        old.asking_price || null,
        uploadResult.path,
        clientIp(req),
      ]
    );

    res.render('sell', { activeNav: 'sell', title: 'Sell your account', success: true, old: {} });
  } catch (err) {
    if (err instanceof multer.MulterError || (err && err.message && String(err.message).includes('JPG'))) {
      return res.status(400).render('sell', {
        activeNav: 'sell',
        title: 'Sell your account',
        errors: [err.message || 'Upload failed.'],
        old: {},
        success: false,
      });
    }
    next(err);
  }
});

router.get('/contact', (req, res) => {
  res.render('contact', {
    activeNav: 'contact',
    title: `Contact — ${config.store.name}`,
    success: false,
    waDirect: whatsappUrl(`Hi ${config.store.name}, I have a question.`),
    mailDirect: mailtoUrl(`Question for ${config.store.name}`, 'Hi,'),
  });
});

router.post('/contact', verifyCsrf, formLimiter, async (req, res, next) => {
  try {
    const errors = [];
    const old = {
      name: cleanText(req.body.name),
      email: cleanText(req.body.email),
      whatsapp: cleanText(req.body.whatsapp),
      subject: cleanText(req.body.subject),
      body: cleanText(req.body.body),
    };
    const waDirect = whatsappUrl(`Hi ${config.store.name}, I have a question.`);
    const mailDirect = mailtoUrl(`Question for ${config.store.name}`, 'Hi,');

    if (cleanText(req.body.website)) {
      return res.render('contact', {
        activeNav: 'contact',
        title: 'Contact',
        success: true,
        old: {},
        waDirect,
        mailDirect,
      });
    }
    if (!old.name || old.name.length > 100) errors.push('Name is required.');
    if (!old.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(old.email)) errors.push('A valid email is required.');
    let wa = '';
    if (old.whatsapp) {
      wa = old.whatsapp.replace(/\D+/g, '');
      if (wa.length < 8 || wa.length > 15) errors.push('WhatsApp number looks invalid.');
    }
    if (!old.subject || old.subject.length > 200) errors.push('Subject is required.');
    if (!old.body || old.body.length > 5000) errors.push('Message is required.');

    if (errors.length) {
      return res.status(400).render('contact', {
        activeNav: 'contact',
        title: 'Contact',
        errors,
        old,
        success: false,
        waDirect,
        mailDirect,
      });
    }

    await query(
      `INSERT INTO messages (name, email, whatsapp, subject, body, ip_address) VALUES (?, ?, ?, ?, ?, ?)`,
      [old.name, old.email, wa || null, old.subject, old.body, clientIp(req)]
    );

    res.render('contact', {
      activeNav: 'contact',
      title: 'Contact',
      success: true,
      old: {},
      waDirect,
      mailDirect,
    });
  } catch (err) {
    next(err);
  }
});

module.exports = router;
