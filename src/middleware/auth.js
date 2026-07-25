'use strict';

const jwt = require('jsonwebtoken');
const bcrypt = require('bcryptjs');
const config = require('../config');
const { query } = require('../db');

// Dummy bcrypt hash for timing-safe login when user is missing.
const DUMMY_HASH = '$2a$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy';

function signToken(user) {
  // Role comes from DB only — never from client input.
  return jwt.sign(
    { sub: user.id, username: user.username, role: user.role },
    config.jwt.secret,
    { expiresIn: config.jwt.expiresIn, algorithm: 'HS256' }
  );
}

function verifyToken(token) {
  return jwt.verify(token, config.jwt.secret, { algorithms: ['HS256'] });
}

function setAuthCookie(res, token) {
  res.cookie(config.jwt.cookieName, token, {
    httpOnly: true, // Mitigates XSS token theft
    sameSite: 'lax',
    secure: config.nodeEnv === 'production',
    maxAge: 8 * 60 * 60 * 1000,
    path: '/',
  });
}

function clearAuthCookie(res) {
  res.clearCookie(config.jwt.cookieName, {
    httpOnly: true,
    sameSite: 'lax',
    secure: config.nodeEnv === 'production',
    path: '/',
  });
}

function readUserFromRequest(req) {
  const token = req.cookies?.[config.jwt.cookieName];
  if (!token) return null;
  try {
    const payload = verifyToken(token);
    if (!config.roles.includes(payload.role)) return null;
    return {
      id: Number(payload.sub),
      username: String(payload.username || ''),
      role: payload.role,
    };
  } catch {
    return null;
  }
}

function requireAuth(requiredRole = null) {
  return (req, res, next) => {
    const user = readUserFromRequest(req);
    if (!user) {
      return res.redirect('/admin/login');
    }
    if (requiredRole) {
      if (!config.roles.includes(requiredRole) || user.role !== requiredRole) {
        return res.status(403).send('Forbidden — admin role required.');
      }
    }
    req.user = user;
    return next();
  };
}

async function authenticate(username, password) {
  const rows = await query(
    'SELECT id, username, password_hash, role, is_active FROM users WHERE username = ? LIMIT 1',
    [username]
  );
  const row = rows[0];
  const hash = row ? row.password_hash : DUMMY_HASH;
  // Always bcrypt.compare — avoids username/active timing leaks via short-circuit.
  const verified = await bcrypt.compare(password, hash);
  const active = row && Number(row.is_active) === 1;
  const roleOk = row && config.roles.includes(row.role);
  if (!verified || !active || !roleOk) {
    return null;
  }
  return { id: row.id, username: row.username, role: row.role };
}

async function hashPassword(password) {
  // cost 12: deliberate slow hashing for stored passwords (never store plaintext).
  return bcrypt.hash(password, 12);
}

/** Reject trivially weak passwords on create (admin/user forms). */
function passwordStrongEnough(password) {
  if (typeof password !== 'string' || password.length < 10 || password.length > 128) return false;
  if (!/[A-Za-z]/.test(password) || !/\d/.test(password)) return false;
  return true;
}

module.exports = {
  signToken,
  setAuthCookie,
  clearAuthCookie,
  readUserFromRequest,
  requireAuth,
  authenticate,
  hashPassword,
  passwordStrongEnough,
};
