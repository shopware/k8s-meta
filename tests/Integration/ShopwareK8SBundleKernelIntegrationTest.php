<?php

declare(strict_types=1);

namespace Shopware\K8sMeta\Tests\Integration;

use Shopware\K8sMeta\ShopwareK8SBundle;
use Shopware\Core\Framework\Framework as ShopwareFrameworkBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

final class ShopwareK8SBundleKernelIntegrationTest extends KernelTestCase
{
    public function testBootKernelLoadsFrameworkConfig(): void
    {
        self::bootKernel(['shopwareCore' => false, 'elasticsearch' => false]);

        $container = self::getContainer();

        self::assertTrue($container->hasParameter('env(K8S_CACHE_HOST)'));
        self::assertSame('localhost', $container->getParameter('env(K8S_CACHE_HOST)'));
        self::assertFalse($container->hasParameter('k8s_meta.test.shopware_config_loaded'));
        self::assertFalse($container->hasParameter('k8s_meta.test.elasticsearch_config_loaded'));
    }

    public function testBootKernelLoadsShopwareAndServicesConfigWhenShopwareCoreBundleIsPresent(): void
    {
        self::bootKernel(['shopwareCore' => true, 'elasticsearch' => false]);

        $container = self::getContainer();

        self::assertTrue($container->hasParameter('shopware.deployment.cluster_setup'));
        self::assertTrue($container->getParameter('shopware.deployment.cluster_setup'));
    }

    public function testBootKernelLoadsElasticsearchConfigWhenElasticsearchBundleIsPresent(): void
    {
        self::bootKernel(['shopwareCore' => false, 'elasticsearch' => true]);

        $container = self::getContainer();

        self::assertTrue($container->hasParameter('k8s_meta.test.elasticsearch_config_loaded'));
        self::assertTrue($container->getParameter('k8s_meta.test.elasticsearch_config_loaded'));
    }

    protected static function createKernel(array $options = []): Kernel
    {
        return new IntegrationTestKernel(
            (bool) ($options['shopwareCore'] ?? false),
            (bool) ($options['elasticsearch'] ?? false),
        );
    }
}

final class IntegrationTestKernel extends Kernel
{
    public function __construct(
        private readonly bool $withShopwareCoreBundle,
        private readonly bool $withElasticsearchBundle,
    ) {
        parent::__construct('test', true);
    }

    public function registerBundles(): iterable
    {
        $bundles = [
            new FrameworkBundle(),
            new ShopwareK8SBundle(),
        ];

        if ($this->withShopwareCoreBundle) {
            $bundles[] = new LightweightShopwareCoreBundle();
        }

        if ($this->withElasticsearchBundle) {
            $bundles[] = new ShopwareElasticsearchBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', [
                'test' => true,
                'secret' => 'test',
            ]);
        });
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
    }

    public function getCacheDir(): string
    {
        $cacheKey = json_encode([
            'shopware_core' => $this->withShopwareCoreBundle,
            'elasticsearch' => $this->withElasticsearchBundle,
        ], \JSON_THROW_ON_ERROR);

        return sys_get_temp_dir() . '/k8s-meta-tests/cache-' . md5($cacheKey);
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/k8s-meta-tests/log';
    }
}

final class LightweightShopwareCoreBundle extends ShopwareFrameworkBundle
{
    public function boot(): void
    {
        // Intentionally skipped: full Shopware boot needs services from other core bundles.
    }

    public function build(ContainerBuilder $container): void
    {
        // Only required for ShopwareK8SBundle service wiring in this test.
        $container->register('shopware.redis.connection.session', \stdClass::class);
    }
}

final class ShopwareElasticsearchBundle extends Bundle
{
    public function getContainerExtension(): ?Extension
    {
        return new ShopwareElasticsearchExtension();
    }
}

final class ShopwareElasticsearchExtension extends Extension
{
    public function getAlias(): string
    {
        return 'elasticsearch';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->resolveEnvPlaceholders($configs);
        $container->setParameter('k8s_meta.test.elasticsearch_config_loaded', $configs !== []);
    }
}
