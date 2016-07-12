<?php
namespace Oro\Component\AmqpMessageQueue\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\ExtensionTrait;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpMessage;
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpSession;

/**
 * If the message could not be processed because of an exception or fatal error.
 * RabbitMQ tries to send this same message again and again.
 * All the other messages behaind this broken message are not processed.
 * The purpose of this extension to push the broken message to the end of the queue allowing to process others.
 * Also it adds a delay so we do not fail often.
 */
class DelayRedeliveredMessageAmqpExtension implements ExtensionInterface
{
    use ExtensionTrait;

    /**
     * @param Context $context
     */
    public function onPreReceived(Context $context)
    {
        /** @var AmqpSession $session */
        $session = $context->getSession();
        if (false == $session instanceof  AmqpSession) {
            return;
        }

        /** @var AmqpMessage $message */
        $message = $context->getMessage();
        if (false == $message->isRedelivered()) {
            return;
        }

        $queueName = $context->getQueueName();

        $deadQueue = $session->createQueue($queueName.'.delayed');
        $deadQueue->setDurable(true);
        $deadQueue->setTable([
            'x-dead-letter-exchange' => '',
            'x-dead-letter-routing-key' => $queueName,
            'x-message-ttl' => 5000,
            'x-expires' => 200000,
        ]);
        $session->declareQueue($deadQueue);
        $context->getLogger()->debug(sprintf(
            '[DelayRedeliveredMessageAmqpExtension] Declare dead queue: %s',
            $deadQueue->getQueueName()
        ));

        $properties = $message->getProperties();

        // The x-death header must be removed because of the bug in RabbitMQ.
        // It was reported that the bug is fixed since 3.5.4 but I tried with 3.6.1 and the bug still there.
        // https://github.com/rabbitmq/rabbitmq-server/issues/216
        unset($properties['x-death']);

        $deadMessage = $session->createMessage($message->getBody(), $properties, $message->getHeaders());
        
        $session->createProducer()->send($deadQueue, $deadMessage);
        $context->getLogger()->debug('[DelayRedeliveredMessageAmqpExtension] Send message to dead queue');

        $context->setStatus(MessageProcessorInterface::REJECT);
        $context->getLogger()->debug('[DelayRedeliveredMessageAmqpExtension] Reject original message');
    }
}
