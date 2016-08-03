<?php
namespace Oro\Component\AmqpMessageQueue\Transport\Amqp;

use Oro\Component\MessageQueue\Transport\QueueInterface;

/**
 * @link https://www.rabbitmq.com/amqp-0-9-1-reference.html#class.queue
 */
class AmqpQueue implements QueueInterface
{
    /**
     * @var string
     */
    private $name;

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
     * Exclusive queues may only be accessed by the current connection, and are deleted when that connection closes.
     * Passive declaration of an exclusive queue by other connections are not allowed.
     *
     * @var boolean
     */
    private $exclusive;

    /**
     * If set, the exchange is deleted when all queues have finished using it.
     *
     * @var boolean
     */
    private $autoDelete;

    /**
     * If set, the server will not respond to the method.
     * The client should not wait for a reply method.
     * If the server could not complete the method it will raise a channel or connection exception.
     *
     * @var boolean
     */
    private $noWait;

    /**
     * Specifies the identifier for the consumer.
     * The consumer tag is local to a channel, so two clients can use the same consumer tags.
     * If this field is empty the server will generate a unique tag.
     *
     * @var string
     */
    private $consumerTag;

    /**
     * If the no-local field is set the server will not send messages to the connection that published them.
     *
     * @var boolean
     */
    private $noLocal;

    /**
     * If this field is set the server does not expect acknowledgements for messages.
     * That is, when a message is delivered to the client
     * the server assumes the delivery will succeed and immediately dequeues it.
     * This functionality may increase performance but at the cost of reliability.
     * Messages can get lost if a client dies before they are delivered to the application.
     *
     * @var boolean
     */
    private $noAck;

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
        $this->durable = true;
        $this->exclusive = false;
        $this->autoDelete = false;
        $this->noWait = false;

        $this->consumerTag = '';
        $this->noLocal = false;
        $this->noAck = false;
        $this->table = [];
    }

    /**
     * @return string
     */
    public function getQueueName()
    {
        return $this->name;
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
    public function isExclusive()
    {
        return $this->exclusive;
    }

    /**
     * @param boolean $exclusive
     */
    public function setExclusive($exclusive)
    {
        $this->exclusive = $exclusive;
    }

    /**
     * @return boolean
     */
    public function isAutoDelete()
    {
        return $this->autoDelete;
    }

    /**
     * @param boolean $autoDelete
     */
    public function setAutoDelete($autoDelete)
    {
        $this->autoDelete = $autoDelete;
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
    public function getConsumerTag()
    {
        return $this->consumerTag;
    }

    /**
     * @param string $consumerTag
     */
    public function setConsumerTag($consumerTag)
    {
        $this->consumerTag = $consumerTag;
    }

    /**
     * @return boolean
     */
    public function isNoLocal()
    {
        return $this->noLocal;
    }

    /**
     * @param boolean $noLocal
     */
    public function setNoLocal($noLocal)
    {
        $this->noLocal = $noLocal;
    }

    /**
     * @return boolean
     */
    public function isNoAck()
    {
        return $this->noAck;
    }

    /**
     * @param boolean $noAck
     */
    public function setNoAck($noAck)
    {
        $this->noAck = $noAck;
    }

    /**
     * @return string[]
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param string[] $table
     */
    public function setTable(array $table)
    {
        $this->table = $table;
    }
}
