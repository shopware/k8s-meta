<?php declare(strict_types=1);

namespace Shopware\K8sMeta\Configuration;

use Symfony\Component\HttpFoundation\Session\Storage\Handler\RedisSessionHandler;

final class OperatorConfiguration
{
    public function __construct(private readonly bool $isV6_6)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function parameters(): array
    {
        return [
            'env(K8S_FILESYSTEM_PRIVATE_BUCKET)' => '',
            'env(K8S_FILESYSTEM_PUBLIC_BUCKET)' => '',
            'env(K8S_FILESYSTEM_PUBLIC_URL)' => '',
            'env(K8S_FILESYSTEM_REGION)' => '',
            'env(K8S_FILESYSTEM_ENDPOINT)' => '',
            'env(K8S_CACHE_HOST)' => 'localhost',
            'env(K8S_CACHE_PORT)' => '6379',
            'env(K8S_CACHE_INDEX)' => '',
            'env(K8S_ES_NUMBER_OF_REPLICAS)' => null,
            'env(K8S_ES_NUMBER_OF_SHARDS)' => null,
            'env(K8S_ES_ADMIN_NUMBER_OF_SHARDS)' => '1',
            'env(K8S_ES_ADMIN_NUMBER_OF_REPLICAS)' => '1',
            'env(PHP_SESSION_SAVE_PATH)' => 'tcp://127.0.0.1:6379',
            'env(K8S_REDIS_SESSION_DSN)' => 'redis://%env(string:key:host:url:PHP_SESSION_SAVE_PATH)%:%env(string:key:port:url:PHP_SESSION_SAVE_PATH)%/%env(string:key:path:url:PHP_SESSION_SAVE_PATH)%',
            'env(K8S_REDIS_APP_DSN)' => 'redis://%env(K8S_CACHE_HOST)%:%env(K8S_CACHE_PORT)%/%env(K8S_CACHE_INDEX)%',
            'env(MESSENGER_TRANSPORT_DSN)' => 'redis://127.0.0.1:6379/messages/symfony/?auto_setup=true&serializer=1&stream_max_entries=0&dbindex=0',
            'env(MESSENGER_CONSUMER_NAME)' => 'shopware-worker',
            'env(FASTLY_API_TOKEN)' => 'myToken',
            'env(FASTLY_SERVICE_ID)' => 'myServiceID',
            'env(HTTP_CACHE_CONTROL_STALE_WHILE_REVALIDATE)' => '300',
            'env(HTTP_CACHE_CONTROL_STALE_IF_ERROR)' => '3600',
            'env(FASTLY_REVERSE_PROXY_ENABLED)' => 'true',
            'env(SOFT_PURGE_ENABLED)' => '1',
            'env(USE_VARNISH_XKEY)' => 'true',
            'env(VARNISH_HOST)' => 'varnish-host',
            'env(MONOLOG_LOG_LEVEL)' => 'error',
            ...($this->isV6_6 ? ['env(TRUSTED_PROXIES)' => ''] : []),
        ];
    }

    /**
     * Applied after the project configuration, so the project cannot override it.
     *
     * @return array<string, array<string, mixed>>
     */
    public function enforced(string $environment): array
    {
        $extensions = [
            'shopware' => $this->enforcedShopware(),
            'elasticsearch' => $this->elasticsearch(),
            'framework' => $this->enforcedFramework(),
        ];

        if ($environment === 'prod') {
            $extensions['monolog'] = $this->monolog();
        }

        return $extensions;
    }

