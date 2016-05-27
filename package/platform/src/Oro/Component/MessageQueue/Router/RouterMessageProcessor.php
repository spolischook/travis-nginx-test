<?php
namespace Oro\Component\MessageQueue\Router;

use Oro\Component\MessageQueue\Consumption\MessageProcessor;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\Session as TransportSession;
use Oro\Component\MessageQueue\ZeroConfig\TopicSubscriber;

class RouterMessageProcessor implements MessageProcessor, TopicSubscriber
{
    /**
     * @var Router
     */
    private $router;

    /**
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Message $message, TransportSession $session)
    {
        $producer = $session->createProducer();
        foreach ($this->router->route($message) as $recipient) {
            $producer->send($recipient->getDestination(), $recipient->getMessage());
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [
            '__router__',
        ];
    }
}
