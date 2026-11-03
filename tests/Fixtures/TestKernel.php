<?php declare(strict_types=1);

namespace Shopware\K8sMeta\Tests\Fixtures;

use Shopware\K8sMeta\K8sMetaBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new ShopwareRecorderBundle();
        yield new ElasticsearchRecorderBundle();
        yield new K8sMetaBundle();
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/k8s-meta-tests/' . spl_object_id($this) . '/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/k8s-meta-tests/' . spl_object_id($this) . '/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->parameters()->set('env(APP_URL)', 'http://localhost');
        $container->extension('framework', ['secret' => 'test']);
        $container->extension('shopware', [
            'admin_worker' => ['enable_admin_worker' => true],
            'http_cache' => ['stale_if_error' => 'project'],
        ]);
    }
}
