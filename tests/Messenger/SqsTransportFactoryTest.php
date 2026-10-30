<?php declare(strict_types=1);

namespace Shopware\K8sMeta\Tests\Messenger;

use PHPUnit\Framework\TestCase;
use Shopware\K8sMeta\Messenger\SqsTransportFactory;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class SqsTransportFactoryTest extends TestCase
{
    public function testEnforcedOptionsCannotBeOverriddenByTransportOptions(): void
    {
        $inner = new RecordingFactory();
        $options = ['region' => 'eu-central-1', 'wait_time' => 20, 'visibility_timeout' => 5, 'auto_setup' => true, 'poll_timeout' => 3.0, 'consumer' => 'worker'];

        (new SqsTransportFactory($inner))->createTransport('sqs://h/q', $options, $this->createStub(SerializerInterface::class));

        $expected = ['poll_timeout' => null, 'wait_time' => 0, 'visibility_timeout' => 30, 'auto_setup' => false, 'region' => 'eu-central-1'];
        ksort($expected);
        ksort($inner->options);

        static::assertSame($expected, $inner->options);
        static::assertSame('sqs://h/q', $inner->dsn);
    }

    public function testSupportsDelegatesToInnerFactory(): void
    {
        $factory = new SqsTransportFactory(new RecordingFactory());

        static::assertTrue($factory->supports('sqs://h/q', []));
    }
}

class RecordingFactory implements TransportFactoryInterface
{
    public string $dsn = '';

    /**
     * @var array<string, mixed>
     */
    public array $options = [];

    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        $this->dsn = $dsn;
        $this->options = $options;

        return new InMemoryTransport();
    }

    public function supports(string $dsn, array $options): bool
    {
        return true;
    }
}
