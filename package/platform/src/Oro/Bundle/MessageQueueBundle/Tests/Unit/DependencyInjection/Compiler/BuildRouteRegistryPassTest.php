<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\DependencyInjection\Compiler;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\BuildRouteRegistryPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class BuildRouteRegistryPassTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new BuildRouteRegistryPass();
    }

    public function testShouldBuildRouteRegistry()
    {
        $container = new ContainerBuilder();

        $processor = new Definition();
        $processor->addTag('oro_message_queue.zero_config.message_processor', [
            'topicName' => 'topic',
            'processorName' => 'processor',
            'destinationName' => 'destination',
        ]);
        $container->setDefinition('processor', $processor);

        $router = new Definition();
        $router->setArguments(['', '']);
        $container->setDefinition('oro_message_queue.zero_config.router', $router);

        $pass = new BuildRouteRegistryPass();
        $pass->process($container);

        $expectedRoutes = [
            'topic' =>  [
                ['processor', 'destination']
            ]
        ];

        $this->assertEquals($expectedRoutes, $router->getArgument(1));
    }

    public function testShouldThrowExceptionIfTopicNameIsNotSet()
    {
        $this->setExpectedException(
            \LogicException::class,
            'Topic name is not set but it is required. service: "processor", tag: "oro_message_queue.zero_config.message'
        );

        $container = new ContainerBuilder();

        $processor = new Definition();
        $processor->addTag('oro_message_queue.zero_config.message_processor');
        $container->setDefinition('processor', $processor);

        $router = new Definition();
        $router->setArguments(['', '']);
        $container->setDefinition('oro_message_queue.zero_config.router', $router);

        $pass = new BuildRouteRegistryPass();
        $pass->process($container);
    }

    public function testShouldSetServiceIdAdProcessorIdIfIsNotSetInTag()
    {
        $container = new ContainerBuilder();

        $processor = new Definition();
        $processor->addTag('oro_message_queue.zero_config.message_processor', [
            'topicName' => 'topic',
            'destinationName' => 'destination',
        ]);
        $container->setDefinition('processor-service-id', $processor);

        $router = new Definition();
        $router->setArguments(['', '']);
        $container->setDefinition('oro_message_queue.zero_config.router', $router);

        $pass = new BuildRouteRegistryPass();
        $pass->process($container);

        $expectedRoutes = [
            'topic' =>  [
                ['processor-service-id', 'destination']
            ]
        ];

        $this->assertEquals($expectedRoutes, $router->getArgument(1));
    }

    public function testShouldSetDestinationToNullIfIsNotSetInTag()
    {
        $container = new ContainerBuilder();

        $processor = new Definition();
        $processor->addTag('oro_message_queue.zero_config.message_processor', [
            'topicName' => 'topic',
        ]);
        $container->setDefinition('processor-service-id', $processor);

        $router = new Definition();
        $router->setArguments(['', '']);
        $container->setDefinition('oro_message_queue.zero_config.router', $router);

        $pass = new BuildRouteRegistryPass();
        $pass->process($container);

        $expectedRoutes = [
            'topic' =>  [
                ['processor-service-id', null]
            ]
        ];

        $this->assertEquals($expectedRoutes, $router->getArgument(1));
    }
}
