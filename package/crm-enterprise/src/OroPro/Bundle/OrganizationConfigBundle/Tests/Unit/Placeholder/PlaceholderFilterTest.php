<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Tests\Unit\Placeholder;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use OroPro\Bundle\OrganizationConfigBundle\Placeholder\PlaceholderFilter;

class PlaceholderFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testIsOrganizationPage()
    {
        $filter = new PlaceholderFilter();
        $this->assertTrue($filter->isOrganizationPage(new Organization()));
        $this->assertFalse($filter->isOrganizationPage(new \stdClass()));
    }
}
