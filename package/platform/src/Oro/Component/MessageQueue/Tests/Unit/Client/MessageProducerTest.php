<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\MessagePriorityEnum;
use Oro\Component\MessageQueue\Transport\MessageProducerInterface as TransportMessageProducer;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullQueue;
use Oro\Component\MessageQueue\Client\MessageProducer;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DriverInterface;

class MessageProducerTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new MessageProducer($this->createTransportMessageProducer(), $this->createDriverStub());
    }

    public function testShouldSendMessageAndCreateSchema()
    {
        $config = new Config('', 'route-message-processor', 'router-queue', '', '');
        $queue = new NullQueue('queue');

        $message = new NullMessage();

        $messageProducer = $this->createTransportMessageProducer();
        $messageProducer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queue), $this->identicalTo($message))
        ;

        $driver = $this->createDriverStub($message, $config, $queue);

        $producer = new MessageProducer($messageProducer, $driver);
        $producer->sendTo('topic', 'message');

        $expectedProperties = [
            'oro.message_queue.client.topic_name' => 'topic',
            'oro.message_queue.client.processor_name' => 'route-message-processor',
            'oro.message_queue.client.queue_name' => 'router-queue',
        ];

        $this->assertEquals($expectedProperties, $message->getProperties());
    }

    public function testShouldSendMessageWithLowPriorityByDefault()
    {
        $config = new Config('', 'route-message-processor', 'router-queue', '', '');
        $queue = new NullQueue('queue');

        $message = new NullMessage();

        $messageProducer = $this->createTransportMessageProducer();

        $driver = $this->createDriverStub($message, $config, $queue);
        $driver
            ->expects($this->once())
            ->method('setMessagePriority')
            ->with($this->identicalTo($message), MessagePriorityEnum::LOW)
        ;

        $producer = new MessageProducer($messageProducer, $driver);
        $producer->sendTo('topic', 'message');
    }

    public function testShouldSendMessageWithCustomPriority()
    {
        $config = new Config('', 'route-message-processor', 'router-queue', '', '');
        $queue = new NullQueue('queue');

        $message = new NullMessage();

        $messageProducer = $this->createTransportMessageProducer();

        $driver = $this->createDriverStub($message, $config, $queue);
        $driver
            ->expects($this->once())
            ->method('setMessagePriority')
            ->with($this->identicalTo($message), MessagePriorityEnum::HIGH)
        ;

        $producer = new MessageProducer($messageProducer, $driver);
        $producer->sendTo('topic', 'message', MessagePriorityEnum::HIGH);
    }

    public function testShouldSendNullAsPlainText()
    {
        $config = new Config('', 'route-message-processor', 'router-queue', '', '');
        $queue = new NullQueue('queue');

        $message = new NullMessage();

        $messageProducer = $this->createTransportMessageProducer();
        $messageProducer
            ->expects($this->once())
            ->method('send')
        ;

        $driver = $this->createDriverStub($message, $config, $queue);

        $producer = new MessageProducer($messageProducer, $driver);
        $producer->sendTo('topic', null);

        $this->assertSame('', $message->getBody());
        $this->assertSame('text/plain', $message->getHeader('content_type'));
    }

    public function testShouldSendStringAsPlainText()
    {
        $config = new Config('', 'route-message-processor', 'router-queue', '', '');
        $queue = new NullQueue('queue');

        $message = new NullMessage();

        $messageProducer = $this->createTransportMessageProducer();
        $messageProducer
            ->expects($this->once())
            ->method('send')
        ;

        $driver = $this->createDriverStub($message, $config, $queue);

        $producer = new MessageProducer($messageProducer, $driver);
        $producer->sendTo('topic', 'message');

        $this->assertSame('message', $message->getBody());
        $this->assertSame('text/plain', $message->getHeader('content_type'));
    }

    public function testShouldSendArrayAsJson()
    {
        $config = new Config('', 'route-message-processor', 'router-queue', '', '');
        $queue = new NullQueue('queue');

        $message = new NullMessage();

        $messageProducer = $this->createTransportMessageProducer();
        $messageProducer
            ->expects($this->once())
            ->method('send')
        ;

        $driver = $this->createDriverStub($message, $config, $queue);

        $producer = new MessageProducer($messageProducer, $driver);
        $producer->sendTo('topic', ['foo' => 'fooVal']);

        $this->assertSame('{"foo":"fooVal"}', $message->getBody());
        $this->assertSame('application/json', $message->getHeader('content_type'));
    }

    public function testThrowIfBodyIsNotSerializable()
    {
        $config = new Config('', 'route-message-processor', 'router-queue', '', '');
        $queue = new NullQueue('queue');

        $message = new NullMessage();

        $messageProducer = $this->createTransportMessageProducer();
        $messageProducer
            ->expects($this->never())
            ->method('send')
        ;

        $driver = $this->createDriverStub($message, $config, $queue);

        $producer = new MessageProducer($messageProducer, $driver);

        $this->setExpectedException(
            \InvalidArgumentException::class,
            'The message\'s body must be either null, scalar or array. Got: stdClass'
        );
        $producer->sendTo('topic', new \stdClass());
    }

    public function testSendShouldThrowExceptionIfTopicNamePropertyIsNotSet()
    {
        $this->setExpectedException(
            \LogicException::class,
            'Parameter "oro.message_queue.client.topic_name" is required.'
        );

        $queue = new NullQueue('');
        
        $message = new NullMessage();

        $producer = new MessageProducer($this->createTransportMessageProducer(), $this->createDriverStub());
        $producer->send($queue, $message);
    }

    public function testSendShouldThrowExceptionIfProcessorNamePropertyIsNotSet()
    {
        $this->setExpectedException(
            \LogicException::class,
            'Parameter "oro.message_queue.client.processor_name" is required.'
        );

        $queue = new NullQueue('');

        $message = new NullMessage();
        $message->setProperties([
            Config::PARAMETER_TOPIC_NAME => 'topic'
        ]);

        $producer = new MessageProducer($this->createTransportMessageProducer(), $this->createDriverStub());
        $producer->send($queue, $message);
    }

    public function testSendShouldForceScalarsToStringAndSetTextContentType()
    {
        $queue = new NullQueue('');

        $message = new NullMessage();
        $message->setBody(12345);
        $message->setProperties([
            Config::PARAMETER_TOPIC_NAME => 'topic',
            Config::PARAMETER_PROCESSOR_NAME => 'processor',
        ]);

        $messageProcessor = $this->createTransportMessageProducer();
        $messageProcessor
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queue, $this->identicalTo($message)))
        ;

        $producer = new MessageProducer($messageProcessor, $this->createDriverStub());
        $producer->send($queue, $message);

        $this->assertEquals(['content_type' => 'text/plain'], $message->getHeaders());
        $this->assertInternalType('string', $message->getBody());
        $this->assertEquals('12345', $message->getBody());
    }

    public function testSendShouldForceNullToStringAndSetTextContentType()
    {
        $queue = new NullQueue('');

        $message = new NullMessage();
        $message->setBody(null);
        $message->setProperties([
            Config::PARAMETER_TOPIC_NAME => 'topic',
            Config::PARAMETER_PROCESSOR_NAME => 'processor',
        ]);

        $messageProcessor = $this->createTransportMessageProducer();
        $messageProcessor
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queue, $this->identicalTo($message)))
        ;

        $producer = new MessageProducer($messageProcessor, $this->createDriverStub());
        $producer->send($queue, $message);

        $this->assertEquals(['content_type' => 'text/plain'], $message->getHeaders());
        $this->assertInternalType('string', $message->getBody());
        $this->assertEquals('', $message->getBody());
    }

    public function testSendShouldAllowAnyContentTypeIfBodyIsScalar()
    {
        $queue = new NullQueue('');

        $message = new NullMessage();
        $message->setBody('string');
        $message->setHeaders([
            'content_type' => 'my/content/type',
        ]);
        $message->setProperties([
            Config::PARAMETER_TOPIC_NAME => 'topic',
            Config::PARAMETER_PROCESSOR_NAME => 'processor',
        ]);

        $messageProcessor = $this->createTransportMessageProducer();
        $messageProcessor
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queue, $this->identicalTo($message)))
        ;

        $producer = new MessageProducer($messageProcessor, $this->createDriverStub());
        $producer->send($queue, $message);

        $this->assertEquals(['content_type' => 'my/content/type'], $message->getHeaders());
        $this->assertInternalType('string', $message->getBody());
        $this->assertEquals('string', $message->getBody());
    }

    public function testSendShouldThrowExceptionIfBodyIsArrayButContentTypeIsNotJson()
    {
        $this->setExpectedException(
            \LogicException::class,
            'Content type "application/json" only allowed when body is array'
        );

        $queue = new NullQueue('');

        $message = new NullMessage();
        $message->setBody([]);
        $message->setHeaders([
            'content_type' => 'invalid/content/type',
        ]);
        $message->setProperties([
            Config::PARAMETER_TOPIC_NAME => 'topic',
            Config::PARAMETER_PROCESSOR_NAME => 'processor',
        ]);

        $messageProcessor = $this->createTransportMessageProducer();

        $producer = new MessageProducer($messageProcessor, $this->createDriverStub());
        $producer->send($queue, $message);
    }

    public function testSendShouldJsonEncodeBodyIfBodyIsArrayAndSetContentTypeJson()
    {
        $queue = new NullQueue('');

        $message = new NullMessage();
        $message->setBody(['key' => 'value']);
        $message->setProperties([
            Config::PARAMETER_TOPIC_NAME => 'topic',
            Config::PARAMETER_PROCESSOR_NAME => 'processor',
        ]);

        $messageProcessor = $this->createTransportMessageProducer();
        $messageProcessor
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queue, $this->identicalTo($message)))
        ;

        $producer = new MessageProducer($messageProcessor, $this->createDriverStub());
        $producer->send($queue, $message);

        $this->assertEquals(['content_type' => 'application/json'], $message->getHeaders());
        $this->assertInternalType('string', $message->getBody());
        $this->assertEquals('{"key":"value"}', $message->getBody());
    }

    public function testSendShouldThrowExceptionIfBodyIsObject()
    {
        $this->setExpectedException(
            \LogicException::class,
            'The message\'s body must be either null, scalar or array. Got: stdClass'
        );

        $queue = new NullQueue('');

        $message = new NullMessage();
        $message->setBody(new \stdClass());
        $message->setProperties([
            Config::PARAMETER_TOPIC_NAME => 'topic',
            Config::PARAMETER_PROCESSOR_NAME => 'processor',
        ]);

        $messageProcessor = $this->createTransportMessageProducer();

        $producer = new MessageProducer($messageProcessor, $this->createDriverStub());
        $producer->send($queue, $message);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DriverInterface
     */
    protected function createDriverStub($message = null, $config = null, $queue = null)
    {
        $driverMock = $this->getMock(DriverInterface::class, [], [], '', false);
        $driverMock
            ->expects($this->any())
            ->method('createMessage')
            ->will($this->returnValue($message))
        ;
        $driverMock
            ->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($config))
        ;
        $driverMock
            ->expects($this->any())
            ->method('createQueue')
            ->will($this->returnValue($queue))
        ;

        return $driverMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TransportMessageProducer
     */
    protected function createTransportMessageProducer()
    {
        return $this->getMock(TransportMessageProducer::class, [], [], '', false);
    }
}
