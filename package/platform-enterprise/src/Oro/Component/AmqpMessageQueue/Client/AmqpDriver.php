<?php
namespace Oro\Component\AmqpMessageQueue\Client;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducer;
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpMessage;
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpSession;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\QueueInterface;

class AmqpDriver implements DriverInterface
{
    /**
     * @var AmqpSession
     */
    protected $session;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var array
     */
    protected $priorityMap;

    /**
     * @param AmqpSession $session
     * @param Config               $config
     */
    public function __construct(AmqpSession $session, Config $config)
    {
        $this->session = $session;
        $this->config = $config;

        $this->priorityMap = [
            MessagePriority::VERY_LOW => 0,
            MessagePriority::LOW => 1,
            MessagePriority::NORMAL => 2,
            MessagePriority::HIGH => 3,
            MessagePriority::VERY_HIGH => 4,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function createMessage()
    {
        return $this->session->createMessage(null, [], [
            'delivery_mode' => AmqpMessage::DELIVERY_MODE_PERSISTENT,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function setMessagePriority(MessageInterface $message, $priority)
    {
        if (false == array_key_exists($priority, $this->priorityMap)) {
            throw new \InvalidArgumentException(sprintf(
                'Given priority could not be converted to transport\'s one. Got: %s',
                $priority
            ));
        }

        $headers = $message->getHeaders();
        $headers['priority'] = $this->priorityMap[$priority];
        $message->setHeaders($headers);
    }

    /**
     * {@inheritdoc}
     */
    public function createProducer()
    {
        return new MessageProducer($this->session->createProducer(), $this);
    }
    
    /**
     * @param string $queueName
     *
     * @return QueueInterface
     */
    public function createQueue($queueName)
    {
        $queue = $this->session->createQueue($queueName);
        $queue->setDurable(true);
        $queue->setAutoDelete(false);
        $queue->setTable(['x-max-priority' => 4]);
        $this->session->declareQueue($queue);

        return $queue;
    }

    /**
     * {@inheritdoc}
     */
    public function delayMessage(QueueInterface $queue, MessageInterface $message, $delaySec)
    {
        $queueName = $queue->getQueueName();

        $delayQueue = $this->session->createQueue($queueName.'.delayed');
        $delayQueue->setDurable(true);
        $delayQueue->setTable([
            'x-dead-letter-exchange' => '',
            'x-dead-letter-routing-key' => $queueName,
        ]);
        $this->session->declareQueue($delayQueue);

        $properties = $message->getProperties();

        // The x-death header must be removed because of the bug in RabbitMQ.
        // It was reported that the bug is fixed since 3.5.4 but I tried with 3.6.1 and the bug still there.
        // https://github.com/rabbitmq/rabbitmq-server/issues/216
        unset($properties['x-death']);

        $headers = $message->getHeaders();
        $headers['expiration'] = (string) ($delaySec * 1000);

        $delayMessage = $this->session->createMessage($message->getBody(), $properties, $headers);

        $this->session->createProducer()->send($delayQueue, $delayMessage);
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }
}
