<?php

declare(strict_types=1);

namespace Shopware\K8sMeta\DependencyInjection;

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

final class ServiceRegistration
{
    private const REDIS_SESSION_HANDLER_ID = 'shopware.session.redis_handler';

    public function register(ContainerConfigurator $container): void
    {
        $container
            ->services()
            ->set(self::REDIS_SESSION_HANDLER_ID, RedisSessionHandler::class)
            ->args([
                service('shopware.redis.connection.session'),
                ['prefix' => 'sess_'],
            ]);
    }
}
