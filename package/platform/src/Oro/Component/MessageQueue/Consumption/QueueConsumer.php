<?php
namespace Oro\Component\MessageQueue\Consumption;

use Oro\Component\MessageQueue\Transport\Connection;
use Psr\Log\NullLogger;

class QueueConsumer
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Extensions
     */
    private $extensions;

    /**
     * @param Connection $connection
     * @param Extensions $extensions
     */
    public function __construct(Connection $connection, Extensions $extensions)
    {
        $this->connection = $connection;
        $this->extensions = $extensions;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param string $queueName
     * @param MessageProcessor $messageProcessor
     * @param Extensions $extensions
     *
     * @throws \Exception
     */
    public function consume($queueName, MessageProcessor $messageProcessor, Extensions $extensions = null)
    {
        $session = $this->connection->createSession();
        $queue = $session->createQueue($queueName);
        $messageConsumer = $session->createConsumer($queue);

        if ($extensions) {
            $extensions = new Extensions([$this->extensions, $extensions]);
        } else {
            $extensions = $this->extensions;
        }

        $context = new Context($session, $messageConsumer, $messageProcessor, new NullLogger());
        $extensions->onStart($context);
        $logger = $context->getLogger();

        while (true) {
            $context = new Context($session, $messageConsumer, $messageProcessor, $logger);

            try {
                $extensions->onBeforeReceive($context);

                if ($message = $messageConsumer->receive($timeout = 1)) {
                    $context->setMessage($message);

                    $extensions->onPreReceived($context);
                    if (false == $context->getStatus()) {
                        $status = $messageProcessor->process($message, $session);
                        $status = $status ?: MessageProcessor::ACK;
                        $context->setStatus($status);
                    }

                    if (MessageProcessor::ACK === $context->getStatus()) {
                        $messageConsumer->acknowledge($message);
                    } elseif (MessageProcessor::REJECT === $context->getStatus()) {
                        $messageConsumer->reject($message, false);
                    } elseif (MessageProcessor::REQUEUE === $context->getStatus()) {
                        $messageConsumer->reject($message, true);
                    } else {
                        throw new \LogicException(sprintf('Status is not supported: %s', $context->getStatus()));
                    }

                    $extensions->onPostReceived($context);
                } else {
                    $extensions->onIdle($context);
                }

                if ($context->isExecutionInterrupted()) {
                    $extensions->onInterrupted($context);
                    $session->close();

                    return;
                }
            } catch (\Exception $e) {
                $context->setExecutionInterrupted(true);
                $context->setException($e);
                $extensions->onInterrupted($context);

                $session->close();

                throw $e;
            }
        }
    }
}
