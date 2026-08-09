<?php

declare(strict_types=1);

use Capell\DiscoveryFoundation\Tests\DiscoveryFoundationTestCase;

pest()->extend(DiscoveryFoundationTestCase::class)->group('discovery-foundation')->in('.');
