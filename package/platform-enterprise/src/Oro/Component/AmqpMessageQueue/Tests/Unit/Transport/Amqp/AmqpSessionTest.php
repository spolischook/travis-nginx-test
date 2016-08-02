<?php
namespace Oro\Component\AmqpMessageQueue\Tests\Unit\Transport\Amqp;

use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpMessage;
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpMessageConsumer;
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpMessageProducer;
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpQueue;
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpSession;
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpTopic;
use Oro\Component\MessageQueue\Transport\DestinationInterface;
use Oro\Component\MessageQueue\Transport\Exception\InvalidDestinationException;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\ClassExtensionTrait;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Wire\AMQPTable;

class AmqpSessionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementSessionInterface()
    {
        $this->assertClassImplements(SessionInterface::class, AmqpSession::class);
    }

    public function testCouldBeConstructedWithChannelAsArgument()
    {
        new AmqpSession($this->createAmqpConnection());
    }

    public function testShouldAllowCreateMessageWithoutAnyArguments()
    {
        $session = new AmqpSession($this->createAmqpConnection());

        $message = $session->createMessage();

        $this->assertInstanceOf(AmqpMessage::class, $message);

        $this->assertSame(null, $message->getBody());
        $this->assertSame([], $message->getHeaders());
        $this->assertSame([], $message->getProperties());
    }

    public function testShouldAllowCreateCustomMessage()
    {
        $session = new AmqpSession($this->createAmqpConnection());

        $message = $session->createMessage('theBody', ['theProperty'], ['theHeader']);

        $this->assertInstanceOf(AmqpMessage::class, $message);

        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['theProperty'], $message->getProperties());
        $this->assertSame(['theHeader'], $message->getHeaders());
    }

    public function testShouldAllowCreateQueue()
    {
        $session = new AmqpSession($this->createAmqpConnection());

        $queue = $session->createQueue('aName');
        
        $this->assertInstanceOf(AmqpQueue::class, $queue);
    }

    public function testShouldAllowCreateTopic()
    {
        $session = new AmqpSession($this->createAmqpConnection());

        $topic = $session->createTopic('aName');

        $this->assertInstanceOf(AmqpTopic::class, $topic);
    }

    public function testShouldAllowCreateConsumerForGivenQueue()
    {
        $connection = $this->createAmqpConnection();
        $connection
            ->expects($this->once())
            ->method('channel')
            ->will($this->returnValue($this->createAmqpChannel()))
        ;

        $session = new AmqpSession($connection);

        $queue = new AmqpQueue('aName');

        $consumer = $session->createConsumer($queue);

        $this->assertInstanceOf(AmqpMessageConsumer::class, $consumer);
    }

    public function testThrowIfGivenDestinationInvalidOnCreateConsumer()
    {
        $session = new AmqpSession($this->createAmqpConnection());

        $invalidDestination = $this->createDestination();

        $this->setExpectedException(InvalidDestinationException::class);

        $session->createConsumer($invalidDestination);
    }

    public function testShouldAllowCreateProducer()
    {
        $connection = $this->createAmqpConnection();
        $connection
            ->expects($this->once())
            ->method('channel')
            ->will($this->returnValue($this->createAmqpChannel()))
        ;

        $session = new AmqpSession($connection);

        $producer = $session->createProducer();

        $this->assertInstanceOf(AmqpMessageProducer::class, $producer);
    }

    public function testShouldCreateNextProducerWithSameInstanceOfChannel()
    {
        $connection = $this->createAmqpConnection();
        $connection
            ->expects($this->once())
            ->method('channel')
            ->will($this->returnValue($this->createAmqpChannel()))
        ;

        $session = new AmqpSession($connection);

        $producer1 = $session->createProducer();
        $producer2 = $session->createProducer();

        $this->assertNotSame($producer1, $producer2);
    }

    public function testShouldAllowDeclareQueue()
    {
        $queue = new AmqpQueue('theQueueName');

        $channelMock = $this->createAmqpChannel();
        $channelMock->expects($this->once())
            ->method('queue_declare')
            ->with('theQueueName')
        ;

        $connection = $this->createAmqpConnection();
        $connection
            ->expects($this->once())
            ->method('channel')
            ->will($this->returnValue($channelMock))
        ;

        $session = new AmqpSession($connection);
        $session->declareQueue($queue);
    }

    public function testShouldCorrectlyPassQueueOptionsToQueueDeclareMethod()
    {
        $queue = new AmqpQueue('aTopicName');
        $queue->setDurable('theDurableBool');
        $queue->setPassive('thePassiveBool');
        $queue->setExclusive('theExclusiveBool');
        $queue->setAutoDelete('theAutoDeleteBool');
        $queue->setNoWait('theNoWaitBool');
        $queue->setTable(['theKey' => 'theVal']);

        $channelMock = $this->createAmqpChannel();
        $channelMock->expects($this->once())
            ->method('queue_declare')
            ->with(
                $this->anything(),
                'thePassiveBool',
                'theDurableBool',
                'theExclusiveBool',
                'theAutoDeleteBool',
                'theNoWaitBool',
                new AMQPTable(['theKey' => 'theVal'])
            )
        ;

        $connection = $this->createAmqpConnection();
        $connection
            ->expects($this->once())
            ->method('channel')
            ->will($this->returnValue($channelMock))
        ;

        $session = new AmqpSession($connection);
        $session->declareQueue($queue);
    }

    public function testThrowIfGivenDestinationInvalidOnDeclareQueue()
    {
        $session = new AmqpSession($this->createAmqpConnection());

        $invalidDestination = $this->createDestination();

        $this->setExpectedException(InvalidDestinationException::class);

        $session->declareQueue($invalidDestination);
    }

    public function testShouldAllowDeclareTopic()
    {
        $topic = new AmqpTopic('theTopicName');

        $channelMock = $this->createAmqpChannel();
        $channelMock->expects($this->once())
            ->method('exchange_declare')
            ->with('theTopicName')
        ;

        $connection = $this->createAmqpConnection();
        $connection
            ->expects($this->once())
            ->method('channel')
            ->will($this->returnValue($channelMock))
        ;

        $session = new AmqpSession($connection);
        $session->declareTopic($topic);
    }

    public function testShouldCorrectlyPassTopicOptionsToExchangeDeclareMethod()
    {
        $topic = new AmqpTopic('aTopicName');
        $topic->setType('theTopicType');
        $topic->setDurable('theDurableBool');
        $topic->setPassive('thePassiveBool');
        $topic->setNoWait('theNoWaitBool');
        $topic->setTable(['theKey' => 'theVal']);

        $channelMock = $this->createAmqpChannel();
        $channelMock->expects($this->once())
            ->method('exchange_declare')
            ->with(
                $this->anything(),
                'theTopicType',
                'thePassiveBool',
                'theDurableBool',
                false,
                false,
                'theNoWaitBool',
                new AMQPTable(['theKey' => 'theVal'])
            )
        ;

        $connection = $this->createAmqpConnection();
        $connection
            ->expects($this->once())
            ->method('channel')
            ->will($this->returnValue($channelMock))
        ;

        $session = new AmqpSession($connection);
        $session->declareTopic($topic);
    }

    public function testThrowIfGivenDestinationInvalidOnDeclareTopic()
    {
        $session = new AmqpSession($this->createAmqpConnection());

        $invalidDestination = $this->createDestination();

        $this->setExpectedException(InvalidDestinationException::class);

        $session->declareTopic($invalidDestination);
    }

    public function testShouldAllowDeclareBindBetweenSourceAndTargetDestinations()
    {
        $topic = new AmqpTopic('theTopicName');
        $queue = new AmqpQueue('theQueueName');

        $channelMock = $this->createAmqpChannel();
        $channelMock->expects($this->once())
            ->method('queue_bind')
            ->with('theQueueName', 'theTopicName')
        ;

        $connection = $this->createAmqpConnection();
        $connection
            ->expects($this->once())
            ->method('channel')
            ->will($this->returnValue($channelMock))
        ;

        $session = new AmqpSession($connection);
        $session->declareBind($topic, $queue);
    }

    public function testThrowIfGivenSourceDestinationInvalidOnDeclareBind()
    {
        $session = new AmqpSession($this->createAmqpConnection());

        $invalidDestination = $this->createDestination();

        $this->setExpectedException(InvalidDestinationException::class);

        $session->declareBind($invalidDestination, new AmqpQueue('aName'));
    }

    public function testThrowIfGivenTargetDestinationInvalidOnDeclareBind()
    {
        $session = new AmqpSession($this->createAmqpConnection());

        $invalidDestination = $this->createDestination();

        $this->setExpectedException(InvalidDestinationException::class);

        $session->declareBind(new AmqpTopic('aName'), $invalidDestination);
    }
    
    public function testShouldCallChannelCloseMethodOnCloseForAllChannels()
    {
        $producerChannel = $this->createAmqpChannel();
        $producerChannel
            ->expects($this->once())
            ->method('close')
        ;

        $consumerChannel = $this->createAmqpChannel();
        $producerChannel
            ->expects($this->once())
            ->method('close')
        ;

        $connection = $this->createAmqpConnection();
        $connection
            ->expects($this->at(0))
            ->method('channel')
            ->will($this->returnValue($producerChannel))
        ;
        $connection
            ->expects($this->at(1))
            ->method('channel')
            ->will($this->returnValue($consumerChannel))
        ;

        $session = new AmqpSession($connection);
        $session->createProducer();
        $session->createConsumer($session->createQueue('queue'));
        
        $session->close();
    }

    public function testShouldNotCallChannelCloseMethodIfProducerChannelWasNotCreated()
    {
        $connection = $this->createAmqpConnection();
        $connection
            ->expects($this->never())
            ->method('channel')
        ;

        $session = new AmqpSession($connection);
        $session->close();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AbstractConnection
     */
    protected function createAmqpConnection()
    {
        return $this->getMock(AbstractConnection::class, [], [], '', false);
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