    /**
     * Applied after the configuration of the other bundles and before the project configuration, so the project can override it.
     *
     * @return array<string, array<string, mixed>>
     */
    public function defaults(): array
    {
        return [
            'shopware' => $this->defaultShopware(),
            'framework' => $this->defaultFramework(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function enforcedShopware(): array
    {
        $publicBucket = [
            'type' => 'amazon-s3',
            'url' => '%env(K8S_FILESYSTEM_PUBLIC_URL)%',
            'visibility' => 'public',
            'config' => [
                'bucket' => '%env(K8S_FILESYSTEM_PUBLIC_BUCKET)%',
                'region' => '%env(K8S_FILESYSTEM_REGION)%',
                'endpoint' => '%env(K8S_FILESYSTEM_ENDPOINT)%',
                'use_path_style_endpoint' => true,
            ],
        ];

        $config = [
            'http_cache' => [
                'reverse_proxy' => [
                    'enabled' => true,
                    'use_varnish_xkey' => '%env(bool:USE_VARNISH_XKEY)%',
                    'hosts' => ['%env(VARNISH_HOST)%'],
                    // When Fastly reverse proxy is enabled the varnish configuration is ignored
                    'fastly' => [
                        'enabled' => '%env(bool:FASTLY_REVERSE_PROXY_ENABLED)%',
                        'api_key' => '%env(FASTLY_API_TOKEN)%',
                        'service_id' => '%env(FASTLY_SERVICE_ID)%',
                        'soft_purge' => '%env(SOFT_PURGE_ENABLED)%',
                    ],
                ],
            ],
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
                'public' => $publicBucket,
                'theme' => $publicBucket,
                'sitemap' => $publicBucket,
                'asset' => [
                    'type' => 'local',
                    'url' => '%env(APP_URL)%',
                    'config' => ['root' => '%kernel.project_dir%/public'],
                ],
            ],
            'cdn' => ['url' => '%env(K8S_FILESYSTEM_PUBLIC_URL)%'],
            'usage_data' => [
                'gateway' => ['dispatch_enabled' => false],
            ],
        ];

        if (!$this->isV6_6) {
            $config['usage_data']['collection_enabled'] = false;
            $config['redis'] = [
                'connections' => [
                    'session' => ['dsn' => '%env(K8S_REDIS_SESSION_DSN)%'],
                ],
            ];
        }

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultShopware(): array
    {
        $config = [
            'http_cache' => [
                'stale_while_revalidate' => '%env(HTTP_CACHE_CONTROL_STALE_WHILE_REVALIDATE)%',
                'stale_if_error' => '%env(HTTP_CACHE_CONTROL_STALE_IF_ERROR)%',
            ],
        ];

        if ($this->isV6_6) {
            $config += [
                'api' => ['jwt_key' => ['use_app_secret' => true]],
                'auto_update' => ['enabled' => false],
                'store' => ['frw' => false],
                'mail' => ['update_mail_variables_on_send' => false],
                'cache' => [
                    'tagging' => [
                        'each_snippet' => false,
                        'each_config' => false,
                        'each_theme_config' => false,
                    ],
                ],
            ];
        }

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    private function elasticsearch(): array
    {
        return [
            'index_settings' => [
                'number_of_replicas' => '%env(int:K8S_ES_NUMBER_OF_REPLICAS)%',
                'number_of_shards' => '%env(int:K8S_ES_NUMBER_OF_SHARDS)%',
            ],
            'administration' => [
                'index_settings' => [
                    'number_of_shards' => '%env(int:K8S_ES_ADMIN_NUMBER_OF_SHARDS)%',
                    'number_of_replicas' => '%env(int:K8S_ES_ADMIN_NUMBER_OF_REPLICAS)%',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function enforcedFramework(): array
    {
        return [
            'session' => ['handler_id' => RedisSessionHandler::class],
            'cache' => [
                'app' => 'cache.adapter.redis_tag_aware',
                'default_redis_provider' => '%env(K8S_REDIS_APP_DSN)%',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultFramework(): array
    {
        $config = [
            'messenger' => [
                'transports' => [
                    'async' => [
                        'dsn' => '%env(MESSENGER_TRANSPORT_DSN)%',
                        'options' => ['consumer' => '%env(MESSENGER_CONSUMER_NAME)%'],
                    ],
                ],
            ],
            'mailer' => ['message_bus' => 'messenger.default_bus'],
        ];

        if ($this->isV6_6) {
            $config['trusted_proxies'] = '%env(TRUSTED_PROXIES)%';
            $config['trusted_headers'] = ['x-forwarded-for', 'x-forwarded-host', 'x-forwarded-proto', 'x-forwarded-port'];
        }

        return $config;
    }

    /**
     * @return array<string, mixed>
     */
    private function monolog(): array
    {
        return [
            'handlers' => [
                'main' => [
                    'type' => 'fingers_crossed',
                    'action_level' => '%env(MONOLOG_LOG_LEVEL)%',
                    'handler' => 'nested',
                    'excluded_http_codes' => [404, 405],
                    'buffer_size' => 50,
                ],
                'nested' => [
                    'type' => 'stream',
                    'path' => 'php://stderr',
                    'level' => '%env(MONOLOG_LOG_LEVEL)%',
                    'formatter' => 'monolog.formatter.json',
                ],
                'console' => [
                    'type' => 'console',
                    'process_psr_3_messages' => false,
                    'channels' => ['!event', '!doctrine'],
                ],
                'business_event_handler_buffer' => [
                    'level' => '%env(MONOLOG_LOG_LEVEL)%',
                ],
            ],
        ];
    }
}
