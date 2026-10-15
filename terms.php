<?php
/**
 *  Terms & Conditions
 */

require_once __DIR__ . '/includes/functions.php';

$legal_title   = 'Terms & Conditions';
$legal_updated = date('F j, Y');
$legal_sections = [
    [
        'h' => 'Acceptance of terms',
        'b'  => [
            'By accessing ' . e(setting('site_name', SITE_NAME)) . ', you agree to these Terms &amp; Conditions. If you do not agree, please do not use this website.',
        ],
    ],
    [
        'h' => 'Use of the website',
        'b'  => [
            'You agree to use the site for lawful purposes only. You must not attempt to disrupt, damage, overload or gain unauthorized access to the website or its database.',
            'Scraping, bulk downloading, or reproducing content without written permission is prohibited.',
        ],
    ],
    [
        'h' => 'Intellectual property',
        'b'  => [
            'Unless otherwise stated, all articles, graphics and design elements are owned by ' . e(setting('site_name', SITE_NAME)) . '.',
            'You may share excerpts with clear attribution and a link to the original article. Full republication requires prior written consent.',
        ],
    ],
    [
        'h' => 'User contributions',
        'b'  => [
            'Comments and messages must be respectful, on-topic and non-infringing. We reserve the right to remove any content that is spammy, abusive, misleading or unlawful.',
            'By submitting a comment, you grant us a non-exclusive, royalty-free licence to display and moderate it on the website.',
        ],
    ],
    [
        'h' => 'No professional advice',
        'b'  => [
            'Content is provided for general information and educational purposes only. It does not constitute professional, legal, financial or career advice. Always consult a qualified professional before acting on information you read here.',
        ],
    ],
    [
        'h' => 'Disclaimer of warranties',
        'b'  => [
            'The website is provided &ldquo;as is&rdquo; without warranties of any kind, express or implied. We do not guarantee that the site will be error-free, uninterrupted or always available.',
        ],
    ],
    [
        'h' => 'Limitation of liability',
        'b'  => [
            'To the maximum extent permitted by law, ' . e(setting('site_name', SITE_NAME)) . ' shall not be liable for any indirect, incidental or consequential damages arising from your use of the website.',
        ],
    ],
    [
        'h' => 'Governing law',
        'b'  => [
            'These terms are governed by applicable laws. Any disputes shall be resolved in the competent courts of the jurisdiction where the site operator resides.',
        ],
    ],
    [
        'h' => 'Changes to these terms',
        'b'  => [
            'We may revise these terms at any time. Continued use of the website after changes constitutes acceptance of the revised terms.',
        ],
    ],
];

require __DIR__ . '/includes/legal-template.php';
