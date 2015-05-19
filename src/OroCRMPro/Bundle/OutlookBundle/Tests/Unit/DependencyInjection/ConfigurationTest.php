<?php

namespace OroCRMPro\Bundle\OutlookBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Translation\MessageSelector;

use Oro\Bundle\TranslationBundle\Translation\Translator;

use OroCRMPro\Bundle\OutlookBundle\DependencyInjection\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $translator = new Translator($container, new MessageSelector());

        $config    = new Configuration($translator);
        $processor = new Processor();

        $actualConfiguration = $processor->processConfiguration($config, []);

        $this->assertInternalType('array', $actualConfiguration);
        $this->assertTrue(isset($actualConfiguration['settings']));
        $this->assertNotCount(0, $actualConfiguration['settings']);
    }
}
