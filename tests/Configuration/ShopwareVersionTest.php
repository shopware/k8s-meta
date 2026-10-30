<?php declare(strict_types=1);

namespace Shopware\K8sMeta\Tests\Configuration;

use Composer\InstalledVersions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\K8sMeta\Configuration\ShopwareVersion;

class ShopwareVersionTest extends TestCase
{
    /**
     * @var array<mixed>
     */
    private array $originalData;

    protected function setUp(): void
    {
        $this->originalData = InstalledVersions::getAllRawData()[0];
    }

    protected function tearDown(): void
    {
        InstalledVersions::reload($this->originalData);
    }

    /**
     * @param list<string> $aliases
     */
    #[DataProvider('versions')]
    public function testIsV6_6(string $version, array $aliases, bool $expected): void
    {
        $this->install(['shopware/core' => [
            'pretty_version' => $version,
            'version' => str_starts_with($version, 'dev-') ? $version : $version . '.0',
            'aliases' => $aliases,
        ]]);

        static::assertSame($expected, ShopwareVersion::isV6_6());
    }

    /**
     * @return iterable<string, array{string, list<string>, bool}>
     */
    public static function versions(): iterable
    {
        yield '6.6' => ['6.6.10', [], true];
        yield '6.7' => ['6.7.15', [], false];
        yield '6.8' => ['6.8.0', [], false];
        yield '6.5' => ['6.5.8', [], false];
        yield '6.7 release candidate' => ['6.7.0-RC1', [], false];
        yield 'trunk without alias' => ['dev-trunk', [], false];
        yield 'trunk aliased to 6.7' => ['dev-trunk', ['6.7.9999999.9999999-dev'], false];
        yield 'branch aliased to 6.6' => ['dev-6.6.x', ['6.6.9999999.9999999-dev'], true];
    }

    public function testReplacedCore(): void
    {
        $this->install(['shopware/platform' => [
            'pretty_version' => '6.6.10',
            'version' => '6.6.10.0',
            'replaced' => ['6.6.10.0'],
        ], 'shopware/core' => [
            'pretty_version' => '6.6.10',
            'version' => '6.6.10.0',
            'replaced' => ['6.6.10.0'],
        ]]);

        static::assertTrue(ShopwareVersion::isV6_6());
    }

    public function testCoreNotInstalled(): void
    {
        $this->install([]);

        static::assertFalse(ShopwareVersion::isV6_6());
    }

    /**
     * @param array<string, array<string, mixed>> $packages
     */
    private function install(array $packages): void
    {
        $versions = [];
        foreach ($packages as $name => $package) {
            $versions[$name] = $package + ['reference' => null, 'type' => 'library', 'install_path' => '', 'aliases' => [], 'dev_requirement' => false];
        }

        InstalledVersions::reload([
            'root' => ['name' => 'test/root', 'pretty_version' => '1.0.0', 'version' => '1.0.0.0', 'reference' => null, 'type' => 'project', 'install_path' => '', 'aliases' => [], 'dev' => true],
            'versions' => $versions,
        ]);
    }
}
