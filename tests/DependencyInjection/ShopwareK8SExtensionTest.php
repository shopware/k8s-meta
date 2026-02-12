<?php

declare(strict_types=1);

namespace Shopware\K8sMeta\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Shopware\K8sMeta\ShopwareK8SBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

final class ShopwareK8SExtensionTest extends TestCase
{
    public function testDoesNothingWhenKernelBundlesParameterIsMissing(): void
    {
        $container = $this->createContainer(['framework', 'shopware', 'elasticsearch']);
        $extension = $this->createBundleExtension();

        $extension->prepend($container);

        self::assertSame([], $container->getExtensionConfig('framework'));
        self::assertSame([], $container->getExtensionConfig('shopware'));
        self::assertSame([], $container->getExtensionConfig('elasticsearch'));
    }

    public function testDoesNothingWhenKernelBundlesParameterIsNotAnArray(): void
    {
        $container = $this->createContainer(['framework', 'shopware', 'elasticsearch']);
        $container->setParameter('kernel.bundles', 'invalid');
        $extension = $this->createBundleExtension();

        $extension->prepend($container);

        self::assertSame([], $container->getExtensionConfig('framework'));
        self::assertSame([], $container->getExtensionConfig('shopware'));
        self::assertSame([], $container->getExtensionConfig('elasticsearch'));
    }

    public function testLoadsFrameworkConfigWhenFrameworkBundleIsPresent(): void
    {
        $container = $this->createContainer(['framework']);
        $container->setParameter('kernel.bundles', ['FrameworkBundle' => true]);
        $extension = $this->createBundleExtension();

        $extension->prepend($container);

        self::assertNotSame([], $container->getExtensionConfig('framework'));
        self::assertSame([], $container->getExtensionConfig('shopware'));
        self::assertSame([], $container->getExtensionConfig('elasticsearch'));
    }

    public function testLoadsShopwareConfigAndRegistersServicesWhenShopwareCoreBundleIsPresent(): void
    {
        $container = $this->createContainer(['shopware']);
        $container->setParameter('kernel.bundles', ['ShopwareCoreBundle' => true]);
        $extension = $this->createBundleExtension();

        $extension->prepend($container);
        $extension->load([[]], $container);

        self::assertNotSame([], $container->getExtensionConfig('shopware'));
        self::assertTrue($container->hasDefinition('shopware.session.redis_handler'));
        self::assertSame([], $container->getExtensionConfig('framework'));
        self::assertSame([], $container->getExtensionConfig('elasticsearch'));
    }

    public function testLoadsElasticsearchConfigWhenAnyElasticsearchBundleIsPresent(): void
    {
        $container = $this->createContainer(['elasticsearch']);
        $container->setParameter('kernel.bundles', ['FroshElasticsearchBundle' => true]);
        $extension = $this->createBundleExtension();

        $extension->prepend($container);

        self::assertNotSame([], $container->getExtensionConfig('elasticsearch'));
    }

    public function testLoadsElasticsearchConfigForShopwareElasticsearchBundle(): void
    {
        $container = $this->createContainer(['elasticsearch']);
        $container->setParameter('kernel.bundles', ['ShopwareElasticsearchBundle' => true]);
        $extension = $this->createBundleExtension();

        $extension->prepend($container);

        self::assertNotSame([], $container->getExtensionConfig('elasticsearch'));
    }

    private function createBundleExtension(): Extension&PrependExtensionInterface
    {
        $bundle = new ShopwareK8SBundle();
        $extension = $bundle->getContainerExtension();

        self::assertInstanceOf(Extension::class, $extension);
        self::assertInstanceOf(PrependExtensionInterface::class, $extension);

        return $extension;
    }

    /**
     * @param list<string> $aliases
     */
    private function createContainer(array $aliases): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir());
        foreach ($aliases as $alias) {
            $container->registerExtension(new DummyExtension($alias));
        }

        return $container;
    }
}

final class DummyExtension extends Extension
{
    public function __construct(private readonly string $alias)
    {
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
    }
}
