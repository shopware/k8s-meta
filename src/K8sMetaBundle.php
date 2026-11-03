<?php declare(strict_types=1);

namespace Shopware\K8sMeta;

use Shopware\K8sMeta\Configuration\OperatorConfiguration;
use Shopware\K8sMeta\Configuration\ShopwareVersion;
use Shopware\K8sMeta\DependencyInjection\Compiler\SqsTransportFactoryPass;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class K8sMetaBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new SqsTransportFactoryPass());

        foreach ($this->configuration()->defaults() as $alias => $config) {
            if ($container->hasExtension($alias)) {
                $container->loadFromExtension($alias, $config);
            }
        }
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $configuration = $this->configuration();

        foreach ($configuration->parameters() as $name => $value) {
            if (!$builder->hasParameter($name)) {
                $builder->setParameter($name, $value);
            }
        }

        foreach ($configuration->enforced($builder->getParameter('kernel.environment')) as $alias => $config) {
            if ($builder->hasExtension($alias)) {
                $container->extension($alias, $config);
            }
        }
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();

        $services->set('Redis', \Redis::class)
            ->factory([RedisAdapter::class, 'createConnection'])
            ->args(['%env(K8S_REDIS_SESSION_DSN)%']);

        $services->set(RedisSessionHandler::class)
            ->args([service('Redis'), ['prefix' => 'sess_']]);
    }

    private function configuration(): OperatorConfiguration
    {
        return new OperatorConfiguration(ShopwareVersion::isV6_6());
    }
}
