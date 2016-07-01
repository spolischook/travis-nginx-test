<?php

namespace OroB2BPro\Bundle\PricingBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use OroB2BPro\Bundle\WarehouseBundle\DependencyInjection\OroB2BProWarehouseExtension;

class OroB2BProWarehouseExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroB2BProPricingExtension
     */
    private $extension;

    /**
     * @var ContainerBuilder
     */
    private $container;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->extension = new OroB2BProWarehouseExtension();
    }

    public function testGetAlias()
    {
        $this->assertSame(OroB2BProWarehouseExtension::ALIAS, $this->extension->getAlias());
    }

    public function testLoad()
    {
        $this->extension->load([], $this->container);
    }
}
