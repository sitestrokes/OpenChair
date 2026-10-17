<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Security headers on every response — docs/70-radar-build.md §6.
 *
 * Runs as a pre_system hook, before any output, so every page (product, ported
 * module, public share, error page) gets the same floor.
 *
 * Two tiers, on purpose:
 *   ENFORCED  headers that cannot break a working page: no framing by other
 *             sites (clickjacking), no MIME sniffing, a strict referrer policy,
 *             no plugins, no <base> hijack, HSTS when the request is HTTPS.
 *   REPORT    the full Content-Security-Policy goes out as Report-Only. The
 *             ported common modules still embed third-party players and CDNs
 *             (YouTube, Vimeo, jsDelivr, HERE maps) and inline scripts; enforcing
 *             script-src today would silently break those pages. Promote it to
 *             the enforced header once the reports come back clean.
 *
 * Public share links (/c/{token}) additionally send no Referer at all — the
 * token IS the credential, and a link clicked from the shared page must not
 * hand it to another site.
 */
class Oc_security_headers
{
    public function send()
    {
        if (PHP_SAPI === 'cli' || headers_sent()) { return; }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
              || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
        $path  = (string) parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/', PHP_URL_PATH);
        $share = (bool) preg_match('#/c/[a-f0-9]{32}/?$#', $path);

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: ' . ($share ? 'no-referrer' : 'strict-origin-when-cross-origin'));
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        if ($https) { header('Strict-Transport-Security: max-age=31536000; includeSubDomains'); }

        // Directives that are safe everywhere are enforced; the full policy is
        // reported until the ported pages are cleaned up.
        header("Content-Security-Policy: frame-ancestors 'self'; object-src 'none'; base-uri 'self'");
        header('Content-Security-Policy-Report-Only: ' . implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://ajax.googleapis.com https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com",
            "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "object-src 'none'",
            "base-uri 'self'",
        ]));
    }
}
