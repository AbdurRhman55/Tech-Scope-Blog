<?php
/**
 *  Disclaimer
 */

require_once __DIR__ . '/includes/functions.php';

$legal_title   = 'Disclaimer';
$legal_updated = date('F j, Y');
$legal_sections = [
    [
        'h' => 'General information only',
        'b'  => [
            'All content published on ' . e(setting('site_name', SITE_NAME)) . ' is for general informational and educational purposes. While we strive for accuracy, we make no representations or warranties regarding completeness, reliability or results.',
        ],
    ],
    [
        'h' => 'Professional advice',
        'b'  => [
            'Nothing in our articles should be treated as professional advice — including but not limited to career, legal, financial, medical or technical decisions. Always seek advice from a qualified professional for your specific situation.',
        ],
    ],
    [
        'h' => 'External links',
        'b'  => [
            'This website may contain links to external sites. We do not control, endorse or assume responsibility for the content, policies or practices of any third-party sites.',
        ],
    ],
    [
        'h' => 'Errors and omissions',
        'b'  => [
            'Technology changes fast. Any code snippets, tools, versions or pricing mentioned may become outdated. We encourage readers to verify information against primary sources. Found a mistake? Please <a href="' . url('/contact') . '">report it</a> — corrections are welcome.',
        ],
    ],
    [
        'h' => 'Views expressed',
        'b'  => [
            'Opinions expressed by authors are their own and do not necessarily reflect the official position of ' . e(setting('site_name', SITE_NAME)) . '.',
        ],
    ],
    [
        'h' => 'Affiliate disclosure',
        'b'  => [
            'Some articles may contain affiliate links. If you purchase through such a link, we may earn a commission at no extra cost to you. This never affects our editorial judgement or the price you pay.',
        ],
    ],
    [
        'h' => 'Consent',
        'b'  => [
            'By using this website, you hereby consent to this disclaimer and agree to its terms.',
        ],
    ],
];

require __DIR__ . '/includes/legal-template.php';
