'use strict';

const path = require('path');
const express = require('express');
const cookieParser = require('cookie-parser');
const helmet = require('helmet');
const config = require('./config');
const { localsMiddleware } = require('./middleware/locals');
const { attachCsrf, verifyCsrf } = require('./middleware/csrf');
const publicRoutes = require('./routes/public');
const adminRoutes = require('./routes/admin');
const setupRoutes = require('./routes/setup');

const app = express();

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));
app.set('trust proxy', 1);

app.use(
  helmet({
    contentSecurityPolicy: {
      useDefaults: true,
      directives: {
        defaultSrc: ["'self'"],
        scriptSrc: ["'self'"],
        styleSrc: ["'self'", 'https://fonts.googleapis.com'],
        fontSrc: ["'self'", 'https://fonts.gstatic.com'],
        imgSrc: ["'self'", 'data:'],
        connectSrc: ["'self'"],
        frameAncestors: ["'none'"],
        baseUri: ["'self'"],
        formAction: ["'self'"],
      },
    },
    crossOriginEmbedderPolicy: false,
  })
);

app.use(express.urlencoded({ extended: false, limit: '32kb' }));
app.use(cookieParser());
app.use(express.static(path.join(__dirname, '..', 'public')));
// Do NOT serve uploads/sell statically — only via authenticated /admin/photo/:filename

app.use(localsMiddleware);
app.use(attachCsrf);
// CSRF verification is applied on routers; sell-account verifies after multer parses multipart.

app.use(publicRoutes);
app.use('/admin', verifyCsrf, adminRoutes);
// Setup only mounted when explicitly enabled — reduces attack surface.
if (config.setupEnabled) {
  app.use('/setup', verifyCsrf, setupRoutes);
} else {
  app.use('/setup', (_req, res) => {
    res.status(403).type('text').send('Setup is disabled.');
  });
}

app.use((req, res) => {
  res.status(404).render('error', {
    title: 'Not found',
    message: 'That page does not exist.',
    status: 404,
  });
});

app.use((err, req, res, _next) => {
  console.error(err);
  res.status(500).render('error', {
    title: 'Something went wrong',
    message: 'Please try again later.',
    status: 500,
  });
});

module.exports = app;
