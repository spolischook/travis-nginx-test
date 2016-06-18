<?php

namespace OroCRMPro\Bundle\ChannelBundle\Tests\Unit\EventListener;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\EntityTrait;

use OroCRM\Bundle\ChannelBundle\Provider\StateProvider;

use OroPro\Bundle\OrganizationBundle\Event\OrganizationUpdateEvent;

use OroCRMPro\Bundle\ChannelBundle\EventListener\OrganizationListener;

class OrganizationListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var StateProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $stateProvider;

    /** @var  OrganizationListener */
    protected $organizationListener;

    protected function setUp()
    {
        $this->stateProvider = $this->getMockBuilder('OroCRM\Bundle\ChannelBundle\Provider\StateProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->organizationListener = new OrganizationListener($this->stateProvider);
    }

    public function testOnUpdateOrganization()
    {
        $organizationId = 42;
        $this->stateProvider->expects($this->once())
            ->method('clearOrganizationCache')
            ->with($organizationId);

        /** @var Organization $organization */
        $organization = $this->getEntity(
            'Oro\Bundle\OrganizationBundle\Entity\Organization',
            [
                'id' => $organizationId
            ]
        );
        $event = new OrganizationUpdateEvent($organization);
        $this->organizationListener->onUpdateOrganization($event);
    }
}
