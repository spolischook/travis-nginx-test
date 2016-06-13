<?php
namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Transport\Amqp\AmqpMessage;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpSession;
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
     * @param AmqpSession $session
     * @param Config               $config
     */
    public function __construct(AmqpSession $session, Config $config)
    {
        $this->session = $session;
        $this->config = $config;
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
        $map = [
            MessagePriority::VERY_LOW => 0,
            MessagePriority::LOW => 1,
            MessagePriority::NORMAL => 2,
            MessagePriority::HIGH => 3,
            MessagePriority::VERY_HIGH => 4,
        ];

        if (false == array_key_exists($priority, $map)) {
            throw new \InvalidArgumentException(sprintf(
                'Given priority could not be converted to transport\'s one. Got: %s',
                $priority
            ));
        }

        $headers = $message->getHeaders();
        $headers['priority'] = $map[$priority];
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
        $queue->setTable(['x-max-priority' => 5]);
        $this->session->declareQueue($queue);

        return $queue;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }
}
