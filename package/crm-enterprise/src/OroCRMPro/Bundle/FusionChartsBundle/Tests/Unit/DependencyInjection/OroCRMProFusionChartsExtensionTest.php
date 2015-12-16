<?php

namespace OroCRMPro\Bundle\FusionChartsBundle\Tests\Unit\DependencyInjection;

use OroCRMPro\Bundle\FusionChartsBundle\DependencyInjection\OroCRMProFusionChartsExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroCRMProFusionChartsExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroCRMProFusionChartsExtension
     */
    private $extension;

    /**
     * @var ContainerBuilder
     */
    private $container;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->extension = new OroCRMProFusionChartsExtension();
    }

    public function testLoad()
    {
        $this->extension->load(array(), $this->container);
    }
}
