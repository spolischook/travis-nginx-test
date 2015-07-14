<?php

namespace OroPro\Bundle\UserBundle\Tests\Unit\EventListener\Datagrid;

use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\UserBundle\Entity\OrganizationAwareUserInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

use OroPro\Bundle\SecurityBundle\Tests\Unit\Fixture\GlobalOrganization;
use OroPro\Bundle\UserBundle\EventListener\Datagrid\RoleListener;

class RoleListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RoleListener
     */
    protected $roleListener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ServiceLink
     */
    protected $serviceLink;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface;
     */
    protected $token;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|OrganizationAwareUserInterface;
     */
    protected $user;

    protected function setUp()
    {
        $this->serviceLink = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityContext = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContextInterface')
            ->getMock();

        $this->token = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->getMock();

        $this->user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\OrganizationAwareUserInterface')
            ->getMock();

        $this->roleListener = new RoleListener($this->serviceLink);
    }

    protected function tearDown()
    {
        unset(
            $this->serviceLink,
            $this->securityContext,
            $this->token,
            $this->user,
            $this->roleListener
        );
    }

    public function testOnBuildBeforeWhenUserIsNotAssignedToAnyOrganization()
    {
        $config = $this->getConfig();
        $event = $this->createEvent($config);

        $this->serviceLink->expects($this->once())
            ->method('getService')
            ->will($this->returnValue($this->securityContext));

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($this->token));

        $this->token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($this->user));

        $this->user->expects($this->once())
            ->method('getOrganizations')
            ->will($this->returnValue([]));

        $this->roleListener->onBuildBefore($event);

        $expectedConfig = $this->getExpectedConfig();
        $param = RoleListener::ORGANIZATION_ALIAS . '.' . 'id';
        $expectedConfig['source']['query']['where']['and'] = ['0' => $param . ' IS NULL'];

        $this->assertEquals($expectedConfig, $event->getConfig()->toArray());
    }

    public function testOnBuildBeforeWhenUserIsAssignedToSystemOrganization()
    {
        $config = $this->getConfig();
        $event = $this->createEvent($config);

        $this->serviceLink->expects($this->once())
            ->method('getService')
            ->will($this->returnValue($this->securityContext));

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($this->token));

        $this->token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($this->user));

        $this->user->expects($this->once())
            ->method('getOrganizations')
            ->will($this->returnValue($this->getOrganizations()));

        $this->roleListener->onBuildBefore($event);

        $expectedConfig = $this->getExpectedConfig();

        $this->assertEquals($expectedConfig, $event->getConfig()->toArray());
    }

    public function testOnBuildBeforeWhenUserIsNotAssignedToSystemOrganization()
    {
        $config = $this->getConfig();
        $event = $this->createEvent($config);

        $this->serviceLink->expects($this->once())
            ->method('getService')
            ->will($this->returnValue($this->securityContext));

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($this->token));

        $this->token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($this->user));

        $organization = new GlobalOrganization();
        $organization->setId(1);
        $organization->setIsGLobal(false);

        $this->user->expects($this->once())
            ->method('getOrganizations')
            ->will($this->returnValue([$organization]));

        $this->roleListener->onBuildBefore($event);

        $expectedConfig = $this->getExpectedConfig();
        $param = RoleListener::ORGANIZATION_ALIAS . '.' . 'id';
        $expectedConfig['source']['query']['where']['and'] =
            ['0' => $param . ' IN (1) OR ' . $param . ' IS NULL'];


        $this->assertEquals($expectedConfig, $event->getConfig()->toArray());
    }

    /**
     * @return array
     */
    protected function getConfig()
    {
        return [
            'source' => [
                'type' => 'orm',
                'query' => [
                    'select' => [
                        'field1',
                        'field2',
                        'field3',
                    ],
                    'from' => [
                        [
                            'table' => 'testTable',
                            'alias' => 'testAlias'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getExpectedConfig()
    {
        return [
            'source' => [
                'type' => 'orm',
                'query' => [
                    'select' => [
                        'field1',
                        'field2',
                        'field3',
                        RoleListener::ORGANIZATION_ALIAS . '.' . RoleListener::ORGANIZATION_NAME_COLUMN
                    ],
                    'from' => [
                        [
                            'table' => 'testTable',
                            'alias' => 'testAlias'
                        ]
                    ],
                    'join' => [
                        'left' => [
                            [
                                'join' => 'testAlias' . '.' . RoleListener::ORGANIZATION_FIELD,
                                'alias' => RoleListener::ORGANIZATION_ALIAS
                            ]
                        ]
                    ]
                ]
            ],
            'columns' => [
                RoleListener::ORGANIZATION_NAME_COLUMN => [
                    'label' => 'oro.user.role.organization.label'
                ]
            ],
            'filters' => [
                'columns' => [
                    RoleListener::ORGANIZATION_NAME_COLUMN => [
                        'type' => 'string',
                        'data_name' => RoleListener::ORGANIZATION_ALIAS . '.' . RoleListener::ORGANIZATION_NAME_COLUMN
                    ]
                ]
            ],
            'sorters' => [
                'columns' => [
                    RoleListener::ORGANIZATION_NAME_COLUMN => [
                        'data_name' => RoleListener::ORGANIZATION_ALIAS . '.' . RoleListener::ORGANIZATION_NAME_COLUMN
                    ]
                ]
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getOrganizations()
    {
        $organization1 = new GlobalOrganization();
        $organization1->setId(1);
        $organization1->setIsGLobal(false);

        $organization2 = new GlobalOrganization();
        $organization2->setId(2);
        $organization2->setIsGLobal(true);

        return [
            $organization1,
            $organization2
        ];
    }

    /**
     * @param array $configuration
     * @return BuildBefore
     */
    protected function createEvent(array $configuration)
    {
        $datagridConfiguration = DatagridConfiguration::create($configuration);

        $event = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\BuildBefore')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())->method('getConfig')
            ->will($this->returnValue($datagridConfiguration));

        return $event;
    }
}
