<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Provider;

use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;
use OroPro\Bundle\OrganizationBundle\Tests\Unit\Fixture\GlobalOrganization;

class SystemAccessModeOrganizationProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var SystemAccessModeOrganizationProvider */
    protected $provider;

    public function testOrganization()
    {
        $this->provider = new SystemAccessModeOrganizationProvider();
        $this->assertNull($this->provider->getOrganization());
        $this->assertFalse($this->provider->getOrganizationId());
        $organization = new GlobalOrganization();
        $organization->setId(654);
        $this->provider->setOrganization($organization);
        $this->assertSame($organization, $this->provider->getOrganization());
        $this->assertEquals(654, $this->provider->getOrganizationId());
    }
}
