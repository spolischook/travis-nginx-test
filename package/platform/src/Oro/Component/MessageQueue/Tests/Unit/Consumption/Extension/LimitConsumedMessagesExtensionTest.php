<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class LimitConsumedMessagesExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new LimitConsumedMessagesExtension(12345);
    }

    public function testShouldThrowExceptionIfMessageLimitIsNotInt()
    {
        $this->setExpectedException(
            \InvalidArgumentException::class,
            'Expected message limit is int but got: "double"'
        );

        new LimitConsumedMessagesExtension(0.0);
    }

    public function testOnBeforeReceiveShouldInterruptExecutionIfLimitIsZero()
    {
        $context = $this->createContext();
        $context->getLogger()
            ->expects($this->once())
            ->method('debug')
            ->with('[LimitConsumedMessagesExtension] Message consumption is interrupted since'.
                ' the message limit reached. limit: "0", consumed: "0"')
        ;

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumedMessagesExtension(0);

        // consume 1
        $extension->onBeforeReceive($context);
        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testOnBeforeReceiveShouldInterruptExecutionIfLimitIsLessThatZero()
    {
        $context = $this->createContext();
        $context->getLogger()
            ->expects($this->once())
            ->method('debug')
            ->with('[LimitConsumedMessagesExtension] Message consumption is interrupted since'.
                ' the message limit reached. limit: "-1", consumed: "0"')
        ;

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumedMessagesExtension(-1);

        // consume 1
        $extension->onBeforeReceive($context);
        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testOnPostReceivedShouldInterruptExecutionIfMessageLimitExceeded()
    {
        $context = $this->createContext();
        $context->getLogger()
            ->expects($this->once())
            ->method('debug')
            ->with('[LimitConsumedMessagesExtension] Message consumption is interrupted since'.
                ' the message limit reached. limit: "2", consumed: "2"')
        ;

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumedMessagesExtension(2);

        // consume 1
        $extension->onPostReceived($context);
        $this->assertFalse($context->isExecutionInterrupted());

        // consume 2 and exit
        $extension->onPostReceived($context);
        $this->assertTrue($context->isExecutionInterrupted());
    }

    /**
     * @return Context
     */
    protected function createContext()
    {
        return new Context(
            $this->getMock(SessionInterface::class),
            $this->getMock(MessageConsumerInterface::class),
            $this->getMock(MessageProcessorInterface::class),
            $this->getMock(LoggerInterface::class)
        );
    }
}
