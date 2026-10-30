<?php declare(strict_types=1);

namespace Shopware\K8sMeta\Tests\Fixtures;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Stands in for Shopware core, which loads its own default configuration in build().
 */
class ShopwareRecorderBundle extends ConfigRecorderBundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->loadFromExtension('shopware', [
            'admin_worker' => ['enable_admin_worker' => true],
            'http_cache' => ['stale_while_revalidate' => 'core', 'stale_if_error' => 'core'],
        ]);
    }

    protected function alias(): string
    {
        return 'shopware';
    }
}
