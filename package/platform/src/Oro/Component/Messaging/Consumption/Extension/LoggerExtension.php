<?php
namespace Oro\Component\Messaging\Consumption\Extension;

use Oro\Component\Messaging\Consumption\Context;
use Oro\Component\Messaging\Consumption\Extension;
use Oro\Component\Messaging\Consumption\ExtensionTrait;
use Psr\Log\LoggerInterface;

class LoggerExtension implements Extension
{
    use ExtensionTrait;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function onStart(Context $context)
    {
        $context->setLogger($this->logger);
        $context->getLogger()->debug(sprintf('Set context\'s logger %s', get_class($this->logger)));
        $context->getLogger()->info('Start consuming');
    }

    /**
     * {@inheritdoc}
     */
    public function onBeforeReceive(Context $context)
    {
        $context->getLogger()->info('Before receive');
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context)
    {
        $context->getLogger()->info('Message received');
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        $context->getLogger()->info(sprintf('Message processed: %s', $context->getStatus()));
    }

    /**
     * {@inheritdoc}
     */
    public function onIdle(Context $context)
    {
        $context->getLogger()->info(sprintf('Idle'));
    }

    /**
     * {@inheritdoc}
     */
    public function onInterrupted(Context $context)
    {
        if ($context->getException()) {
            $context->getLogger()->info(sprintf('Consuming interrupted by exception'));
        } else {
            $context->getLogger()->info(sprintf('Consuming interrupted'));
        }
    }
}
