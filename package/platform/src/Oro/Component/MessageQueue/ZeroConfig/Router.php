<?php
namespace Oro\Component\MessageQueue\ZeroConfig;

use Oro\Component\MessageQueue\Router\RecipientListRouterInterface as RuterInterface;
use Oro\Component\MessageQueue\Router\Recipient;
use Oro\Component\MessageQueue\Transport\MessageInterface;

class Router implements RuterInterface
{
    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var array
     */
    protected $routes;

    /**
     * @param SessionInterface $session
     * @param array   $routes
     */
    public function __construct(SessionInterface $session, array $routes = [])
    {
        $this->session = $session;
        $this->routes = $routes;
    }

    /**
     * @param string $topicName
     * @param string $processorName
     * @param string $queueName
     */
    public function addRoute($topicName, $processorName, $queueName = null)
    {
        if (empty($topicName)) {
            throw new \InvalidArgumentException('The topic name must not be empty');
        }

        if (empty($processorName)) {
            throw new \InvalidArgumentException('The processor name must not be empty');
        }

        if (false == array_key_exists($topicName, $this->routes)) {
            $this->routes[$topicName] = [];
        }

        $this->routes[$topicName][] = [$processorName, $queueName];
    }

    /**
     * @internal
     *
     * @param string $topicName
     *
     * @return array
     */
    public function getTopicSubscribers($topicName)
    {
        return array_key_exists($topicName, $this->routes) ? $this->routes[$topicName] : [];
    }

    /**
     * {@inheritdoc}
     */
    public function route(MessageInterface $message)
    {
        $topicName = $message->getProperty(Config::PARAMETER_TOPIC_NAME);
        if (false == $topicName) {
            throw new \LogicException(sprintf(
                'Got message without required parameter: "%s"',
                Config::PARAMETER_TOPIC_NAME
            ));
        }

        // TODO: what to do with such messages? silently drop?
        if (array_key_exists($topicName, $this->routes)) {
            foreach ($this->routes[$topicName] as $route) {
                $recipient = $this->createRecipient(
                    $message,
                    $route[0],
                    $route[1] ?: $this->session->getConfig()->getDefaultQueueName()
                );

                yield $recipient;
            }
        }
    }

    /**
     * @param MessageInterface $message
     * @param string $processorName
     * @param string $queueName
     *
     * @return Recipient
     */
    protected function createRecipient(MessageInterface $message, $processorName, $queueName)
    {
        $properties = $message->getProperties();
        $properties[Config::PARAMETER_PROCESSOR_NAME] = $processorName;
        $properties[Config::PARAMETER_QUEUE_NAME] = $queueName;

        $newMessage = $this->session->createMessage();
        $newMessage->setProperties($properties);
        $newMessage->setHeaders($message->getHeaders());
        $newMessage->setBody($message->getBody());

        $queue = $this->session->createQueue($queueName);

        return new Recipient($queue, $newMessage);
    }
}
