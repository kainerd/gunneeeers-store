'use strict';

const path = require('path');
const fs = require('fs');
const multer = require('multer');
const FileType = require('file-type');
const crypto = require('crypto');

const UPLOAD_DIR = path.join(__dirname, '..', '..', 'uploads', 'sell');
const MAX_BYTES = 3 * 1024 * 1024;
const ALLOWED = {
  'image/jpeg': 'jpg',
  'image/png': 'png',
  'image/webp': 'webp',
};

if (!fs.existsSync(UPLOAD_DIR)) {
  fs.mkdirSync(UPLOAD_DIR, { recursive: true });
}

const storage = multer.memoryStorage();

const upload = multer({
  storage,
  limits: { fileSize: MAX_BYTES, files: 1 },
  fileFilter(_req, file, cb) {
    // Client MIME is untrusted — final check uses file-type on buffer.
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.mimetype)) {
      return cb(new Error('Only JPG, PNG, or WebP images are allowed.'));
    }
    return cb(null, true);
  },
});

async function persistSellPhoto(file) {
  if (!file) {
    return { ok: false, error: 'A screenshot/photo of the account is required.' };
  }
  const detected = await FileType.fromBuffer(file.buffer);
  if (!detected || !ALLOWED[detected.mime]) {
    return { ok: false, error: 'Only JPG, PNG, or WebP images are allowed.' };
  }
  const ext = ALLOWED[detected.mime];
  const name = `${crypto.randomBytes(16).toString('hex')}.${ext}`;
  const full = path.join(UPLOAD_DIR, name);
  await fs.promises.writeFile(full, file.buffer);
  return { ok: true, path: `uploads/sell/${name}`, filename: name };
}

function isSafePhotoFilename(name) {
  return /^[a-f0-9]{32}\.(jpg|png|webp)$/.test(name);
}

module.exports = {
  upload,
  persistSellPhoto,
  isSafePhotoFilename,
  UPLOAD_DIR,
};
