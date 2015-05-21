<?php

namespace OroCRMPro\Bundle\OutlookBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;

use OroCRMPro\Bundle\OutlookBundle\DependencyInjection\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $config    = new Configuration();
        $processor = new Processor();

        $actualConfiguration = $processor->processConfiguration($config, []);

        $this->assertInternalType('array', $actualConfiguration);
        $this->assertTrue(isset($actualConfiguration['settings']));
        $this->assertNotCount(0, $actualConfiguration['settings']);
    }
}
