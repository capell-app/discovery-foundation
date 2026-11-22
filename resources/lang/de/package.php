<?php

declare(strict_types=1);

return [
    'health' => [
        'public_urls' => [
            'label' => 'Discovery Foundation: Quellen für öffentliche URLs gültig',
            'passed' => 'Der CMS-Seiten-Contributor ist registriert. Registrierte Contributors: :count.',
            'failed' => 'Der CMS-Seiten-Contributor ist nicht registriert.',
            'remediation' => 'Stellen Sie sicher, dass DiscoveryFoundationServiceProvider installiert ist und seinen CMS-Seiten-Contributor registriert.',
        ],
    ],
];
