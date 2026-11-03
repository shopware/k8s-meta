<?php declare(strict_types=1);

namespace Shopware\K8sMeta\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\K8sMeta\Configuration\OperatorConfiguration;

class OperatorConfigurationTest extends TestCase
{
    public function testV66Configuration(): void
    {
        $configuration = new OperatorConfiguration(true);
        $defaults = $configuration->defaults();
        $enforced = $configuration->enforced('dev');

        foreach (['api', 'auto_update', 'store', 'mail', 'cache'] as $key) {
            static::assertArrayHasKey($key, $defaults['shopware']);
        }
        static::assertArrayNotHasKey('redis', $enforced['shopware']);
        static::assertArrayHasKey('trusted_proxies', $defaults['framework']);
        static::assertArrayHasKey('env(TRUSTED_PROXIES)', $configuration->parameters());
    }

    public function testV67AndLaterConfiguration(): void
    {
        $configuration = new OperatorConfiguration(false);
        $defaults = $configuration->defaults();
        $enforced = $configuration->enforced('dev');

        foreach (['api', 'auto_update', 'store', 'mail', 'cache'] as $key) {
            static::assertArrayNotHasKey($key, $defaults['shopware']);
        }
        static::assertArrayHasKey('redis', $enforced['shopware']);
        static::assertArrayNotHasKey('trusted_proxies', $defaults['framework']);
        static::assertArrayNotHasKey('env(TRUSTED_PROXIES)', $configuration->parameters());
    }

    #[DataProvider('shopwareVersionProvider')]
    public function testMonologIsOnlyEnforcedInProd(bool $isV6_6): void
    {
        $configuration = new OperatorConfiguration($isV6_6);

        static::assertArrayNotHasKey('monolog', $configuration->enforced('dev'));
        static::assertArrayHasKey('monolog', $configuration->enforced('prod'));
    }

    #[DataProvider('shopwareVersionProvider')]
    public function testInfrastructureKeysAreEnforced(bool $isV6_6): void
    {
        $enforced = (new OperatorConfiguration($isV6_6))->enforced('prod');

        foreach (['filesystem', 'cdn', 'deployment', 'admin_worker', 'increment', 'usage_data'] as $key) {
            static::assertArrayHasKey($key, $enforced['shopware']);
        }
        static::assertArrayHasKey('reverse_proxy', $enforced['shopware']['http_cache']);
        static::assertArrayHasKey('index_settings', $enforced['elasticsearch']);
        static::assertArrayHasKey('handlers', $enforced['monolog']);
    }

    #[DataProvider('shopwareVersionProvider')]
    public function testLayersDoNotOverlap(bool $isV6_6): void
    {
        $configuration = new OperatorConfiguration($isV6_6);
        $enforced = $configuration->enforced('prod');
        $defaults = $configuration->defaults();

        foreach ($defaults as $alias => $config) {
            static::assertSame([], $this->sharedLeaves($config, $enforced[$alias]), $alias);
        }
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function shopwareVersionProvider(): iterable
    {
        yield '6.6' => [true];
        yield '6.7 and later' => [false];
    }

    /**
     * @param array<mixed> $left
     * @param array<mixed> $right
     *
     * @return list<string>
     */
    private function sharedLeaves(array $left, array $right, string $path = ''): array
    {
        $shared = [];
        foreach (array_intersect_key($left, $right) as $key => $value) {
            if (\is_array($value) && \is_array($right[$key])) {
                $shared = [...$shared, ...$this->sharedLeaves($value, $right[$key], $path . '.' . $key)];

                continue;
            }

            $shared[] = $path . '.' . $key;
        }

        return $shared;
    }
}
