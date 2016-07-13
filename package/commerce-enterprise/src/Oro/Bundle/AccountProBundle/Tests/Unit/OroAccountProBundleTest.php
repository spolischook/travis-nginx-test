<?php

namespace Oro\Bundle\AccountProBundle\Tests\Unit;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\AccountProBundle\DependencyInjection\OroAccountProExtension;
use Oro\Bundle\AccountProBundle\OroAccountProBundle;

class OroB2BProAccountBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testGetContainerExtension()
    {
        $bundle = new OroAccountProBundle();
        
        $this->assertInstanceOf(OroAccountProExtension::class, $bundle->getContainerExtension());
    }
}
