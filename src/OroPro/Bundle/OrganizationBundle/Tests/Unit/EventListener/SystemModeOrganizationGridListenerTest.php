<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;

use OroPro\Bundle\OrganizationBundle\EventListener\SystemModeOrganizationGridListener;
use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;
use OroPro\Bundle\OrganizationBundle\Tests\Unit\Fixture\GlobalOrganization;

class SystemModeOrganizationGridListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var SystemModeOrganizationGridListener */
    protected $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var SystemAccessModeOrganizationProvider */
    protected $organizationProvider;

    public function setUp()
    {
        $this->organizationProvider = new SystemAccessModeOrganizationProvider();
        $this->doctrine             = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade       = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new SystemModeOrganizationGridListener(
            $this->organizationProvider,
            $this->doctrine,
            $this->securityFacade
        );
    }

    /**
     * @dataProvider buildBeforeProvider
     *
     * @param $currentOrgIsGlobal
     * @param $gridParameters
     * @param $isOrganizationSet
     */
    public function testOnBuildBefore($currentOrgIsGlobal, $gridParameters, $isOrganizationSet)
    {
        $datagridConfiguration = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();
        $parameters            = new ParameterBag();
        $parameters->add($gridParameters);
        $datagrid = new Datagrid('test', $datagridConfiguration, $parameters);

        $event = new BuildBefore($datagrid, $datagridConfiguration);

        $currentOrganization = new GlobalOrganization();
        $currentOrganization->setIsGlobal($currentOrgIsGlobal);

        $this->securityFacade->expects($this->once())
            ->method('getOrganization')
            ->willReturn($currentOrganization);

        $organization = new GlobalOrganization();
        $repo         = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->with('OroOrganizationBundle:Organization')
            ->willReturn($repo);

        $repo->expects($this->any())
            ->method('find')
            ->willReturnCallback(
                function ($id) use ($organization) {
                    $organization->setId($id);
                    return $organization;
                }
            );

        $this->listener->onBuildBefore($event);

        if ($isOrganizationSet) {
            $this->assertTrue($this->organizationProvider->getOrganizationId() > 0);
        } else {
            $this->assertFalse($this->organizationProvider->getOrganizationId());
        }
    }

    public function buildBeforeProvider()
    {
        return [
            'Not in system Access org' => [false, [], false],
            'Where is no parameter'    => [true, [], false],
            'Grid with parameter'      => [true, ['_sa_org_id' => 123], true]
        ];
    }
}
