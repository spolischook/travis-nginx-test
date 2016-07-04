<?php

namespace OroB2BPro\Bundle\WebsiteBundle\Tests\Functional\Provider;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class WebsiteProviderTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadWebsiteData::class ]);
    }

    public function testGetWebsites()
    {
        $websites = $this->getContainer()->get('orob2b_website.website.provider')->getWebsites();
        $this->assertCount(4, $websites);
    }
}
