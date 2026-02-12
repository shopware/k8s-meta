<?php

declare(strict_types=1);

namespace Shopware\K8sMeta\Tests\Integration;

use Composer\InstalledVersions;
use Shopware\Core\Checkout\Checkout as ShopwareCheckoutBundle;
use Shopware\Core\Content\Content as ShopwareContentBundle;
use Shopware\Core\DevOps\DevOps as ShopwareDevOpsBundle;
use Shopware\Core\Kernel as ShopwareCoreKernel;
use Shopware\Core\Framework\Framework as ShopwareFrameworkBundle;
use Shopware\Core\Maintenance\Maintenance as ShopwareMaintenanceBundle;
use Shopware\Core\Profiling\Profiling as ShopwareProfilingBundle;
use Shopware\Core\Service\Service as ShopwareServiceBundle;
use Shopware\Core\System\System as ShopwareSystemBundle;
use Shopware\Elasticsearch\Elasticsearch as ShopwareElasticsearchBundle;
use Shopware\K8sMeta\ShopwareK8SBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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
        self::assertFalse($container->hasParameter('shopware.deployment.cluster_setup'));
        self::assertFalse($container->hasParameter('env(K8S_ES_NUMBER_OF_REPLICAS)'));
    }

    public function testBootKernelLoadsShopwareAndServicesConfigWhenShopwareCoreBundleIsPresent(): void
    {
        self::bootKernel(['shopwareCore' => true, 'elasticsearch' => false]);

        $container = self::getContainer();

        self::assertTrue($container->hasParameter('env(K8S_REDIS_SESSION_DSN)'));
        self::assertTrue($container->hasParameter('env(K8S_FILESYSTEM_PUBLIC_BUCKET)'));
    }

    public function testBootKernelLoadsElasticsearchConfigWhenElasticsearchBundleIsPresent(): void
    {
        self::bootKernel(['shopwareCore' => false, 'elasticsearch' => true]);

        $container = self::getContainer();

        self::assertTrue($container->hasParameter('env(K8S_ES_NUMBER_OF_REPLICAS)'));
        self::assertTrue($container->hasParameter('env(K8S_ES_NUMBER_OF_SHARDS)'));
        self::assertNull($container->getParameter('env(K8S_ES_NUMBER_OF_REPLICAS)'));
        self::assertNull($container->getParameter('env(K8S_ES_NUMBER_OF_SHARDS)'));
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
        parent::__construct('dev', true);
    }

    public function registerBundles(): iterable
    {
        $bundles = [
            new FrameworkBundle(),
            new MonologBundle(),
            new TwigBundle(),
            new ShopwareK8SBundle(),
        ];

        if ($this->withShopwareCoreBundle || $this->withElasticsearchBundle) {
            $bundles[] = new ShopwareFrameworkBundle();
            $bundles[] = new ShopwareSystemBundle();
            $bundles[] = new ShopwareContentBundle();
            $bundles[] = new ShopwareCheckoutBundle();
            $bundles[] = new ShopwareServiceBundle();
            $bundles[] = new ShopwareDevOpsBundle();
            $bundles[] = new ShopwareMaintenanceBundle();
            $bundles[] = new ShopwareProfilingBundle();
        }

        if ($this->withElasticsearchBundle) {
            $bundles[] = new ShopwareElasticsearchBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(static function (ContainerBuilder $container): void {
            $container->setParameter('kernel.cache.hash', 'test');
            $container->loadFromExtension('framework', [
                'test' => true,
                'secret' => 'test',
                'router' => [
                    'resource' => 'kernel::loadRoutes',
                    'type' => 'service',
                ],
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

    protected function getKernelParameters(): array
    {
        $parameters = parent::getKernelParameters();
        $projectDir = rtrim($this->getProjectDir(), '/');
        $coreDir = \dirname((string) (new \ReflectionClass(ShopwareCoreKernel::class))->getFileName());

        return array_merge(
            $parameters,
            [
                'kernel.cache.hash' => 'test',
                'kernel.shopware_version' => InstalledVersions::getPrettyVersion('shopware/core') ?? '6.7.0.0',
                'kernel.shopware_version_revision' => null,
                'kernel.shopware_core_dir' => $coreDir,
                'kernel.plugin_dir' => $projectDir . '/custom/plugins',
                'kernel.plugin_infos' => [],
                'kernel.app_dir' => $projectDir . '/custom/apps',
                'kernel.active_plugins' => [],
            ],
        );
    }
}
