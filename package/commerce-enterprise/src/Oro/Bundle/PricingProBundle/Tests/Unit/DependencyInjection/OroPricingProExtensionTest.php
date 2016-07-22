<?php

namespace Oro\Bundle\PricingProBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\PricingProBundle\DependencyInjection\OroPricingProExtension;

class OroPricingProExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroPricingProExtension
     */
    private $extension;

    /**
     * @var ContainerBuilder
     */
    private $container;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->extension = new OroPricingProExtension();
    }

    public function testGetAlias()
    {
        $this->assertSame(OroPricingProExtension::ALIAS, $this->extension->getAlias());
    }

    public function testLoad()
    {
        $this->extension->load([], $this->container);
    }
}
