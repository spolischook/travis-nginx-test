<?php

require_once __DIR__.'/vendor/autoload.php';

$date = new \DateTime();
var_dump($date->format('U'));

$connection = new \PhpAmqpLib\Connection\AMQPConnection(
    'localhost',
    '5672',
    'guest',
    'guest'
);

$channel = new \PhpAmqpLib\Channel\AMQPChannel($connection);
$channel->queue_declare('oro.default');
var_dump($channel->getChannelId());

