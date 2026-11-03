<?php declare(strict_types=1);

namespace Shopware\K8sMeta\DependencyInjection\Compiler;

use Shopware\K8sMeta\Messenger\SqsTransportFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Messenger\Bridge\AmazonSqs\Transport\AmazonSqsTransportFactory;

class SqsTransportFactoryPass implements CompilerPassInterface
{
    private const INNER_FACTORY_ID = 'messenger.transport.sqs.factory';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::INNER_FACTORY_ID)) {
            return;
        }

        if (!class_exists(AmazonSqsTransportFactory::class)) {
            throw new \LogicException('The package symfony/amazon-sqs-messenger is required.');
        }

        $factory = new Definition(SqsTransportFactory::class, [new Reference(SqsTransportFactory::class . '.inner')]);
        $factory->setDecoratedService(self::INNER_FACTORY_ID);
        $factory->addTag('messenger.transport_factory');

        $container->setDefinition(SqsTransportFactory::class, $factory);
    }
}
