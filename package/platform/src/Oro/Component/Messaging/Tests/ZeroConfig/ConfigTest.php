<?php
namespace Oro\Component\Messaging\Tests\ZeroConfig;

use Oro\Component\Messaging\ZeroConfig\Config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldReturnRouterMessageProcessorNameSetInConstructor()
    {
        $config = new Config('aPrefix', 'aRouterMessageProcessorName', 'aRouterQueueName', 'aDefaultQueueName');

        $this->assertEquals('aRouterMessageProcessorName', $config->getRouterMessageProcessorName());
    }

    public function testShouldReturnRouterQueueNameSetInConstructor()
    {
        $config = new Config('aPrefix', 'aRouterMessageProcessorName', 'aRouterQueueName', 'aDefaultQueueName');

        $this->assertEquals('aprefix.arouterqueuename', $config->getRouterQueueName());
    }

    public function testShouldReturnDefaultQueueNameSetInConstructor()
    {
        $config = new Config('aPrefix', 'aRouterMessageProcessorName', 'aRouterQueueName', 'aDefaultQueueName');

        $this->assertEquals('aprefix.adefaultqueuename', $config->getDefaultQueueName());
    }
}
