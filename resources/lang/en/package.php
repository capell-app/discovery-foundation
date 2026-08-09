<?php

declare(strict_types=1);

return [
    'health' => [
        'public_urls' => [
            'label' => 'Discovery Foundation public URL contributors',
            'passed' => 'The CMS page contributor is registered with :count contributor(s).',
            'failed' => 'The CMS page contributor is not registered.',
            'remediation' => 'Ensure DiscoveryFoundationServiceProvider is installed and registers its CMS page contributor.',
        ],
    ],
];
