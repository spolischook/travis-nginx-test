<?php

namespace OroCRMPro\Bundle\OutlookBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\Config\Resource\FileResource;

use OroCRMPro\Bundle\OutlookBundle\DependencyInjection\OroCRMProOutlookExtension;

class OroCRMProOutlookExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testLoad()
    {
        $extension = new OroCRMProOutlookExtension();
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');

        $container->expects($this->at(0))
            ->method('addResource')
            ->with(new FileResource(realpath(__DIR__ . '/../../../Resources/config/services.yml')));

        $extension->load([], $container);
    }
}
