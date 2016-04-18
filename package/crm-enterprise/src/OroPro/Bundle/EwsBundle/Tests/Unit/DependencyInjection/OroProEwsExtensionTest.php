<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\DependencyInjection;

use OroPro\Bundle\EwsBundle\DependencyInjection\OroProEwsExtension;
use OroPro\Bundle\EwsBundle\OroProEwsBundle;

class OroProEwsExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testLoad()
    {
        $extension = new OroProEwsExtension();

        $configs = array(
            array('wsdl_endpoint' => '@OroProEwsBundle/test')
        );
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $container->expects($this->any())
            ->method('getParameter')
            ->with('kernel.bundles')
            ->will($this->returnValue(array('OroProEwsBundle' => 'OroPro\Bundle\EwsBundle\OroProEwsBundle')));

        $isCalled = false;
        $wsdlEndpointPath = '';

        $container->expects($this->any())
            ->method('setParameter')
            ->will(
                $this->returnCallback(
                    function ($name, $value) use (&$isCalled, &$wsdlEndpointPath) {
                        if ($name == 'oro_pro_ews.wsdl_endpoint' && is_string($value)) {
                            $isCalled = true;
                            $wsdlEndpointPath = $value;
                        }
                    }
                )
            );

        $extension->load($configs, $container);

        $this->assertTrue($isCalled);
        $this->assertEquals(
            (new OroProEwsBundle())->getPath() . DIRECTORY_SEPARATOR . 'test',
            $wsdlEndpointPath
        );
    }
}
