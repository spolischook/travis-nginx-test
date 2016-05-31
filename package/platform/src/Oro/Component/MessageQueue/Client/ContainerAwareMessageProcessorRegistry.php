<?php
namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerAwareMessageProcessorRegistry implements MessageProcessorRegistryInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var MessageProcessorInterface[]
     */
    protected $processors;

    /**
     * @param array $processors
     */
    public function __construct(array $processors = [])
    {
        $this->processors = $processors;
    }

    /**
     * @param string $processorName
     * @param string $serviceId
     */
    public function set($processorName, $serviceId)
    {
        $this->processors[$processorName] = $serviceId;
    }

    /**
     * {@inheritdoc}
     */
    public function get($processorName)
    {
        if (false == isset($this->processors[$processorName])) {
            throw new \LogicException(sprintf('MessageProcessor was not found. processorName: "%s"', $processorName));
        }

        if (null === $this->container) {
            throw new \LogicException('Container was not set');
        }

        $processor = $this->container->get($this->processors[$processorName]);

        if (false == $processor instanceof MessageProcessorInterface) {
            throw new \LogicException(
                sprintf('Invalid instance of message processor. expected: "%s", got: "%s"', MessageProcessorInterface::class, is_object($processor) ? get_class($processor) : gettype($processor))
            );
        }

        return $processor;
    }
}
