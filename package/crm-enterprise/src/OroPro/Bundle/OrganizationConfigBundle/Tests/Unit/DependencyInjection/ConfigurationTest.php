<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Tests\Unit\DependencyInjection;

use OroPro\Bundle\OrganizationConfigBundle\DependencyInjection\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $builder = $configuration->getConfigTreeBuilder();
        $this->assertInstanceOf('Symfony\Component\Config\Definition\Builder\TreeBuilder', $builder);

        $root = $builder->buildTree();
        $this->assertInstanceOf('Symfony\Component\Config\Definition\ArrayNode', $root);
        $this->assertEquals('oropro_organization_config', $root->getName());
    }
}
