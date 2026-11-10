<?php

declare(strict_types=1);

return [
    'health' => [
        'public_urls' => [
            'label' => 'Discovery Core: public URL sources valid',
            'passed' => 'The CMS page contributor is registered. Contributors registered: :count.',
            'failed' => 'The CMS page contributor is not registered.',
            'remediation' => 'Ensure DiscoveryFoundationServiceProvider is installed and registers its CMS page contributor.',
        ],
    ],
];
