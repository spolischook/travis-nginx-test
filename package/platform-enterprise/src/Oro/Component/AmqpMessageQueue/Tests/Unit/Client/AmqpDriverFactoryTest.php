<?php
namespace Oro\Component\AmqpMessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DriverFactory;
use Oro\Component\AmqpMessageQueue\Client\AmqpDriver;
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpConnection;
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpSession;

class AmqpDriverFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldCreateAmpqSessionInstance()
    {
        $config = new Config('', '', '', '');

        $connection = $this->createAmqpConnectionMock();
        $connection
            ->expects($this->once())
            ->method('createSession')
            ->will($this->returnValue($this->createAmqpSessionMock()))
        ;

        $driver = DriverFactory::create($connection, $config);

        $this->assertInstanceOf(AmqpDriver::class, $driver);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AmqpSession
     */
    protected function createAmqpSessionMock()
    {
        return $this->getMock(AmqpSession::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AmqpConnection
     */
    protected function createAmqpConnectionMock()
    {
        return $this->getMock(AmqpConnection::class, [], [], '', false);
    }
}
