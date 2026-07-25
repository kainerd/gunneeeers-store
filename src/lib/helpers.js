'use strict';

const crypto = require('crypto');
const config = require('../config');

function sanitizeLinkText(value) {
  return String(value || '')
    .replace(/[\x00-\x1F\x7F]+/g, '')
    .trim();
}

function cleanText(value) {
  return String(value || '')
    .trim()
    .replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/g, '');
}

function whatsappUrl(message) {
  const number = config.store.whatsapp;
  const text = sanitizeLinkText(message);
  return `https://wa.me/${encodeURIComponent(number)}?text=${encodeURIComponent(text)}`;
}

function mailtoUrl(subject, body) {
  const to = sanitizeLinkText(config.store.email);
  return `mailto:${to}?subject=${encodeURIComponent(sanitizeLinkText(subject))}&body=${encodeURIComponent(sanitizeLinkText(body))}`;
}

function platformLabel(platform) {
  return ({ mobile: 'Mobile', ps: 'PlayStation', xbox: 'Xbox', pc: 'PC' })[platform] || platform;
}

function priceRangeLabel(range) {
  return (
    {
      under_25: 'Under $25',
      '25_50': '$25 – $50',
      '50_100': '$50 – $100',
      '100_200': '$100 – $200',
      '200_plus': '$200+',
    }[range] || range
  );
}

function deliveryLabel(method) {
  return ({ website: 'Website top-up', in_game: 'In-game trade' })[method] || method;
}

function clientIp(req) {
  // Use connection remoteAddress only — do not trust X-Forwarded-For without a trusted proxy config.
  return String(req.socket.remoteAddress || '').slice(0, 45) || null;
}

function newCsrfToken() {
  return crypto.randomBytes(32).toString('hex');
}

function timingSafeEqualStr(a, b) {
  const ba = Buffer.from(String(a || ''), 'utf8');
  const bb = Buffer.from(String(b || ''), 'utf8');
  if (ba.length !== bb.length) {
    // Compare against self to keep timing roughly constant, then fail.
    crypto.timingSafeEqual(ba, ba);
    return false;
  }
  return crypto.timingSafeEqual(ba, bb);
}

module.exports = {
  sanitizeLinkText,
  cleanText,
  whatsappUrl,
  mailtoUrl,
  platformLabel,
  priceRangeLabel,
  deliveryLabel,
  clientIp,
  newCsrfToken,
  timingSafeEqualStr,
};
