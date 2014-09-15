<?php

namespace OroPro\Bundle\ElasticSearchBundle\Tests\Unit\Client;

use OroPro\Bundle\ElasticSearchBundle\Client\ClientFactory;

class ClientFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $factory = new ClientFactory();
        $configuration = ['hosts' => ['1.2.3.4:5678']];
        $client = $factory->create($configuration);

        $this->assertInstanceOf('Elasticsearch\Client', $client);
        $this->assertAttributeEquals('http://1.2.3.4:5678', 'host', $client->transport->getConnection());
    }
}
