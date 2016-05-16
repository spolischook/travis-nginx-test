<?php
namespace Oro\Component\Messaging\Transport\Amqp;

use Oro\Component\Messaging\Transport\Exception\InvalidMessageException;
use Oro\Component\Messaging\Transport\Message;
use Oro\Component\Messaging\Transport\Queue;
use Oro\Component\Messaging\Transport\MessageConsumer;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage as AMQPLibMessage;

final class AmqpMessageConsumer implements MessageConsumer
{
    /**
     * @var AmqpSession
     */
    private $session;

    /**
     * @var Queue
     */
    private $queue;

    /**
     * @var bool
     */
    private $isInit;

    /**
     * @var AmqpMessage|null
     */
    private $receivedMessage;

    /**
     * @param AmqpSession $session
     * @param AmqpQueue $queue
     */
    public function __construct(AmqpSession $session, AmqpQueue $queue)
    {
        $this->isInit = false;

        $this->queue = $queue;
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpQueue
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpMessage|null
     */
    public function receive($timeout = 0)
    {
        $this->initialize();
        
        try {
            $this->receivedMessage = null;
            $this->session->getChannel()->wait($allowedMethods = [], $nonBlocking = false, $timeout);

            return $this->receivedMessage;
        } catch (AMQPTimeoutException $e) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpMessage $message
     */
    public function acknowledge(Message $message)
    {
        if (false == $message instanceof AmqpMessage) {
            throw new InvalidMessageException(sprintf(
                'A message is invalid. Message must be an instance of %s but it is %s.',
                'Oro\Component\Messaging\Transport\Amqp\AmqpMessage',
                get_class($message)
            ));
        }

        $this->session->getChannel()->basic_ack($message->getDeliveryTag());
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpMessage $message
     */
    public function reject(Message $message, $requeue = false)
    {
        if (false == $message instanceof AmqpMessage) {
            throw new InvalidMessageException(sprintf(
                'A message is invalid. Message must be an instance of %s but it is %s.',
                'Oro\Component\Messaging\Transport\Amqp\AmqpMessage',
                get_class($message)
            ));
        }

        $this->session->getChannel()->basic_reject($message->getDeliveryTag(), $requeue);
    }

    protected function initialize()
    {
        if ($this->isInit) {
            return;
        }

        $callback = function (AMQPLibMessage $internalMessage) {
            $properties = $internalMessage->has('application_headers') ?
                $internalMessage->get('application_headers')->getNativeData() :
                [];

            $message = $this->session->createMessage(
                $internalMessage->body,
                $properties,
                $internalMessage->get_properties()
            );
            $message->setDeliveryTag($internalMessage->delivery_info['delivery_tag']);
            $message->setRedelivered($internalMessage->delivery_info['redelivered']);

            $this->receivedMessage = $message;
        };

        $this->session->getChannel()->basic_consume(
            $this->queue->getQueueName(),
            $this->queue->getConsumerTag(),
            $this->queue->isNoLocal(),
            $this->queue->isNoAck(),
            $this->queue->isExclusive(),
            $this->queue->isNoWait(),
            $callback
        );

        $this->isInit = true;
    }
}
