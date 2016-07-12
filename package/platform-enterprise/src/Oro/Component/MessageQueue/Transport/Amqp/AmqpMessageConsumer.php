<?php
namespace Oro\Component\MessageQueue\Transport\Amqp;

use Oro\Component\MessageQueue\Transport\Exception\InvalidMessageException;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\QueueInterface;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage as AMQPLibMessage;
use PhpAmqpLib\Wire\AMQPTable;

class AmqpMessageConsumer implements MessageConsumerInterface
{
    /**
     * @var AmqpSession
     */
    private $session;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var QueueInterface
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
     * @param AMQPChannel $channel
     * @param AmqpQueue   $queue
     */
    public function __construct(AmqpSession $session, AMQPChannel $channel, AmqpQueue $queue)
    {
        $this->isInit = false;

        $this->session = $session;
        $this->channel = $channel;

        // The queue consumer tag could be set while initializing the consumer.
        // To prevent any other side effects we do a local copy of the queue.
        $this->queue = clone $queue;
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
            $this->channel->wait($allowedMethods = [], $nonBlocking = false, $timeout);

            return $this->receivedMessage;
        } catch (AMQPTimeoutException $e) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpMessage|null
     */
    public function receiveNoWait()
    {
        $internalMessage = $this->channel->basic_get($this->queue->getQueueName(), $noAck = false);

        return $internalMessage ? $this->convertMessage($internalMessage) : null;
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpMessage $message
     */
    public function acknowledge(MessageInterface $message)
    {
        InvalidMessageException::assertMessageInstanceOf($message, AmqpMessage::class);

        $this->channel->basic_ack($message->getDeliveryTag());
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpMessage $message
     */
    public function reject(MessageInterface $message, $requeue = false)
    {
        InvalidMessageException::assertMessageInstanceOf($message, AmqpMessage::class);

        $this->channel->basic_reject($message->getDeliveryTag(), $requeue);
    }

    protected function initialize()
    {
        if ($this->isInit) {
            return;
        }

        if (0 != count($this->channel->callbacks)) {
            throw new \LogicException(
                'The channel has a callback set. '.
                'We cannot use this channel because of unexpected behavior in such case.'
            );
        }

        $this->channel->basic_qos(0, 1, false);

        $callback = function (AMQPLibMessage $internalMessage) {
            $this->receivedMessage = $this->convertMessage($internalMessage);
        };

        $this->queue->setConsumerTag($this->channel->basic_consume(
            $this->queue->getQueueName(),
            $this->queue->getConsumerTag(),
            $this->queue->isNoLocal(),
            $this->queue->isNoAck(),
            $this->queue->isExclusive(),
            $this->queue->isNoWait(),
            $callback
        ));

        $this->isInit = true;
    }

    /**
     * @param AMQPLibMessage $internalMessage
     *
     * @return AmqpMessage
     */
    protected function convertMessage(AMQPLibMessage $internalMessage)
    {
        $properties = $internalMessage->has('application_headers')
            ? $internalMessage->get('application_headers')->getNativeData()
            : [];

        $headers = (new AMQPTable($internalMessage->get_properties()))->getNativeData();
        unset($headers['application_headers']);

        $message = $this->session->createMessage($internalMessage->body, $properties, $headers);
        $message->setDeliveryTag($internalMessage->delivery_info['delivery_tag']);
        $message->setRedelivered($internalMessage->delivery_info['redelivered']);
        $message->setExchange($internalMessage->delivery_info['exchange']);

        return $message;
    }
}
