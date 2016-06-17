<?php

namespace OroCRMPro\Bundle\ChannelBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use OroCRMPro\Bundle\ChannelBundle\DependencyInjection\OroCRMProChannelExtension;

class OroCRMProChannelExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroCRMProChannelExtension
     */
    private $extension;

    /**
     * @var ContainerBuilder
     */
    private $container;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->extension = new OroCRMProChannelExtension();
    }

    public function testLoad()
    {
        $this->extension->load([], $this->container);
    }
}
