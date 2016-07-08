<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Transport\Amqp;

use Oro\Component\MessageQueue\Transport\Amqp\AmqpConnection;
use Oro\Component\MessageQueue\Transport\Amqp\AmqpSession;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\Testing\ClassExtensionTrait;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;

class AmqpConnectionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectionInterface()
    {
        $this->assertClassImplements(ConnectionInterface::class, AmqpConnection::class);
    }

    public function testCouldBeConstructedWithLibAmqpConnection()
    {
        new AmqpConnection($this->createAMQPLibConnection());
    }

    public function testShouldAllowCreateSession()
    {
        $libConnection = $this->createAMQPLibConnection();

        $connection = new AmqpConnection($libConnection);

        $session = $connection->createSession();

        $this->assertInstanceOf(AmqpSession::class, $session);
    }

    public function testShouldCallLibConnectionCloseMethodOnClose()
    {
        $libConnection = $this->createAMQPLibConnection();
        $libConnection
            ->expects($this->once())
            ->method('close')
        ;

        $connection = new AmqpConnection($libConnection);

        $connection->close();
    }

    public function testShouldCreateInstanceFromConfig()
    {
        $this->assertInstanceOf(AmqpConnection::class, AmqpConnection::createFromConfig([]));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AbstractConnection
     */
    protected function createAMQPLibConnection()
    {
        return $this->getMock(AbstractConnection::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AMQPChannel
     */
    protected function createAMQPLibChannel()
    {
        return $this->getMock(AMQPChannel::class, [], [], '', false);
    }
}
