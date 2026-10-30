<?php declare(strict_types=1);

namespace Shopware\K8sMeta\Tests;

use PHPUnit\Framework\TestCase;
use Shopware\K8sMeta\Tests\Fixtures\TestKernel;

class BundleIntegrationTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $shopwareConfig;

    protected function setUp(): void
    {
        $kernel = new TestKernel('dev', true);
        $kernel->boot();

        $this->shopwareConfig = array_replace_recursive(...$kernel->getContainer()->getParameter('recorded.shopware'));
        $kernel->shutdown();
    }

    public function testProjectCannotOverrideEnforcedConfiguration(): void
    {
        static::assertFalse($this->shopwareConfig['admin_worker']['enable_admin_worker'], 'core and the project both enable it');
    }

    public function testProjectOverridesBundleDefaults(): void
    {
        static::assertSame('project', $this->shopwareConfig['http_cache']['stale_if_error']);
    }

    public function testBundleDefaultsOverrideCore(): void
    {
        static::assertSame('300', $this->shopwareConfig['http_cache']['stale_while_revalidate'], 'the default of HTTP_CACHE_CONTROL_STALE_WHILE_REVALIDATE');
    }
}
