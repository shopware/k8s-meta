# k8s-meta

Symfony bundle that configures a Shopware shop for the [Shopware Operator](https://github.com/shopware/shopware-operator).

It supports Shopware 6.6 and 6.7. The configuration that matches the installed Shopware version is applied automatically.

## What it does

The bundle prepends default configuration, so a shop needs no operator-specific config files in its project:

- S3 filesystems for private and public files, the CDN URL and the Elasticsearch/OpenSearch index settings
- Redis for the app cache and sessions
- HTTP cache with Varnish or Fastly reverse proxy
- Messenger `async` transport, configured through `MESSENGER_TRANSPORT_DSN` (Redis by default, set it to an SQS DSN to switch)
- JSON logging to stderr in `prod`
- Disabled admin worker, runtime extension management and usage data dispatch (and collection on Shopware 6.7)

All defaults are driven by environment variables, which the operator sets (for example `K8S_FILESYSTEM_*`, `K8S_CACHE_*` and `MESSENGER_TRANSPORT_DSN`). The bundle's configuration is applied after the project's own `config/packages/`, so a project cannot override it. Only the environment variables change the behavior. A project can still define its own defaults for these environment variables.

### SQS transport

When `MESSENGER_TRANSPORT_DSN` points to SQS, the bundle decorates the SQS transport factory and enforces these options. They cannot be changed through the transport options. The DSN is expected to come from the platform and not to contain these parameters:

| Option | Value |
|:---|:---|
| `wait_time` | `0` (short polling, see [symfony/symfony#50342](https://github.com/symfony/symfony/issues/50342)) |
| `poll_timeout` | `null` |
| `visibility_timeout` | `30` (required for keepalive) |
| `auto_setup` | `false` (the platform provisions the queues) |

The Redis-only `consumer` option is removed, so the same `async` transport definition works with both Redis and SQS.

## Installation

```bash
composer require shopware/k8s-meta
```

Symfony Flex registers `Shopware\K8sMeta\K8sMetaBundle` in `config/bundles.php` through the [recipe](https://github.com/shopware/recipes/tree/main/shopware/k8s-meta).

### Upgrading from 1.x and 2.x

Earlier versions copied `config/packages/operator.yaml`, `config/packages/prod/monolog.yaml` and, in 1.x, `config/packages/trusted_env.yaml` into the project. From 3.0 the bundle provides this configuration. After updating:

1. Make sure `Shopware\K8sMeta\K8sMetaBundle::class => ['all' => true]` is in `config/bundles.php`.
2. Delete the copied files. They override the bundle defaults and are no longer updated.

## Development

```bash
composer install
vendor/bin/phpunit
```

## License

MIT
