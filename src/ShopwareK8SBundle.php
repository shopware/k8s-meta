<?php

declare(strict_types=1);

namespace Shopware\K8sMeta;

use Shopware\K8sMeta\DependencyInjection\Prepend\ElasticsearchPrepend;
use Shopware\K8sMeta\DependencyInjection\Prepend\FrameworkPrepend;
use Shopware\K8sMeta\DependencyInjection\Prepend\ShopwarePrepend;
use Shopware\K8sMeta\DependencyInjection\ServiceRegistration;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class ShopwareK8SBundle extends AbstractBundle
{
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if (!$builder->hasParameter('kernel.bundles')) {
            return;
        }

        $bundles = $builder->getParameter('kernel.bundles');
        if (!\is_array($bundles)) {
            return;
        }

        if ($builder->hasExtension('framework')) {
            (new FrameworkPrepend())->prepend($container);
        }

        if ($builder->hasExtension('shopware')) {
            (new ShopwarePrepend())->prepend($container);
        }

        if ($builder->hasExtension('elasticsearch')) {
            (new ElasticsearchPrepend())->prepend($container);
        }
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        (new ServiceRegistration())->register($container);
    }
}
