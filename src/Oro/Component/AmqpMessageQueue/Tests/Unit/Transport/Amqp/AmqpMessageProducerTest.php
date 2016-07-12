<?php
namespace Oro\Component\AmqpMessageQueue\Tests\Unit\Transport\Amqp;

use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpMessage;
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpMessageProducer;
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpQueue;
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpTopic;
use Oro\Component\MessageQueue\Transport\DestinationInterface;
use Oro\Component\MessageQueue\Transport\Exception\Exception;
use Oro\Component\MessageQueue\Transport\Exception\InvalidDestinationException;
use Oro\Component\MessageQueue\Transport\Exception\InvalidMessageException;
use Oro\Component\MessageQueue\Transport\MessageProducerInterface;
use Oro\Component\Testing\ClassExtensionTrait;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage as AMQPLibMessage;

class AmqpMessageProducerTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageProducerInterface()
    {
        $this->assertClassImplements(MessageProducerInterface::class, AmqpMessageProducer::class);
    }

    public function testCouldBeConstructedWithChannelAsArgument()
    {
        new AmqpMessageProducer($this->createAmqpChannel());
    }

    public function testThrowInvalidDestinationIfGivenNotTopic()
    {
        $producer = new AmqpMessageProducer($this->createAmqpChannel());

        $invalidDestination = $this->createDestination();

        $this->setExpectedException(
            InvalidDestinationException::class,
            'The destination must be an instance of Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpTopic'
        );

        $producer->send($invalidDestination, new AmqpMessage());
    }

    public function testThrowIfBodyIsNotScalarButObject()
    {
        $producer = new AmqpMessageProducer($this->createAmqpChannel());

        $message = new AmqpMessage();
        $message->setBody(new \stdClass());

        $this->setExpectedException(
            InvalidMessageException::class,
            'The message body must be a scalar or null. Got: stdClass'
        );

        $producer->send(new AmqpTopic('aTopic'), $message);
    }

    public function testThrowIfBodyIsNotSerializable()
    {
        $producer = new AmqpMessageProducer($this->createAmqpChannel());

        $message = new AmqpMessage();
        $message->setBody(['foo' => 'fooVal']);

        $this->setExpectedException(
            InvalidMessageException::class,
            'The message body must be a scalar or null. Got: array'
        );

        $producer->send(new AmqpTopic('aTopic'), $message);
    }

    public function testShouldSendMessageToTopic()
    {
        $channel = $this->createAmqpChannel();
        $channel
            ->expects($this->once())
            ->method('basic_publish')
            ->with($this->isInstanceOf('PhpAmqpLib\Message\AMQPMessage'), 'theTopicName')
        ;

        $producer = new AmqpMessageProducer($channel);

        $topic = new AmqpTopic('theTopicName');

        $producer->send($topic, new AmqpMessage());
    }

    public function testShouldCorrectlyPassOptionsToBasicPublishMethodWhileSendingMessageToTopic()
    {
        $topic = new AmqpTopic('aTopicName');
        $topic->setRoutingKey('theTopicRoutingKey');
        $topic->setImmediate('theImmediateBool');
        $topic->setMandatory('theMandatoryBool');

        $message = new AmqpMessage();

        $channel = $this->createAmqpChannel();
        $channel
            ->expects($this->once())
            ->method('basic_publish')
            ->with($this->anything(), $this->anything(), 'theTopicRoutingKey', 'theMandatoryBool', 'theImmediateBool')
        ;

        $producer = new AmqpMessageProducer($channel);

        $producer->send($topic, $message);
    }

    public function testShouldSendMessageToQueue()
    {
        $channel = $this->createAmqpChannel();
        $channel
            ->expects($this->once())
            ->method('basic_publish')
            ->with($this->isInstanceOf('PhpAmqpLib\Message\AMQPMessage'), '', 'theQueueName')
        ;

        $producer = new AmqpMessageProducer($channel);

        $queue = new AmqpQueue('theQueueName');

        $producer->send($queue, new AmqpMessage());
    }

    public function testCatchAnyExceptionHappendDuringPublishingToQueueAndConvertThemToTransportException()
    {
        $channel = $this->createAmqpChannel();
        $channel
            ->expects($this->once())
            ->method('basic_publish')
            ->willThrowException(new \Exception)
        ;

        $producer = new AmqpMessageProducer($channel);

        $queue = new AmqpQueue('aName');

        $this->setExpectedException(
            Exception::class,
            'The transport fails to send the message due to some internal error.'
        );
        $producer->send($queue, new AmqpMessage());
    }

    public function testCatchAnyExceptionHappendDuringPublishingToTopicAndConvertThemToTransportException()
    {
        $channel = $this->createAmqpChannel();
        $channel
            ->expects($this->once())
            ->method('basic_publish')
            ->willThrowException(new \Exception)
        ;

        $producer = new AmqpMessageProducer($channel);

        $topic = new AmqpTopic('aName');

        $this->setExpectedException(
            Exception::class,
            'The transport fails to send the message due to some internal error.'
        );
        $producer->send($topic, new AmqpMessage());
    }

    public function testShouldCorrectlyConvertMessageBodyToLibMessageBody()
    {
        $message = new AmqpMessage();
        $message->setBody('theBody');

        $channel = $this->createAmqpChannel();
        $channel
            ->expects($this->once())
            ->method('basic_publish')
            ->with($this->isInstanceOf('PhpAmqpLib\Message\AMQPMessage'), 'aTopicName')
            ->willReturnCallback(function (AMQPLibMessage $message) {
                $this->assertEquals('theBody', $message->getBody());
            })
        ;

        $producer = new AmqpMessageProducer($channel);

        $topic = new AmqpTopic('aTopicName');

        $producer->send($topic, $message);
    }

    public function testShouldCorrectlyConvertMessagePropertiesToLibMessageOne()
    {
        $message = new AmqpMessage();
        $message->setProperties(['aPropertyKey' => 'aPropertyVal']);

        $channel = $this->createAmqpChannel();
        $channel
            ->expects($this->once())
            ->method('basic_publish')
            ->with($this->isInstanceOf('PhpAmqpLib\Message\AMQPMessage'), 'aTopicName')
            ->willReturnCallback(function (AMQPLibMessage $message) {
                $this->assertNotEmpty($message->get_properties());
                $this->assertInstanceOf('PhpAmqpLib\Wire\AMQPTable', $message->get('application_headers'));
                $this->assertEquals(
                    ['aPropertyKey' => 'aPropertyVal'],
                    $message->get('application_headers')->getNativeData()
                );
            })
        ;

        $producer = new AmqpMessageProducer($channel);

        $topic = new AmqpTopic('aTopicName');

        $producer->send($topic, $message);
    }

    public function testShouldCorrectlyConvertMessageHeadersToLibMessageOne()
    {
        $message = new AmqpMessage();
        $message->setHeaders(['timestamp' => 123123123]);

        $channel = $this->createAmqpChannel();
        $channel
            ->expects($this->once())
            ->method('basic_publish')
            ->with($this->isInstanceOf('PhpAmqpLib\Message\AMQPMessage'), 'aTopicName')
            ->willReturnCallback(function (AMQPLibMessage $message) {
                $this->assertNotEmpty($message->get_properties());
                $this->assertEquals(123123123, $message->get('timestamp'));
            })
        ;

        $producer = new AmqpMessageProducer($channel);

        $topic = new AmqpTopic('aTopicName');

        $producer->send($topic, $message);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AMQPChannel
     */
    protected function createAmqpChannel()
    {
        return $this->getMock(AMQPChannel::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DestinationInterface
     */
    protected function createDestination()
    {
        return $this->getMock(DestinationInterface::class);
    }
}
