<?php
// Security headers to protect against various attacks

// Prevent MIME type sniffing
header('X-Content-Type-Options: nosniff');

// Prevent clickjacking attacks
header('X-Frame-Options: SAMEORIGIN');

// Enable XSS protection (for older browsers)
header('X-XSS-Protection: 1; mode=block');

// Content Security Policy (CSP) - adjust as needed for your site
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://kit.fontawesome.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com https://kit.fontawesome.com; connect-src 'self'; frame-src 'none';");

// Strict Transport Security (if using HTTPS)
// header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');

// Referrer Policy
header('Referrer-Policy: no-referrer-when-downgrade');

// Permissions Policy (restrict browser features)
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// Remove server information
header_remove('X-Powered-By');

// Prevent caching of sensitive content
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
?>