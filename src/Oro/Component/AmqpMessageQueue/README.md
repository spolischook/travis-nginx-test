AmqpMessageQueue Component
==========================

The component incorporates message queue in your application via different transports.
It contains several layers.

The lowest layer is called Transport and provides an abstraction of transport protocol.
The Consumption layer provides tools to consume messages, such as cli command, signal handling, logging, extensions.
It works on top of transport layer.

The Client layer provides ability to start producing\consuming messages with as less as possible configuration.

Minimum Permissions (RabbitMQ)
------------------------------

More about [access control](https://www.rabbitmq.com/access-control.html)

Your credentials must meet next minimum requirments:

* You have access to requested rabbitmq's virtual host (`/` by default).
* You have to have next permissions: `configure`, `write`, `read`. It could be a default value `.*` or a stricter `oro\..*`.

### Queues

If you use only this component you are free to create any queues you want and as many as you need.
If you are using the Client abstraction with this transport
next queues will be created `oro.default` and `oro.default.delayed`.
The first keeps all sent messages and the seconds keeps broken message that have to be delayed and redelivered later.
You can still have more queues by explicitly configuring message processor `destinationName` option.

Usage
-----

This is a complete example of message producing using only a transport layer:

```php
<?php

use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpConnection;

$connection = AmqpConnection::createFromConfig($config = []);

$session = $connection->createSession();

$queue = $session->createQueue('aQueue');
$message = $session->createMessage('Something has happened');

$session->createProducer()->send($queue, $message);

$session->close();
$connection->close();
```

This is a complete example of message consuming using only a transport layer:

```php
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpConnection;

$connection = AmqpConnection::createFromConfig($config = []);

$session = $connection->createSession();

$queue = $session->createQueue('aQueue');
$consumer = $session->createConsumer($queue);

while (true) {
    if ($message = $consumer->receive()) {
        echo $message->getBody();

        $consumer->acknowledge($message);
    }
}

$session->close();
$connection->close();
```

This is a complete example of message consuming using consumption layer:

```php
<?php
use Oro\Component\MessageQueue\Consumption\MessageProcessor;

class FooMessageProcessor implements MessageProcessor
{
    public function process(Message $message, Session $session)
    {
        echo $message->getBody();

        return self::ACK;
    }
}
```

```php
<?php
use Oro\Component\MessageQueue\Consumption\Extensions;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpConnection;

$connection = AmqpConnection::createFromConfig($config = []);

$queueConsumer = new QueueConsumer($connection, new Extensions([]));
$queueConsumer->bind('aQueue', new FooMessageProcessor());

try {
    $queueConsumer->consume();
} finally {
    $queueConsumer->getConnection()->close();
}
```
