<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Functional\Helper;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class OrganizationProHelperTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testIsGlobalOrganizationExists()
    {
        $helper = $this->getContainer()->get('oropro_organization.helper');
        $this->loadFixtures(
            [
                'OroPro\Bundle\OrganizationBundle\Tests\Functional\Fixture\LoadSystemAccessModeOrganizationData'
            ]
        );
        $this->assertTrue($helper->isGlobalOrganizationExists());
    }

    /**
     * @depends testIsGlobalOrganizationExists
     */
    public function testGetGlobalOrganizationId()
    {
        $helper = $this->getContainer()->get('oropro_organization.helper');
        $this->assertTrue($helper->getGlobalOrganizationId() > 0);
    }

    /**
     * @depends testIsGlobalOrganizationExists
     */
    public function testGetGlobalOrganization()
    {
        $this->assertSame(
            'test system access org',
            $this->getContainer()->get('oropro_organization.helper')->getGlobalOrganization()->getName()
        );
    }
}
