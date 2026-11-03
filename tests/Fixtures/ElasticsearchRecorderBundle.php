<?php declare(strict_types=1);

namespace Shopware\K8sMeta\Tests\Fixtures;

class ElasticsearchRecorderBundle extends ConfigRecorderBundle
{
    protected function alias(): string
    {
        return 'elasticsearch';
    }
}
