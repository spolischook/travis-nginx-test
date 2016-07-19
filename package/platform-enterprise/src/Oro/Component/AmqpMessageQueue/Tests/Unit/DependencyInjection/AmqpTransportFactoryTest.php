<?php
namespace Oro\Component\AmqpMessageQueue\Tests\Unit\DependencyInjection;

use Oro\Component\AmqpMessageQueue\DependencyInjection\AmqpTransportFactory;
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpConnection;
use Oro\Component\MessageQueue\DependencyInjection\TransportFactoryInterface;
use Oro\Component\MessageQueue\Transport\Null\NullConnection;
use Oro\Component\Testing\ClassExtensionTrait;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AmqpTransportFactoryTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTransportFactoryInterface()
    {
        $this->assertClassImplements(TransportFactoryInterface::class, AmqpTransportFactory::class);
    }

    public function testCouldBeConstructedWithDefaultName()
    {
        $transport = new AmqpTransportFactory();

        $this->assertEquals('amqp', $transport->getName());
    }

    public function testCouldBeConstructedWithCustomName()
    {
        $transport = new AmqpTransportFactory('theCustomName');

        $this->assertEquals('theCustomName', $transport->getName());
    }

    public function testThrowIfHostOptionNotConfigured()
    {
        $transport = new AmqpTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        
        $this->setExpectedException(
            InvalidConfigurationException::class,
            'The child node "host" at path "foo" must be configured.'
        );

        $config = $processor->process($tb->buildTree(), [[]]);

        $this->assertEquals([], $config);
    }

    public function testThrowIfPortOptionNotConfigured()
    {
        $transport = new AmqpTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();

        $this->setExpectedException(
            InvalidConfigurationException::class,
            'The child node "port" at path "foo" must be configured.'
        );

        $config = $processor->process($tb->buildTree(), [[
            'host' => 'aHost'
        ]]);

        $this->assertEquals([], $config);
    }

    public function testThrowIfUserOptionNotConfigured()
    {
        $transport = new AmqpTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();

        $this->setExpectedException(
            InvalidConfigurationException::class,
            'The child node "user" at path "foo" must be configured.'
        );

        $config = $processor->process($tb->buildTree(), [[
            'host' => 'aHost',
            'port' => 'aPort',
        ]]);

        $this->assertEquals([], $config);
    }

    public function testThrowIfPasswordOptionNotConfigured()
    {
        $transport = new AmqpTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();

        $this->setExpectedException(
            InvalidConfigurationException::class,
            'The child node "password" at path "foo" must be configured.'
        );

        $config = $processor->process($tb->buildTree(), [[
            'host' => 'aHost',
            'port' => 'aPort',
            'user' => 'aUser',
        ]]);

        $this->assertEquals([], $config);
    }

    public function testThrowIfVHostOptionNotConfigured()
    {
        $transport = new AmqpTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();

        $this->setExpectedException(
            InvalidConfigurationException::class,
            'The child node "vhost" at path "foo" must be configured.'
        );

        $config = $processor->process($tb->buildTree(), [[
            'host' => 'aHost',
            'port' => 'aPort',
            'user' => 'aUser',
            'password' => 'aPassword',
        ]]);

        $this->assertEquals([], $config);
    }

    public function testShouldAllowAddConfiguration()
    {
        $transport = new AmqpTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [[
            'host' => 'aHost',
            'port' => 'aPort',
            'user' => 'aUser',
            'password' => 'aPassword',
            'vhost' => 'aVHost',
        ]]);

        $this->assertEquals([
            'host' => 'aHost',
            'port' => 'aPort',
            'user' => 'aUser',
            'password' => 'aPassword',
            'vhost' => 'aVHost',
        ], $config);
    }

    public function testShouldCreateService()
    {
        $container = new ContainerBuilder();

        $transport = new AmqpTransportFactory();

        $serviceId = $transport->createService($container, ['theConfig']);

        $this->assertEquals('oro_message_queue.transport.amqp.connection', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $connection = $container->getDefinition($serviceId);
        $this->assertEquals(AmqpConnection::class, $connection->getClass());
        $this->assertEquals([AmqpConnection::class, 'createFromConfig'], $connection->getFactory());
        $this->assertEquals([['theConfig']], $connection->getArguments());
    }
}
