<?php
namespace Oro\Component\AmqpMessageQueue\DependencyInjection;

use Oro\Component\AmqpMessageQueue\Consumption\Extension\DelayRedeliveredMessageAmqpExtension;
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpConnection;
use Oro\Component\MessageQueue\DependencyInjection\TransportFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class AmqpTransportFactory implements TransportFactoryInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name = 'amqp')
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
                ->scalarNode('host')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('port')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('user')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('password')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('vhost')->isRequired()->cannotBeEmpty()->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function createService(ContainerBuilder $container, array $config)
    {
        $delayRedeliveredMessageExtension = new Definition(DelayRedeliveredMessageAmqpExtension::class);
        $delayRedeliveredMessageExtension->setPublic(false);
        $delayRedeliveredMessageExtension->addTag('oro_message_queue.consumption.extension');
        $container->setDefinition(
            'oro_message_queue.consumption.delay_redelivered_message_amqp_extension',
            $delayRedeliveredMessageExtension
        );

        $connection = new Definition(AmqpConnection::class, [$config]);
        $connection->setFactory([AmqpConnection::class, 'createFromConfig']);

        $connectionId = sprintf('oro_message_queue.transport.%s.connection', $this->getName());
        
        $container->setDefinition($connectionId, $connection);
        
        return $connectionId;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
