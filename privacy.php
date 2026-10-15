<?php
/**
 *  Privacy Policy
 */

require_once __DIR__ . '/includes/functions.php';

$legal_title   = 'Privacy Policy';
$legal_updated = date('F j, Y');
$legal_sections = [
    [
        'h' => 'Who we are',
        'b'  => [
            'This website (' . e(setting('site_name', SITE_NAME)) . ') is a technology publication. This policy explains what information we collect, why we collect it, and the choices you have. For privacy-related questions, use our <a href="' . url('/contact') . '">contact page</a>.',
        ],
    ],
    [
        'h' => 'Information we collect',
        'b'  => [
            '<strong>Contact form:</strong> when you reach out, we store your name, email address, subject and message so we can respond.',
            '<strong>Comments:</strong> if you comment on an article, we store your name, email (never displayed), optional website, and the comment itself.',
            '<strong>Analytics:</strong> we may use privacy-respecting analytics to understand aggregate traffic. No cross-site tracking cookies are set.',
            '<strong>Technical data:</strong> standard server logs (IP address, browser, referrer) may be retained for security and debugging.',
        ],
    ],
    [
        'h' => 'How we use your information',
        'b'  => [
            'We use collected data only to: respond to your messages, moderate and display comments, keep the site secure, and improve our content.',
            'We do <strong>not</strong> sell, rent or trade your personal information to third parties.',
        ],
    ],
    [
        'h' => 'Cookies',
        'b'  => [
            'We use a single essential session cookie required for security features such as form protection and admin login. It is not used for advertising.',
            'You can disable cookies in your browser settings; core reading functionality will still work.',
        ],
    ],
    [
        'h' => 'Third-party links',
        'b'  => [
            'Articles may link to external resources. We are not responsible for the privacy practices of those sites. We recommend reviewing their policies.',
        ],
    ],
    [
        'h' => 'Data retention',
        'b'  => [
            'Contact messages and comments are retained as long as needed for moderation and record-keeping. You may request deletion of your data at any time.',
        ],
    ],
    [
        'h' => 'Your rights',
        'b'  => [
            'You may request access to, correction of, or deletion of your personal data by contacting us. We respond to verified requests within a reasonable time.',
        ],
    ],
    [
        'h' => 'Children&rsquo;s privacy',
        'b'  => [
            'This site is intended for general audiences and is not directed at children under 13. We do not knowingly collect data from children.',
        ],
    ],
    [
        'h' => 'Changes to this policy',
        'b'  => [
            'We may update this policy occasionally. The &ldquo;last updated&rdquo; date at the top reflects the latest revision.',
        ],
    ],
];

require __DIR__ . '/includes/legal-template.php';
