<?php

namespace Oro\Bundle\WebsiteConfigProBundle\Tests\Unit\Placeholder;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteConfigProBundle\Placeholder\PlaceholderFilter;

class PlaceholderFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testIsWebsitePageTrue()
    {
        $placeholderFilter = new PlaceholderFilter();
        $this->assertTrue($placeholderFilter->isWebsitePage($this->getMock(Website::class)));
    }

    public function testIsWebsitePageFalse()
    {
        $placeholderFilter = new PlaceholderFilter();
        $this->assertFalse($placeholderFilter->isWebsitePage(new \stdClass()));
    }
}
