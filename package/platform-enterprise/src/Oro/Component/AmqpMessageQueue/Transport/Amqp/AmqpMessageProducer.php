<?php
namespace Oro\Component\AmqpMessageQueue\Transport\Amqp;

use Oro\Component\MessageQueue\Transport\DestinationInterface;
use Oro\Component\MessageQueue\Transport\Exception\Exception;
use Oro\Component\MessageQueue\Transport\Exception\InvalidDestinationException;
use Oro\Component\MessageQueue\Transport\Exception\InvalidMessageException;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\MessageProducerInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage as AMQPLibMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * @link https://www.rabbitmq.com/amqp-0-9-1-reference.html
 */
class AmqpMessageProducer implements MessageProducerInterface
{
    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @param AMQPChannel $channel
     */
    public function __construct(AMQPChannel $channel)
    {
        $this->channel = $channel;
    }

    /**
     * {@inheritdoc}
     */
    public function send(DestinationInterface $destination, MessageInterface $message)
    {
        $body = $message->getBody();
        if (is_scalar($body) || is_null($body)) {
            $body = (string)$body;
        } else {
            throw new InvalidMessageException(sprintf(
                'The message body must be a scalar or null. Got: %s',
                is_object($body) ? get_class($body) : gettype($body)
            ));
        }

        $amqpMessage = new AMQPLibMessage($body, $message->getHeaders());
        $amqpMessage->set('application_headers', new AMQPTable($message->getProperties()));

        if ($destination instanceof AmqpTopic) {
            try {
                $this->channel->basic_publish(
                    $amqpMessage,
                    $destination->getTopicName(),
                    $destination->getRoutingKey(),
                    $destination->isMandatory(),
                    $destination->isImmediate()
                );
            } catch (\Exception $e) {
                throw new Exception('The transport fails to send the message due to some internal error.', null, $e);
            }
        } elseif ($destination instanceof AmqpQueue) {
            try {
                $this->channel->basic_publish($amqpMessage, '', $destination->getQueueName());
            } catch (\Exception $e) {
                throw new Exception('The transport fails to send the message due to some internal error.', null, $e);
            }
        } else {
            InvalidDestinationException::assertDestinationInstanceOf(
                $destination,
                AmqpTopic::class.' or '.AmqpQueue::class
            );
        }
    }
}
