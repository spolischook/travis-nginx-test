<?php
namespace Oro\Component\MessageQueue\Transport\Amqp;

use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPLazyConnection;

class AmqpConnection implements ConnectionInterface
{
    /**
     * @var AbstractConnection
     */
    private $connection;

    /**
     * @param AbstractConnection $connection
     */
    public function __construct(AbstractConnection $connection)
    {
        if (false == defined('AMQP_WITHOUT_SIGNALS')) {
            define('AMQP_WITHOUT_SIGNALS', false);
        }
        if (true == AMQP_WITHOUT_SIGNALS) {
            throw new \LogicException('The AMQP_WITHOUT_SIGNALS must be set to false.');
        }

        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpSession
     */
    public function createSession()
    {
        return new AmqpSession($this->connection);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->connection->close();
    }

    /**
     * @param array $config
     *
     * @return static
     */
    public static function createFromConfig(array $config)
    {
        $config = array_replace([
            'host' => 'localhost',
            'port' => 5672,
            'user' => null,
            'password' => null,
            'vhost' => '/',
        ], $config);

        return new static(new AMQPLazyConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password'],
            $config['vhost']
        ));
    }
}
