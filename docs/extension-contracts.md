# Worked extension examples

These developer-facing recipes are kept beside the package contract. Replace the example values with the site-specific records and data objects used by the calling workflow.

<!-- example: contract Capell\DiscoveryFoundation\Contracts\PublicUrlContributor -->

```php
<?php
declare(strict_types=1);
final class ExamplePublicUrlContributorImplementation implements \Capell\DiscoveryFoundation\Contracts\PublicUrlContributor
{
    /**
     * @return Collection<int, PublicUrlData>
     */
    public function publicUrls(): \Illuminate\Support\Collection
    {
        throw new LogicException('Implement this package contract for the calling site.');
    }
}

app()->bind(\Capell\DiscoveryFoundation\Contracts\PublicUrlContributor::class, ExamplePublicUrlContributorImplementation::class);
```

<!-- example: action buildPublicUrlRegistry -->

```php
<?php
declare(strict_types=1);
$inputs = []; // Supply the arguments required by the action handle() method.
resolve(\Capell\DiscoveryFoundation\Actions\BuildPublicUrlRegistryAction::class)->handle(...$inputs);
```

<!-- example: action discoverPublicPages -->

```php
<?php
declare(strict_types=1);
$inputs = []; // Supply the arguments required by the action handle() method.
resolve(\Capell\DiscoveryFoundation\Actions\DiscoverPublicPagesAction::class)->handle(...$inputs);
```

<!-- example: action replacePhrase -->

```php
<?php
declare(strict_types=1);
$inputs = []; // Supply the arguments required by the action handle() method.
resolve(\Capell\DiscoveryFoundation\Actions\ReplacePhraseAction::class)->handle(...$inputs);
```
