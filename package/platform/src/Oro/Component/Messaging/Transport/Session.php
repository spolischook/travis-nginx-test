<?php
namespace Oro\Component\Messaging\Transport;

interface Session
{
    /**
     * @param string $body
     * @param array  $properties
     * @param array  $headers
     *
     * @return Message
     */
    public function createMessage($body = null, array $properties = [], array $headers = []);

    /**
     * @param string $name
     *
     * @return Queue
     */
    public function createQueue($name);

    /**
     * @param string $name
     *
     * @return Topic
     */
    public function createTopic($name);

    /**
     * @param Destination $destination
     *
     * @return MessageConsumer
     */
    public function createConsumer(Destination $destination);

    /**
     * @param Destination $destination
     *
     * @return MessageProducer
     */
    public function createProducer(Destination $destination);

    /**
     * @param Destination $destination
     */
    public function declareTopic(Destination $destination);

    /**
     * @param Destination $destination
     */
    public function declareQueue(Destination $destination);

    /**
     * @param Destination $source
     * @param Destination $target
     */
    public function declareBind(Destination $source, Destination $target);
}
