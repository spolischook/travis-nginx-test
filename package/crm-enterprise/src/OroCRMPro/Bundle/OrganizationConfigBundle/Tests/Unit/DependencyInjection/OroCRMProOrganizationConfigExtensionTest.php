<?php

namespace OroCRMPro\Bundle\OrganizationConfigBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use OroCRMPro\Bundle\OrganizationConfigBundle\DependencyInjection\OroCRMProOrganizationConfigExtension;

class OroCRMProOrganizationConfigExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var OroCRMProOrganizationConfigExtension */
    private $extension;

    /** @var ContainerBuilder */
    private $container;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->extension = new OroCRMProOrganizationConfigExtension();
    }

    public function testLoad()
    {
        $this->extension->load([], $this->container);
    }
}
