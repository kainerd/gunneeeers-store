'use strict';

const { newCsrfToken, timingSafeEqualStr } = require('../lib/helpers');

const CSRF_COOKIE = 'gs_csrf';

function attachCsrf(req, res, next) {
  let token = req.cookies?.[CSRF_COOKIE];
  if (!token || typeof token !== 'string' || token.length < 32) {
    token = newCsrfToken();
    res.cookie(CSRF_COOKIE, token, {
      httpOnly: false, // Double-submit: readable by form JS if needed; compared server-side
      sameSite: 'lax',
      secure: process.env.NODE_ENV === 'production',
      path: '/',
    });
  }
  res.locals.csrfToken = token;
  next();
}

function verifyCsrf(req, res, next) {
  if (req.method === 'GET' || req.method === 'HEAD' || req.method === 'OPTIONS') {
    return next();
  }
  const cookieToken = req.cookies?.[CSRF_COOKIE] || '';
  const bodyToken = req.body?._csrf || '';
  if (!cookieToken || !bodyToken || !timingSafeEqualStr(cookieToken, bodyToken)) {
    return res.status(403).render('error', {
      title: 'Security check failed',
      message: 'Invalid or missing CSRF token. Reload the page and try again.',
      status: 403,
    });
  }
  return next();
}

module.exports = { attachCsrf, verifyCsrf, CSRF_COOKIE };
