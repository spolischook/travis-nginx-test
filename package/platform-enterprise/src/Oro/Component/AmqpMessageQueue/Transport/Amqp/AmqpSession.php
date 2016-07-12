<?php
namespace Oro\Component\AmqpMessageQueue\Transport\Amqp;

use Oro\Component\MessageQueue\Transport\DestinationInterface;
use Oro\Component\MessageQueue\Transport\Exception\InvalidDestinationException;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Wire\AMQPTable;

class AmqpSession implements SessionInterface
{
    /**
     * @var AbstractConnection
     */
    private $connection;

    /**
     * @var AMQPChannel
     */
    private $producerChannel;

    /**
     * Because of limitation of the AMQP library we are not able to reuse one channel with several message consumers.
     * So we have to internally create a new channel each time a new consumer is requested.
     *
     * @var AMQPChannel[]
     */
    private $consumersChannels = [];

    /**
     * @param AbstractConnection $connection
     */
    public function __construct(AbstractConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpMessage
     */
    public function createMessage($body = null, array $properties = [], array $headers = [])
    {
        $message = new AmqpMessage();
        $message->setBody($body);
        $message->setProperties($properties);
        $message->setHeaders($headers);

        return $message;
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpQueue
     */
    public function createQueue($name)
    {
        return new AmqpQueue($name);
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpTopic
     */
    public function createTopic($name)
    {
        return new AmqpTopic($name);
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpQueue $destination
     *
     * @return AmqpMessageConsumer
     */
    public function createConsumer(DestinationInterface $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, AmqpQueue::class);

        $this->consumersChannels[] = $consumerChannel = $this->connection->channel();

        return new AmqpMessageConsumer($this, $consumerChannel, $destination);
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpMessageProducer
     */
    public function createProducer()
    {
        return new AmqpMessageProducer($this->getProducerChannel());
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpTopic $destination
     */
    public function declareTopic(DestinationInterface $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, AmqpTopic::class);

        $this->getProducerChannel()->exchange_declare(
            $destination->getTopicName(),
            $destination->getType(),
            $destination->isPassive(),
            $destination->isDurable(),
            $autoDelete = false, // rabbitmq specific
            $internal = false, // rabbitmq specific
            $destination->isNoWait(),
            $destination->getTable() ? new AMQPTable($destination->getTable()) : null
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpQueue $destination
     */
    public function declareQueue(DestinationInterface $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, AmqpQueue::class);

        $this->getProducerChannel()->queue_declare(
            $destination->getQueueName(),
            $destination->isPassive(),
            $destination->isDurable(),
            $destination->isExclusive(),
            $destination->isAutoDelete(),
            $destination->isNoWait(),
            $destination->getTable() ? new AMQPTable($destination->getTable()) : null
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpTopic $source
     * @param AmqpQueue $target
     */
    public function declareBind(DestinationInterface $source, DestinationInterface $target)
    {
        InvalidDestinationException::assertDestinationInstanceOf($source, AmqpTopic::class);
        InvalidDestinationException::assertDestinationInstanceOf($target, AmqpQueue::class);

        $this->getProducerChannel()->queue_bind(
            $target->getQueueName(),
            $source->getTopicName(),
            $source->getRoutingKey()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if ($this->producerChannel) {
            $this->producerChannel->close();
        }

        foreach ($this->consumersChannels as $consumersChannel) {
            $consumersChannel->close();
        }
    }

    /**
     * @return AMQPChannel
     */
    protected function getProducerChannel()
    {
        if (false == $this->producerChannel) {
            $this->producerChannel = $this->connection->channel();
        }

        return $this->producerChannel;
    }
}
