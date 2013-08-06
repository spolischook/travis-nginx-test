<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\DependencyInjection;

use OroPro\Bundle\EwsBundle\DependencyInjection\OroEwsExtension;

class OroEwsExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testLoad()
    {
        $extension = new OroEwsExtension();
        $configs = array(
            array('wsdl_endpoint' => '@OroProfessionalEwsBundle/test')
        );
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $bundle = $this->getMock('OroPro\Bundle\EwsBundle\OroProfessionalEwsBundle');

        $bundle->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue('PATH'));

        $container->expects($this->any())
            ->method('getParameter')
            ->with('kernel.bundles')
            ->will($this->returnValue(array('OroProfessionalEwsBundle' => $bundle)));

        $isCalled = false;
        $wsdlEndpointPath = '';

        $container->expects($this->any())
            ->method('setParameter')
            ->will(
                $this->returnCallback(
                    function ($name, $value) use (&$isCalled, &$wsdlEndpointPath) {
                        if ($name == 'oro_ews.wsdl_endpoint' && is_string($value)) {
                            $isCalled = true;
                            $wsdlEndpointPath = $value;
                        }
                    }
                )
            );

        $extension->load($configs, $container);

        $this->assertTrue($isCalled);
        $this->assertEquals('PATH'.DIRECTORY_SEPARATOR.'test', $wsdlEndpointPath);
    }
}
