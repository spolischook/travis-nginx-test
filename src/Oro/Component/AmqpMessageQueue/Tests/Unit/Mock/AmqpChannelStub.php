<?php
namespace Oro\Component\AmqpMessageQueue\Tests\Unit\Mock;

use PhpAmqpLib\Message\AMQPMessage as AMQPLibMessage;
use PhpAmqpLib\Channel\AMQPChannel;

// @codingStandardsIgnoreStart

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