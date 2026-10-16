<?php

return [
    // Admins must confirm two-factor authentication before using the admin panel.
    'admin_requires_2fa' => (bool) env('ADMIN_REQUIRES_2FA', true),

    // Store client IP / user agent with approval decisions (disclosed in the privacy policy).
    'record_client_ip' => (bool) env('RECORD_CLIENT_IP', true),

    // Days after which IP addresses and user agents are pruned from decisions.
    'client_ip_retention_days' => (int) env('CLIENT_IP_RETENTION_DAYS', 365),

    // Send an HSTS header in production once HTTPS is validated.
    'hsts' => (bool) env('SECURITY_HSTS', false),

    // Proxies whose X-Forwarded-* headers are trusted (a local tunnel such as
    // cloudflared or ngrok, or a load balancer). Comma-separated IPs or CIDRs, or *.
    'trusted_proxies' => array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', ''))))),

    // Admin impersonation is intentionally not implemented in the MVP.
    'impersonation_enabled' => false,

    'comment_max_length' => 5000,
];
