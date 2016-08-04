<?php

namespace Oro\Bundle\AccountProBundle\Tests\Unit;

use Oro\Bundle\AccountProBundle\DependencyInjection\OroAccountProExtension;
use Oro\Bundle\AccountProBundle\OroAccountProBundle;

class OroB2BProAccountBundleTest extends \PHPUnit_Framework_TestCase
{
    /** @var OroAccountProBundle */
    protected $bundle;

    protected function setUp()
    {
        $this->bundle = new OroAccountProBundle();
    }

    public function testGetContainerExtension()
    {
        $this->assertInstanceOf(OroAccountProExtension::class, $this->bundle->getContainerExtension());
    }
}
