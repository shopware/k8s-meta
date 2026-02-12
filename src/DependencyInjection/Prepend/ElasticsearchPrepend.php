<?php

declare(strict_types=1);

namespace Shopware\K8sMeta\DependencyInjection\Prepend;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

final class ElasticsearchPrepend
{
    public function prepend(ContainerConfigurator $container): void
    {
        $parameters = $container->parameters();
        $parameters->set('env(K8S_ES_NUMBER_OF_REPLICAS)', null);
        $parameters->set('env(K8S_ES_NUMBER_OF_SHARDS)', null);

        $container->extension(
            'elasticsearch',
            [
                'index_settings' => [
                    'number_of_replicas' => '%env(int:K8S_ES_NUMBER_OF_REPLICAS)%',
                    'number_of_shards' => '%env(int:K8S_ES_NUMBER_OF_SHARDS)%',
                ],
            ],
            prepend: true,
        );
    }
}
