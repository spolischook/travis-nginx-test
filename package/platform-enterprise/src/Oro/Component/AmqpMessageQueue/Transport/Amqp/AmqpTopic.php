<?php
namespace Oro\Component\AmqpMessageQueue\Transport\Amqp;

use Oro\Component\MessageQueue\Transport\TopicInterface;

/**
 * @link https://www.rabbitmq.com/amqp-0-9-1-reference.html#class.exchange
 */
class AmqpTopic implements TopicInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * Each exchange belongs to one of a set of exchange types implemented by the server.
     * The exchange types define the functionality of the exchange - i.e. how messages are routed through it.
     * It is not valid or meaningful to attempt to change the type of an existing exchange.
     *
     * @var string
     */
    private $type;

    /**
     * If set, the server will reply with Declare-Ok if the exchange already exists with the same name,
     * and raise an error if not.
     * The client can use this to check whether an exchange exists without modifying the server state.
     * When set, all other method fields except name and no-wait are ignored.
     * A declare with both passive and no-wait has no effect. Arguments are compared for semantic equivalence.
     *
     * @var boolean
     */
    private $passive;

    /**
     * If set when creating a new exchange, the exchange will be marked as durable.
     * Durable exchanges remain active when a server restarts.
     * Non-durable exchanges (transient exchanges) are purged if/when a server restarts.
     *
     * @var boolean
     */
    private $durable;

    /**
     * If set, the server will not respond to the method.
     * The client should not wait for a reply method.
     * If the server could not complete the method it will raise a channel or connection exception.
     *
     * @var boolean
     */
    private $noWait;

    /**
     * Specifies the routing key for the binding.
     * The routing key is used for routing messages depending on the exchange configuration.
     * Not all exchanges use a routing key - refer to the specific exchange documentation.
     *
     * @var string
     */
    private $routingKey;

    /**
     * This flag tells the server how to react if the message cannot be routed to a queue.
     * If this flag is set, the server will return an unroutable message with a Return method.
     * If this flag is zero, the server silently drops the message.
     *
     * @var boolean
     */
    private $mandatory;

    /**
     * This flag tells the server how to react if the message cannot be routed to a queue consumer immediately.
     * If this flag is set, the server will return an undeliverable message with a Return method.
     * If this flag is zero, the server will queue the message, but with no guarantee that it will ever be consumed.
     *
     * @var boolean
     */
    private $immediate;

    /**
     * A set of arguments for the declaration.
     * The syntax and semantics of these arguments depends on the server implementation.
     *
     * @var string[]
     */
    private $table;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->passive = false;
        $this->durable = false;
        $this->noWait = false;

        // Fanout means broadcast message to everyone who is subscribed to it. Everyone will get a copy of the message.
        $this->type = 'fanout';
        $this->routingKey = '';
        $this->mandatory = false;
        $this->immediate = false;
        $this->table = [];
    }

    /**
     * @return string
     */
    public function getTopicName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return boolean
     */
    public function isPassive()
    {
        return $this->passive;
    }

    /**
     * @param boolean $passive
     */
    public function setPassive($passive)
    {
        $this->passive = $passive;
    }

    /**
     * @return boolean
     */
    public function isDurable()
    {
        return $this->durable;
    }

    /**
     * @param boolean $durable
     */
    public function setDurable($durable)
    {
        $this->durable = $durable;
    }

    /**
     * @return boolean
     */
    public function isNoWait()
    {
        return $this->noWait;
    }

    /**
     * @param boolean $noWait
     */
    public function setNoWait($noWait)
    {
        $this->noWait = $noWait;
    }

    /**
     * @return string
     */
    public function getRoutingKey()
    {
        return $this->routingKey;
    }

    /**
     * @param string $routingKey
     */
    public function setRoutingKey($routingKey)
    {
        $this->routingKey = $routingKey;
    }

    /**
     * @return boolean
     */
    public function isMandatory()
    {
        return $this->mandatory;
    }

    /**
     * @param boolean $mandatory
     */
    public function setMandatory($mandatory)
    {
        $this->mandatory = $mandatory;
    }

    /**
     * @return boolean
     */
    public function isImmediate()
    {
        return $this->immediate;
    }

    /**
     * @param boolean $immediate
     */
    public function setImmediate($immediate)
    {
        $this->immediate = $immediate;
    }

    /**
     * @return \string[]
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param \string[] $table
     */
    public function setTable(array $table)
    {
        $this->table = $table;
    }
}
