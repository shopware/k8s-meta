<?php

declare(strict_types=1);

namespace Shopware\K8sMeta\DependencyInjection\Prepend;

use Shopware\K8sMeta\VersionChecker;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

final class FrameworkPrepend
{
    public function prepend(ContainerConfigurator $container): void
    {
        $parameters = $container->parameters();
        $parameters->set('env(K8S_CACHE_HOST)', 'localhost');
        $parameters->set('env(K8S_CACHE_PORT)', '6379');
        $parameters->set('env(K8S_CACHE_INDEX)', '');
        $parameters->set(
            'env(K8S_REDIS_APP_DSN)',
            'redis://%env(K8S_CACHE_HOST)%:%env(K8S_CACHE_PORT)%/%env(K8S_CACHE_INDEX)%',
        );

        $frameworkConfig = [
            'cache' => [
                'app' => 'cache.adapter.redis_tag_aware',
                'default_redis_provider' => '%env(K8S_REDIS_APP_DSN)%',
            ],
            'mailer' => [
                'message_bus' => 'messenger.default_bus',
            ],
        ];

        if (VersionChecker::isPackageVersionLessThan('shopware/core', '6.7.0.0')) {
            $parameters->set('env(TRUSTED_PROXIES)', '');
            $frameworkConfig['trusted_proxies'] = '%env(TRUSTED_PROXIES)%';
            $frameworkConfig['trusted_headers'] = [
                'x-forwarded-for',
                'x-forwarded-host',
                'x-forwarded-proto',
                'x-forwarded-port',
            ];
        }

        $container->extension('framework', $frameworkConfig, prepend: true);
    }
}
