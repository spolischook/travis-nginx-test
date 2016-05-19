<?php
namespace Oro\Component\Messaging\Tests\Transport\Amqp;

use Oro\Component\Messaging\Transport\Amqp\AmqpMessage;
use Oro\Component\Messaging\Transport\Amqp\AmqpMessageProducer;
use Oro\Component\Messaging\Transport\Amqp\AmqpQueue;
use Oro\Component\Messaging\Transport\Amqp\AmqpTopic;
use Oro\Component\Messaging\Transport\Destination;
use Oro\Component\Testing\ClassExtensionTrait;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage as AMQPLibMessage;

class AmqpMessageProducerTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageProducerInterface()
    {
        $this->assertClassImplements(
            'Oro\Component\Messaging\Transport\MessageProducer',
            'Oro\Component\Messaging\Transport\Amqp\AmqpMessageProducer'
        );
    }

    public function testCouldBeConstructedWithChannelAsArgument()
    {
        new AmqpMessageProducer($this->createAmqpChannel());
    }

    /**
     * @expectedException \Oro\Component\Messaging\Transport\Exception\InvalidDestinationException
     * @expectedExceptionMessage A destination is not understood.
     */
    public function testThrowInvalidDestinationIfGivenNotTopic()
    {
        $producer = new AmqpMessageProducer($this->createAmqpChannel());

        $invalidDestination = $this->createDestination();

        $producer->send($invalidDestination, new AmqpMessage());
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
        return $this->getMock('PhpAmqpLib\Channel\AMQPChannel', [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Destination
     */
    protected function createDestination()
    {
        return $this->getMock('Oro\Component\Messaging\Transport\Destination');
    }
}
