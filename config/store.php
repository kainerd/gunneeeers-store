<?php
declare(strict_types=1);

/**
 * Store identity and outbound contact helpers (WhatsApp / email).
 *
 * Edit the placeholders below before going live.
 * No payment processing lives here — order CTAs only deep-link to a human.
 */

const STORE_NAME = 'Gunneeeers Store';

// Digits only, country code included, no + or spaces. Example: 2126XXXXXXXX
const STORE_WHATSAPP = '249112780717';

const STORE_EMAIL = 'emadsesko@gmail.com';

/**
 * Strip CR/LF and other control chars that could be abused in URL / mailto params.
 * Defensive even though these are query values, not real SMTP headers.
 */
function sanitize_link_text(string $value): string
{
    // Remove ASCII control characters (including \r \n) and trim.
    $clean = preg_replace('/[\x00-\x1F\x7F]+/u', '', $value) ?? '';
    return trim($clean);
}

/**
 * Build a wa.me deep link with a pre-filled message.
 */
function whatsapp_order_url(string $message): string
{
    $number = preg_replace('/\D+/', '', STORE_WHATSAPP) ?? '';
    $text = sanitize_link_text($message);
    return 'https://wa.me/' . rawurlencode($number) . '?text=' . rawurlencode($text);
}

/**
 * Build a mailto: link with subject + body (CR/LF already stripped from inputs).
 */
function mailto_order_url(string $subject, string $body): string
{
    $to = sanitize_link_text(STORE_EMAIL);
    // Keep address readable for mail clients; only encode query params.
    // Still reject CR/LF via sanitize_link_text to block header-injection style tricks.
    $subject = sanitize_link_text($subject);
    $body = sanitize_link_text($body);
    return 'mailto:' . $to
        . '?subject=' . rawurlencode($subject)
        . '&body=' . rawurlencode($body);
}
