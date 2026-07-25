<?php
declare(strict_types=1);

/**
 * Database connection (PDO).
 *
 * SECURITY NOTES:
 * - Edit the placeholders below for your local XAMPP/MySQL install.
 * - Production MUST use a dedicated MySQL user scoped ONLY to this database
 *   (not root). Grant: SELECT, INSERT, UPDATE, DELETE — no FILE, no SUPER.
 * - Never commit real passwords. Keep this file out of public repos with secrets.
 */

// --- Edit these for your environment ---
const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'gunneeeers_store';
const DB_USER = 'store_app';           // Prefer a least-privilege user, not root
const DB_PASS = 'CHANGE_ME_DB_PASSWORD'; // Placeholder — set locally; never commit real secrets
const DB_CHARSET = 'utf8mb4';

/**
 * Returns a shared PDO instance.
 * Never lets raw PDOException messages reach the browser.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            // EMULATE_PREPARES off: forces real server-side prepares (not client-side
            // string quoting), which is the correct defense against SQL injection.
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Prefer native types where possible; still escape everything for HTML.
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
    } catch (PDOException $e) {
        // Log server-side only — never echo $e->getMessage() (leaks paths/credentials hints).
        error_log('DB connection failed: ' . $e->getMessage());
        throw new RuntimeException('Database temporarily unavailable. Please try again later.');
    }

    return $pdo;
}
