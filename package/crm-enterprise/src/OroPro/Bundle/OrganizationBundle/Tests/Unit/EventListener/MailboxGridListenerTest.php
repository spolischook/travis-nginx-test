<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;

use OroPro\Bundle\OrganizationBundle\EventListener\MailboxGridListener;
use OroPro\Bundle\OrganizationBundle\Tests\Unit\Fixture\GlobalOrganization;

class MailboxGridListenerTest extends \PHPUnit_Framework_TestCase
{
    protected $registry;
    protected $aclHelper;
    protected $securityFacade;

    protected $mailboxGridListener;

    public function setUp()
    {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->any())
            ->method('select')
            ->will($this->returnSelf());

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qb));

        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($em));

        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getArrayResult'])
            ->getMockForAbstractClass();
        $query->expects($this->any())
            ->method('getArrayResult')
            ->will($this->returnValue([]));

        $this->aclHelper->expects($this->any())
            ->method('apply')
            ->will($this->returnValue($query));

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mailboxGridListener = new MailboxGridListener(
            $this->registry,
            $this->aclHelper
        );
        $this->mailboxGridListener->setSecurityFacade($this->securityFacade);
    }

    /**
     * @dataProvider onBuildAfterShouldNotCallParentDataProvider
     */
    public function testOnBuildAfterShouldNotCallParent(array $config, GlobalOrganization $organization = null)
    {
        $this->securityFacade->expects($this->any())
            ->method('getOrganization')
            ->will($this->returnValue($organization));

        $datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        
        $datasource->expects($this->never())
            ->method('getQueryBuilder');

        $datagrid = new Datagrid(
            'name',
            DatagridConfiguration::create($config),
            new ParameterBag()
        );
        $datagrid->setDatasource($datasource);

        $event = new BuildAfter($datagrid);
        $this->mailboxGridListener->onBuildAfter($event);
    }

    public function onBuildAfterShouldNotCallParentDataProvider()
    {
        $globalOrganization = new GlobalOrganization();

        return [
            [
                [
                    'properties' => [
                        'update_link' => [
                            'direct_params' => [
                                'redirectData' => [
                                    'route' => 'oro_config_configuration_system',
                                ],
                            ],
                        ],
                    ],
                ],
                $globalOrganization,
            ]
        ];
    }

    /**
     * @dataProvider onBuildAfterShouldCallParentDataProvider
     */
    public function testOnBuildAfterShouldCallParent(array $config, GlobalOrganization $organization = null)
    {
        $this->securityFacade->expects($this->any())
            ->method('getOrganization')
            ->will($this->returnValue($organization));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($qb));

        $datagrid = new Datagrid(
            'name',
            DatagridConfiguration::create($config),
            new ParameterBag()
        );
        $datagrid->setDatasource($datasource);

        $event = new BuildAfter($datagrid);
        $this->mailboxGridListener->onBuildAfter($event);
    }

    public function onBuildAfterShouldCallParentDataProvider()
    {
        $organization = new GlobalOrganization();
        $organization->setIsGlobal(false);

        return [
            [
                [
                    'properties' => [
                        'update_link' => [
                            'direct_params' => [
                                'redirectData' => [
                                    'route' => 'oro_config_configuration_system',
                                ],
                            ],
                        ],
                    ],
                ],
                $organization,
            ]
        ];
    }
}
