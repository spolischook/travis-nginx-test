<?php

namespace OroPro\Bundle\UserBundle\Tests\Unit\EventListener\Datagrid;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

use OroPro\Bundle\OrganizationBundle\Helper\OrganizationProHelper;
use OroPro\Bundle\UserBundle\EventListener\Datagrid\RoleListener;
use OroPro\Bundle\UserBundle\Helper\UserProHelper;

class RoleListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RoleListener
     */
    protected $roleListener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UserProHelper
     */
    protected $userHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|OrganizationProHelper
     */
    protected $organizationHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|OrganizationContextTokenInterface
     */
    protected $token;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Organization
     */
    protected $organization;

    protected function setUp()
    {
        $this->userHelper = $this->getMockBuilder('OroPro\Bundle\UserBundle\Helper\UserProHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->organizationHelper = $this->getMockBuilder(
            'OroPro\Bundle\OrganizationBundle\Helper\OrganizationProHelper'
        )
            ->disableOriginalConstructor()
            ->getMock();


        $this->tokenStorage = $this->getMock(
            'Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface'
        );

        $this->token = $this->getMock(
            'Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface'
        );

        $this->organization = $this->getMock(
            'Oro\Bundle\OrganizationBundle\Entity\Organization',
            ['getId', 'getIsGlobal']
        );

        $this->roleListener = new RoleListener(
            $this->userHelper,
            $this->organizationHelper,
            $this->tokenStorage
        );
    }

    protected function tearDown()
    {
        unset(
            $this->serviceLink,
            $this->tokenStorage,
            $this->token,
            $this->user,
            $this->roleListener
        );
    }

    public function testOnBuildBeforeWhenUserIsLoggedInToGlobalOrganization()
    {
        $config = $this->getConfig();
        $event = $this->createEvent($config);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($this->token));

        $this->token->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($this->organization));

        $this->organization->expects($this->exactly(2))
            ->method('getIsGlobal')
            ->will($this->returnValue(true));

        $this->userHelper->expects($this->once())
            ->method('isUserAssignedToOrganization')
            ->with($this->organization)
            ->will($this->returnValue(true));

        $organizationChoices = ['1' => 'Foo', '2' => 'Bar'];

        $this->organizationHelper->expects($this->once())
            ->method('getOrganizationFilterChoices')
            ->will($this->returnValue($organizationChoices));

        $this->roleListener->onBuildBefore($event);

        $expectedConfig = $this->getExpectedConfig($organizationChoices);

        $this->assertEquals($expectedConfig, $event->getConfig()->toArray());
    }

    public function testOnBuildBeforeWhenUserIsNotLoggedInToGlobalOrganization()
    {
        $config = $this->getConfig();
        $event = $this->createEvent($config);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($this->token));

        $this->token->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($this->organization));

        $this->organization->expects($this->exactly(2))
            ->method('getIsGlobal')
            ->will($this->returnValue(false));

        $this->organization->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1));

        $this->userHelper->expects($this->never())
            ->method($this->anything());

        $organizationChoices = ['1' => 'Foo'];

        $this->organizationHelper->expects($this->never())
            ->method('getOrganizationFilterChoices');

        $this->roleListener->onBuildBefore($event);

        $completeConfig = $this->getExpectedConfig($organizationChoices);
        $completeConfig['source']['query']['where']['and'] =
            ['0' => 'org.id = 1 OR org.id IS NULL'];

        $expectedConfig['source'] = $completeConfig['source'];

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
    protected function getExpectedConfig(array $organizationChoices = [])
    {
        return [
            'source' => [
                'type' => 'orm',
                'query' => [
                    'select' => [
                        'field1',
                        'field2',
                        'field3',
                        'org.name AS org_name'
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
                                'join' => 'testAlias' . '.organization',
                                'alias' => 'org'
                            ]
                        ]
                    ]
                ]
            ],
            'columns' => [
                'org_name' => [
                    'label' => 'oro.user.role.organization.label',
                    'type' => 'twig',
                    'frontend_type' => 'html',
                    'template' => 'OroProUserBundle:Role:Datagrid/Property/organization.html.twig',
                ]
            ],
            'filters' => [
                'columns' => [
                    'org_name' => [
                        'type' => 'choice',
                        'data_name' => 'org.id',
                        'enabled'      => true,
                        'options'      => [
                            'field_options' => [
                                'choices'              => $organizationChoices,
                                'translatable_options' => false,
                                'multiple'             => true,
                            ]
                        ]
                    ]
                ]
            ],
            'sorters' => [
                'columns' => [
                    'org_name' => [
                        'data_name' => 'org_name'
                    ]
                ]
            ],
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
