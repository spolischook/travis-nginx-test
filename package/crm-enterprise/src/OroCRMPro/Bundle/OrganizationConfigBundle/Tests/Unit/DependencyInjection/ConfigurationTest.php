<?php

namespace OroCRMPro\Bundle\OrganizationConfigBundle\Tests\Unit\DependencyInjection;

use OroCRMPro\Bundle\OrganizationConfigBundle\DependencyInjection\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $builder = $configuration->getConfigTreeBuilder();

        $this->assertInstanceOf('Symfony\Component\Config\Definition\Builder\TreeBuilder', $builder);
    }
}
