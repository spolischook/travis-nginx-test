<?php
namespace Oro\Component\AmqpMessageQueue\Tests\Unit\Transport\Amqp;

use Oro\Component\MessageQueue\Transport\Exception\InvalidMessageException;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\Testing\ClassExtensionTrait;
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpMessage;
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpMessageConsumer;
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpQueue;
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpSession;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage as AMQPLibMessage;
use PhpAmqpLib\Wire\AMQPTable;

// @codingStandardsIgnoreStart

class AmqpMessageConsumerTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageConsumerInterface()
    {
        $this->assertClassImplements(MessageConsumerInterface::class, AmqpMessageConsumer::class);
    }

    public function testCouldBeConstructedWithSessionAndQueueAsArguments()
    {
        new AmqpMessageConsumer($this->createAmqpSessionStub(), $this->createAmqpChannel(), new AmqpQueue('aName'));
    }

    public function testShouldThrowExceptionIfChannelHadCallbackBeforeInitialization()
    {
        $this->setExpectedException(\LogicException::class, 'The channel has a callback set. We cannot use this channel because of unexpected behavior in such case');

        $channelMock = $this->createAmqpChannel();
        $channelMock->callbacks = ['has-registered-callback'];

        $sessionStub = $this->createAmqpSessionStub();

        $consumer = new AmqpMessageConsumer($sessionStub, $channelMock,  new AmqpQueue('theQueueName'));

        $consumer->receive();
    }

    public function testShouldSubscribeRegisterConsumerBeforeReceive()
    {
        $channelMock = $this->createAmqpChannel();
        $channelMock
            ->expects($this->exactly(1))
            ->method('basic_qos')
            ->with(null, 1, false)
        ;
        $channelMock
            ->expects($this->exactly(1))
            ->method('basic_consume')
            ->with('theQueueName')
        ;
        $channelMock
            ->expects($this->exactly(2))
            ->method('wait')
        ;

        $sessionStub = $this->createAmqpSessionStub();

        $consumer = new AmqpMessageConsumer($sessionStub, $channelMock,  new AmqpQueue('theQueueName'));

        $consumer->receive();
        $consumer->receive();
    }

    public function testShouldNotSubscribeToQueueOnReceiveNoWaitCall()
    {
        $channelMock = $this->createAmqpChannel();
        $channelMock
            ->expects($this->never())
            ->method('basic_consume')
        ;
        $channelMock
            ->expects($this->exactly(2))
            ->method('basic_get')
        ;

        $sessionStub = $this->createAmqpSessionStub();

        $consumer = new AmqpMessageConsumer($sessionStub, $channelMock, new AmqpQueue('theQueueName'));

        $consumer->receiveNoWait();
        $consumer->receiveNoWait();
    }

    public function testShouldWaitForMessageAndReturnItOnReceiveNoWait()
    {
        $expectedMessage = new AmqpMessage();
        $expectedInternalMessage = new AMQPLibMessage();
        $expectedInternalMessage->delivery_info['delivery_tag'] = 'theDeliveryTag';
        $expectedInternalMessage->delivery_info['redelivered'] = 'theRedeliveredBool';
        $expectedInternalMessage->delivery_info['exchange'] = 'theExchange';

        $channelStub = new AMQPChannelStub();
        $channelStub->receivedInternalMessage = $expectedInternalMessage;

        $sessionStub = $this->createAmqpSessionStub();
        $sessionStub
            ->expects($this->once())
            ->method('createMessage')
            ->with(null, [], [])
            ->willReturn($expectedMessage)
        ;

        $consumer = new AmqpMessageConsumer($sessionStub, $channelStub, new AmqpQueue('aName'));

        $actualMessage = $consumer->receiveNoWait();
        $this->assertSame($expectedMessage, $actualMessage);
        $this->assertSame('theDeliveryTag', $actualMessage->getDeliveryTag());
        $this->assertSame('theRedeliveredBool', $actualMessage->isRedelivered());
        $this->assertSame('theExchange', $actualMessage->getExchange());
    }

    public function testShouldCorrectlyPassQueueOptionsToBasicConsumeMethod()
    {
        $queue = new AmqpQueue('aQueueName');
        $queue->setConsumerTag('theConsumerTag');
        $queue->setNoLocal('theLocalBool');
        $queue->setNoAck('theAskBool');
        $queue->setExclusive('theExclusiveBool');
        $queue->setNoWait('theNoWaitBool');

        $channelMock = $this->createAmqpChannel();
        $channelMock
            ->expects($this->once())
            ->method('basic_consume')
            ->with($this->anything(), 'theConsumerTag', 'theLocalBool', 'theAskBool', 'theExclusiveBool', 'theNoWaitBool')
        ;

        $sessionStub = $this->createAmqpSessionStub();

        $consumer = new AmqpMessageConsumer($sessionStub, $channelMock, $queue);

        $consumer->receive();
    }

    public function testShouldRegisterCreateMessageCallbackOnlyOnFirstReceive()
    {
        $channelMock = $this->createAmqpChannel();
        $channelMock
            ->expects($this->exactly(1))
            ->method('basic_consume')
            ->willReturnCallback(function () {
                $this->assertInstanceOf('Closure', func_get_arg(6));
            })
        ;
        $channelMock
            ->expects($this->exactly(2))
            ->method('wait')
        ;

        $sessionStub = $this->createAmqpSessionStub();

        $consumer = new AmqpMessageConsumer($sessionStub, $channelMock, new AmqpQueue('aName'));

        $consumer->receive();
        $consumer->receive();
    }

    public function testShouldPassTimeoutWhileCallingChannelWaitMethod()
    {
        $expectedTimeout = 123123123;

        $channelMock = $this->createAmqpChannel();
        $channelMock
            ->expects($this->once())
            ->method('wait')
            ->with($this->anything(), $this->anything(), $expectedTimeout)
        ;

        $sessionStub = $this->createAmqpSessionStub();

        $consumer = new AmqpMessageConsumer($sessionStub, $channelMock, new AmqpQueue('aName'));

        $consumer->receive($expectedTimeout);
    }

    public function testShouldWaitForMessageAndReturnItOnReceive()
    {
        $expectedMessage = new AmqpMessage();
        $expectedInternalMessage = new AMQPLibMessage();
        $expectedInternalMessage->delivery_info['delivery_tag'] = 'theDeliveryTag';
        $expectedInternalMessage->delivery_info['redelivered'] = 'theRedeliveredBool';
        $expectedInternalMessage->delivery_info['exchange'] = 'theExchange';

        $channelStub = new AMQPChannelStub();
        $channelStub->receivedInternalMessage = $expectedInternalMessage;

        $sessionStub = $this->createAmqpSessionStub();
        $sessionStub
            ->expects($this->once())
            ->method('createMessage')
            ->with(null, [], [])
            ->willReturn($expectedMessage)
        ;

        $consumer = new AmqpMessageConsumer($sessionStub, $channelStub, new AmqpQueue('aName'));

        $actualMessage = $consumer->receive();
        $this->assertSame($expectedMessage, $actualMessage);
        $this->assertSame('theDeliveryTag', $actualMessage->getDeliveryTag());
        $this->assertSame('theRedeliveredBool', $actualMessage->isRedelivered());
        $this->assertSame('theExchange', $actualMessage->getExchange());
    }

    public function testShouldCorrectlyExtractInternalMessageBodyAndPassItMessageFactory()
    {
        $internalMessage = new AMQPLibMessage('theMessageBody');
        $internalMessage->delivery_info['delivery_tag'] = 'aTag';
        $internalMessage->delivery_info['redelivered'] = 'aRedeliveredBool';
        $internalMessage->delivery_info['exchange'] = 'aExchange';

        $channelStub = new AMQPChannelStub();
        $channelStub->receivedInternalMessage = $internalMessage;

        $sessionStub = $this->createAmqpSessionStub();
        $sessionStub
            ->expects($this->once())
            ->method('createMessage')
            ->with('theMessageBody')
            ->willReturn(new AmqpMessage())
        ;

        $consumer = new AmqpMessageConsumer($sessionStub, $channelStub, new AmqpQueue('aName'));

        // guard
        $this->assertNotNull($consumer->receive());
    }

    public function testShouldCorrectlyExtractInternalMessagePropertiesAndPassItMessageFactory()
    {
        $internalMessage = new AMQPLibMessage('theMessageBody');
        $internalMessage->set('application_headers', new AMQPTable(['theProp' => 'thePropVal']));
        $internalMessage->delivery_info['delivery_tag'] = 'aTag';
        $internalMessage->delivery_info['redelivered'] = 'aRedeliveredBool';
        $internalMessage->delivery_info['exchange'] = 'aExchange';

        $channelStub = new AMQPChannelStub();
        $channelStub->receivedInternalMessage = $internalMessage;

        $sessionStub = $this->createAmqpSessionStub();
        $sessionStub
            ->expects($this->once())
            ->method('createMessage')
            ->with($this->anything(), ['theProp' => 'thePropVal'])
            ->willReturn(new AmqpMessage())
        ;

        $consumer = new AmqpMessageConsumer($sessionStub, $channelStub, new AmqpQueue('aName'));

        // guard
        $this->assertNotNull($consumer->receive());
    }

    public function testShouldCorrectlyExtractInternalMessageHeadersAndPassItMessageFactory()
    {
        $internalMessage = new AMQPLibMessage('theMessageBody', ['timestamp' => 123123123]);
        $internalMessage->delivery_info['delivery_tag'] = 'aTag';
        $internalMessage->delivery_info['redelivered'] = 'aRedeliveredBool';
        $internalMessage->delivery_info['exchange'] = 'aExchange';

        $channelStub = new AMQPChannelStub();
        $channelStub->receivedInternalMessage = $internalMessage;

        $sessionStub = $this->createAmqpSessionStub();
        $sessionStub
            ->expects($this->once())
            ->method('createMessage')
            ->with($this->anything(), $this->anything(), ['timestamp' => 123123123])
            ->willReturn(new AmqpMessage())
        ;

        $consumer = new AmqpMessageConsumer($sessionStub, $channelStub, new AmqpQueue('aName'));

        // guard
        $this->assertNotNull($consumer->receive());
    }

    public function testShouldReturnNullIfWaitTimeOutedWithoutReceivingInternalMessage()
    {
        $timeout = 5;

        $channelMock = $this->createAmqpChannel();
        $channelMock
            ->expects($this->once())
            ->method('wait')
            ->with($this->anything(), $this->anything(), $timeout)
            ->willReturn(null)
        ;

        $sessionStub = $this->createAmqpSessionStub();

        $consumer = new AmqpMessageConsumer($sessionStub, $channelMock, new AmqpQueue('aName'));

        $this->assertNull($consumer->receive($timeout));
    }

    public function testShouldReturnNullIfWaitTimeOutedWithException()
    {
        $timeout = 5;

        $channelMock = $this->createAmqpChannel();
        $channelMock
            ->expects($this->once())
            ->method('wait')
            ->with($this->anything(), $this->anything(), $timeout)
            ->willThrowException(new AMQPTimeoutException())
        ;

        $sessionStub = $this->createAmqpSessionStub();

        $consumer = new AmqpMessageConsumer($sessionStub, $channelMock, new AmqpQueue('aName'));

        $this->assertNull($consumer->receive($timeout));
    }

    public function testThrowIfGivenDestinationInvalidOnAcknowledge()
    {
        $consumer = new AmqpMessageConsumer($this->createAmqpSessionStub(), $this->createAmqpChannel(), new AmqpQueue('aName'));

        $invalidMessage = $this->createMessage();

        $this->setExpectedException(
            InvalidMessageException::class,
            'The message must be an instance of Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpMessage'
        );

        $consumer->acknowledge($invalidMessage);
    }

    public function testShouldAcknowledgeMessage()
    {
        $expectedDeliveryTag = 'theDeliveryTag';

        $channelMock = $this->createAmqpChannel();
        $channelMock
            ->expects($this->once())
            ->method('basic_ack')
            ->with($expectedDeliveryTag)
        ;

        $sessionStub = $this->createAmqpSessionStub();

        $consumer = new AmqpMessageConsumer($sessionStub, $channelMock, new AmqpQueue('aName'));

        $message = new AmqpMessage();
        $message->setDeliveryTag($expectedDeliveryTag);

        $consumer->acknowledge($message);
    }

    public function testThrowIfGivenDestinationInvalidOnReject()
    {
        $consumer = new AmqpMessageConsumer($this->createAmqpSessionStub(), $this->createAmqpChannel(), new AmqpQueue('aName'));

        $invalidMessage = $this->createMessage();

        $this->setExpectedException(
            InvalidMessageException::class,
            'The message must be an instance of Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpMessage'
        );

        $consumer->reject($invalidMessage);
    }

    public function testShouldRejectMessageWithoutRequeueByDefault()
    {
        $expectedDeliveryTag = 'theDeliveryTag';

        $channelMock = $this->createAmqpChannel();
        $channelMock
            ->expects($this->once())
            ->method('basic_reject')
            ->with($expectedDeliveryTag, $requeue = false)
        ;

        $sessionStub = $this->createAmqpSessionStub();

        $consumer = new AmqpMessageConsumer($sessionStub, $channelMock, new AmqpQueue('aName'));

        $message = new AmqpMessage();
        $message->setDeliveryTag($expectedDeliveryTag);

        $consumer->reject($message);
    }

    public function testShouldRejectMessageWithRequeuePassedExplicitly()
    {
        $expectedDeliveryTag = 'theDeliveryTag';

        $channelMock = $this->createAmqpChannel();
        $channelMock
            ->expects($this->once())
            ->method('basic_reject')
            ->with($expectedDeliveryTag, $requeue = true)
        ;

        $sessionStub = $this->createAmqpSessionStub();

        $consumer = new AmqpMessageConsumer($sessionStub, $channelMock, new AmqpQueue('aName'));

        $message = new AmqpMessage();
        $message->setDeliveryTag($expectedDeliveryTag);

        $consumer->reject($message, true);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageInterface
     */
    protected function createMessage()
    {
        return $this->getMock(MessageInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AMQPChannel
     */
    protected function createAmqpChannel()
    {
        return $this->getMock(AMQPChannel::class, [], [], '', false);
    }

    /**
     * @return AmqpSession|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createAmqpSessionStub()
    {
        return $this->getMock(AmqpSession::class, [], [], '', false);
    }
}

class AMQPChannelStub extends AMQPChannel
{
    /**
     * @var AMQPLibMessage
     */
    public $receivedInternalMessage;

    protected $callback;

    public function __construct()
    {
    }

    public function basic_qos($prefetch_size, $prefetch_count, $a_global)
    {
    }

    public function wait($allowed_methods = null, $non_blocking = false, $timeout = 0)
    {
        call_user_func($this->callback, $this->receivedInternalMessage);
    }

    public function basic_consume(
        $queue = '',
        $gconsumer_tag = '',
        $no_local = false,
        $no_ack = false,
        $exclusive = false,
        $nowait = false,
        $callback = null,
        $ticket = null,
        $arguments = array()
    ) {
        $this->callback = $callback;
    }

    public function basic_get($queue = '', $no_ack = false, $ticket = null)
    {
        return $this->receivedInternalMessage;
    }

    public function basic_cancel($consumer_tag, $nowait = false, $noreturn = false)
    {
    }
}

// @codingStandardsIgnoreEnd
