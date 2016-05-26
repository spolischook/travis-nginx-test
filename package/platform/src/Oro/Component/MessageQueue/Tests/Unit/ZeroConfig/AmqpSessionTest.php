<?php
namespace Oro\Component\MessageQueue\Tests\Unit\ZeroConfig;

use Oro\Component\MessageQueue\Transport\Amqp\AmqpMessage;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpQueue;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpSession as TransportAmqpSession;
use Oro\Component\MessageQueue\ZeroConfig\FrontProducer;
use Oro\Component\MessageQueue\ZeroConfig\AmqpSession;
use Oro\Component\MessageQueue\ZeroConfig\Config;

class AmqpSessionTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new AmqpSession($this->createTransportSessionMock(), new Config('', '', '', '', ''));
    }

    public function testShouldCreateMessageInstance()
    {
        $message = new AmqpMessage();

        $expectedProperties = [
            'delivery_mode' => AmqpMessage::DELIVERY_MODE_PERSISTENT,
        ];

        $transportSession = $this->createTransportSessionMock();
        $transportSession
            ->expects($this->once())
            ->method('createMessage')
            ->with(null, [], $expectedProperties)
            ->will($this->returnValue($message))
        ;

        $session = new AmqpSession($transportSession, new Config('', '', '', '', ''));
        $result = $session->createMessage();

        $this->assertSame($message, $result);
    }

    public function testShouldCreateProducerInstance()
    {
        $transportSession = $this->createTransportSessionMock();
        $transportSession
            ->expects($this->once())
            ->method('createProducer')
            ->will($this->returnValue('producer-instance'))
        ;

        $session = new AmqpSession($transportSession, new Config('', '', '', '', ''));
        $result = $session->createProducer();

        $this->assertEquals('producer-instance', $result);
    }

    public function testShouldCreateFrontProducerInstance()
    {
        $session = new AmqpSession($this->createTransportSessionMock(), new Config('', '', '', '', ''));
        $result = $session->createFrontProducer();

        $this->assertInstanceOf(FrontProducer::class, $result);
    }

    public function testShouldReturnConfigInstance()
    {
        $config = new Config('', '', '', '', '');

        $session = new AmqpSession($this->createTransportSessionMock(), $config);
        $result = $session->getConfig();

        $this->assertSame($config, $result);
    }

    public function testShouldCreateQueueWithExpectedParameters()
    {
        $queue = new AmqpQueue('');

        $config = new Config('', '', '', '', '');

        $transportSession = $this->createTransportSessionMock();
        $transportSession
            ->expects($this->once())
            ->method('createQueue')
            ->with('queue-name')
            ->will($this->returnValue($queue))
        ;
        $transportSession
            ->expects($this->once())
            ->method('declareQueue')
            ->with($this->identicalTo($queue))
        ;

        $session = new AmqpSession($transportSession, $config);
        $result = $session->createQueue('queue-name');

        $this->assertSame($queue, $result);

        $this->assertEmpty($queue->getConsumerTag());
        $this->assertEmpty($queue->getTable());
        $this->assertFalse($queue->isExclusive());
        $this->assertFalse($queue->isAutoDelete());
        $this->assertFalse($queue->isPassive());
        $this->assertFalse($queue->isNoWait());
        $this->assertTrue($queue->isDurable());
        $this->assertFalse($queue->isNoAck());
        $this->assertFalse($queue->isNoLocal());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TransportAmqpSession
     */
    protected function createTransportSessionMock()
    {
        return $this->getMock(TransportAmqpSession::class, [], [], '', false);
    }
}
