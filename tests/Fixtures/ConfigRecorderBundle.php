<?php declare(strict_types=1);

namespace Shopware\K8sMeta\Tests\Fixtures;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Stands in for an extension (e.g. shopware) and exposes every configuration it receives, in merge order.
 */
abstract class ConfigRecorderBundle extends Bundle
{
    abstract protected function alias(): string;

    public function getContainerExtension(): ExtensionInterface
    {
        return new class($this->alias()) extends Extension {
            public function __construct(private readonly string $alias)
            {
            }

            public function getAlias(): string
            {
                return $this->alias;
            }

            public function load(array $configs, ContainerBuilder $container): void
            {
                $container->setParameter('recorded.' . $this->alias, $configs);
            }
        };
    }
}
