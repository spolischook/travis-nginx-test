<?php
namespace Oro\Component\MessageQueue\Transport;

interface MessageConsumerInterface
{
    /**
     * @return QueueInterface
     */
    public function getQueue();

    /**
     * @param int $timeout
     *
     * @return MessageInterface|null
     */
    public function receive($timeout = 0);

    /**
     * @return MessageInterface|null
     */
    public function receiveNoWait();
    
    /**
     * @param MessageInterface $message
     *
     * @return void
     */
    public function acknowledge(MessageInterface $message);

    /**
     * @param MessageInterface $message
     * @param bool $requeue
     *
     * @return void
     */
    public function reject(MessageInterface $message, $requeue = false);
}
