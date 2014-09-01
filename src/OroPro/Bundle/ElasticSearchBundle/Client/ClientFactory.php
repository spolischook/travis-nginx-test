<?php

namespace OroPro\Bundle\ElasticSearchBundle\Client;

use Elasticsearch\Client;

class ClientFactory
{
    /**
     * @param array $configuration
     * @return Client
     */
    public function create(array $configuration = array())
    {
        return new Client($configuration);
    }
}
