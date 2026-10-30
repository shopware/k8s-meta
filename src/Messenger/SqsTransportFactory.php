<?php declare(strict_types=1);

namespace Shopware\K8sMeta\Messenger;

use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class SqsTransportFactory implements TransportFactoryInterface
{
    private const ENFORCED_OPTIONS = [
        // Short polling, see https://github.com/symfony/symfony/issues/50342
        'poll_timeout' => null,
        'wait_time' => 0,
        // Required for keepalive to work
        'visibility_timeout' => 30,
        // Queues are provisioned by the platform
        'auto_setup' => false,
    ];

    // Options of other transports, rejected by the SQS connection
    private const UNSUPPORTED_OPTIONS = ['consumer'];

    public function __construct(private readonly TransportFactoryInterface $innerFactory)
    {
    }

    public function createTransport(#[\SensitiveParameter] string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        $options = [...array_diff_key($options, array_flip(self::UNSUPPORTED_OPTIONS)), ...self::ENFORCED_OPTIONS];

        return $this->innerFactory->createTransport($dsn, $options, $serializer);
    }

    public function supports(#[\SensitiveParameter] string $dsn, array $options): bool
    {
        return $this->innerFactory->supports($dsn, $options);
    }
}
