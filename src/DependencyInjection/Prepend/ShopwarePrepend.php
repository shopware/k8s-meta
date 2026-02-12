<?php

declare(strict_types=1);

namespace Shopware\K8sMeta\DependencyInjection\Prepend;

use Shopware\K8sMeta\VersionChecker;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

final class ShopwarePrepend
{
    public function prepend(ContainerConfigurator $container): void
    {
        $parameters = $container->parameters();
        $parameters->set('env(K8S_FILESYSTEM_PRIVATE_BUCKET)', '');
        $parameters->set('env(K8S_FILESYSTEM_PUBLIC_BUCKET)', '');
        $parameters->set('env(K8S_FILESYSTEM_PUBLIC_URL)', '');
        $parameters->set('env(K8S_FILESYSTEM_REGION)', '');
        $parameters->set('env(K8S_FILESYSTEM_ENDPOINT)', '');
        $parameters->set('env(K8S_CACHE_HOST)', 'localhost');
        $parameters->set('env(K8S_CACHE_PORT)', '6379');
        $parameters->set('env(K8S_CACHE_INDEX)', '');
        $parameters->set('env(PHP_SESSION_SAVE_PATH)', 'tcp://127.0.0.1:6379');
        $parameters->set(
            'env(K8S_REDIS_SESSION_DSN)',
            'redis://%env(string:key:host:url:PHP_SESSION_SAVE_PATH)%:%env(string:key:port:url:PHP_SESSION_SAVE_PATH)%/%env(string:key:path:url:PHP_SESSION_SAVE_PATH)%',
        );
        $parameters->set(
            'env(K8S_REDIS_APP_DSN)',
            'redis://%env(K8S_CACHE_HOST)%:%env(K8S_CACHE_PORT)%/%env(K8S_CACHE_INDEX)%',
        );

        $shopwareConfig = [
            'admin_worker' => [
                'enable_admin_worker' => false,
                'enable_notification_worker' => false,
                'enable_queue_stats_worker' => false,
            ],
            'deployment' => [
                'cluster_setup' => true,
                'runtime_extension_management' => false,
            ],
            'increment' => [
                'user_activity' => ['type' => 'array'],
                'message_queue' => ['type' => 'array'],
            ],
            'filesystem' => [
                'private' => [
                    'type' => 'amazon-s3',
                    'visibility' => 'private',
                    'config' => [
                        'bucket' => '%env(K8S_FILESYSTEM_PRIVATE_BUCKET)%',
                        'region' => '%env(K8S_FILESYSTEM_REGION)%',
                        'endpoint' => '%env(K8S_FILESYSTEM_ENDPOINT)%',
                        'use_path_style_endpoint' => true,
                    ],
                ],
                'public' => [
                    'type' => 'amazon-s3',
                    'url' => '%env(K8S_FILESYSTEM_PUBLIC_URL)%',
                    'visibility' => 'public',
                    'config' => [
                        'bucket' => '%env(K8S_FILESYSTEM_PUBLIC_BUCKET)%',
                        'region' => '%env(K8S_FILESYSTEM_REGION)%',
                        'endpoint' => '%env(K8S_FILESYSTEM_ENDPOINT)%',
                        'use_path_style_endpoint' => true,
                    ],
                ],
                'theme' => [
                    'type' => 'amazon-s3',
                    'url' => '%env(K8S_FILESYSTEM_PUBLIC_URL)%',
                    'visibility' => 'public',
                    'config' => [
                        'bucket' => '%env(K8S_FILESYSTEM_PUBLIC_BUCKET)%',
                        'region' => '%env(K8S_FILESYSTEM_REGION)%',
                        'endpoint' => '%env(K8S_FILESYSTEM_ENDPOINT)%',
                        'use_path_style_endpoint' => true,
                    ],
                ],
                'sitemap' => [
                    'type' => 'amazon-s3',
                    'url' => '%env(K8S_FILESYSTEM_PUBLIC_URL)%',
                    'visibility' => 'public',
                    'config' => [
                        'bucket' => '%env(K8S_FILESYSTEM_PUBLIC_BUCKET)%',
                        'region' => '%env(K8S_FILESYSTEM_REGION)%',
                        'endpoint' => '%env(K8S_FILESYSTEM_ENDPOINT)%',
                        'use_path_style_endpoint' => true,
                    ],
                ],
                'asset' => [
                    'type' => 'local',
                    'url' => '%env(APP_URL)%',
                    'config' => [
                        'root' => '%kernel.project_dir%/public',
                    ],
                ],
            ],
            'cdn' => [
                'url' => '%env(K8S_FILESYSTEM_PUBLIC_URL)%',
            ],
            'redis' => [
                'connections' => [
                    'session' => [
                        'dsn' => '%env(K8S_REDIS_SESSION_DSN)%',
                    ],
                ],
            ],
        ];

        if (VersionChecker::isPackageVersionLessThan('shopware/core', '6.7.0.0')) {
            $shopwareConfig['api'] = [
                'jwt_key' => [
                    'use_app_secret' => true,
                ],
            ];
            $shopwareConfig['cache'] = [
                'tagging' => [
                    'each_snippet' => false,
                    'each_config' => false,
                    'each_theme_config' => false,
                ],
            ];
        }

        $container->extension('shopware', $shopwareConfig, prepend: true);
    }
}
