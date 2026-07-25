'use strict';

const config = require('./config');
const { readUserFromRequest } = require('./middleware/auth');
const {
  whatsappUrl,
  mailtoUrl,
  platformLabel,
  priceRangeLabel,
  deliveryLabel,
} = require('./lib/helpers');

function localsMiddleware(req, res, next) {
  res.locals.storeName = config.store.name;
  res.locals.storeWhatsapp = config.store.whatsapp;
  res.locals.storeEmail = config.store.email;
  res.locals.user = readUserFromRequest(req);
  res.locals.whatsappUrl = whatsappUrl;
  res.locals.mailtoUrl = mailtoUrl;
  res.locals.platformLabel = platformLabel;
  res.locals.priceRangeLabel = priceRangeLabel;
  res.locals.deliveryLabel = deliveryLabel;
  res.locals.platforms = config.platforms;
  res.locals.priceRanges = config.priceRanges;
  res.locals.deliveries = config.deliveries;
  res.locals.activeNav = '';
  res.locals.isAdminSection = false;
  res.locals.flash = null;
  res.locals.errors = [];
  res.locals.old = {};
  next();
}

module.exports = { localsMiddleware };
