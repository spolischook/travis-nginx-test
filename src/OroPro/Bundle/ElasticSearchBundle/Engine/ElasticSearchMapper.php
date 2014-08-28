<?php

namespace OroPro\Bundle\ElasticSearchBundle\Engine;

use Oro\Bundle\SearchBundle\Engine\AbstractMapper;

use OroPro\Bundle\ElasticSearchBundle\DependencyInjection\Compiler\ElasticSearchProviderPass;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ElasticSearchMapper extends AbstractMapper
{
    protected $engineParameters;

    public function __construct(ContainerInterface $container, $mappingConfig)
    {
        $this->mappingConfig = $mappingConfig;
        $this->container     = $container;
    }

    public function getIndexConfiguration()
    {
        $parameters = $this->getEngineParameters();
        return isset($parameters['index']) ? $parameters['index'] : array();
    }

    /**
     * @return array
     */
    protected function getEngineParameters()
    {
        if (!$this->engineParameters) {
            $this->engineParameters = $this->container->getParameter(ElasticSearchProviderPass::ENGINE_PARAMETERS_KEY);
        }

        return $this->engineParameters;
    }
}
