<?php
namespace Oro\Component\MessageQueue\ZeroConfig;

use Oro\Component\MessageQueue\Transport\Amqp\AmqpConnection;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\MessageQueue\Transport\Null\NullConnection;

class SessionFactory
{
    /**
     * @param ConnectionInterface $connection
     * @param Config     $config
     *
     * @return SessionInterface
     */
    public static function create(ConnectionInterface $connection, Config $config)
    {
        if ($connection instanceof  AmqpConnection) {
            return new AmqpSession($connection->createSession(), $config);
        } elseif ($connection instanceof NullConnection) {
            return new NullSession($connection->createSession(), $config);
        } else {
            throw new \LogicException(sprintf('Unexpected connection instance: "%s"', get_class($connection)));
        }
    }
}
