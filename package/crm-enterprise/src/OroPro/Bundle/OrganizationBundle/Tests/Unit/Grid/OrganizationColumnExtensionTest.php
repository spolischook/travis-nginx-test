<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

use OroPro\Bundle\OrganizationBundle\Grid\OrganizationColumnExtension;
use OroPro\Bundle\OrganizationBundle\Tests\Unit\Fixture\GlobalOrganization;

class OrganizationColumnExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityClassResolver;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $organizationProvider;

    /** @var OrganizationColumnExtension */
    protected $extension;

    protected function setUp()
    {
        $this->configManager        = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityClassResolver  = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade       = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->organizationProvider =
            $this->getMockBuilder('OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new OrganizationColumnExtension(
            $this->securityFacade,
            $this->configManager,
            $this->entityClassResolver,
            $this->organizationProvider
        );
    }

    protected function tearDown()
    {
        unset($this->securityFacade);
        unset($this->configManager);
        unset($this->entityClassResolver);
        unset($this->entityConfigProvider);
        unset($this->extension);
    }

    public function testIsApplicableFalse()
    {
        $this->securityFacade->expects($this->once())
            ->method('getOrganization')
            ->will($this->returnValue($this->getOrganizationMock()));

        $this->assertFalse($this->extension->isApplicable($this->getDatagridConfiguration([])));
    }

    public function testIsApplicableInSystemModeWithAdditionalOrg()
    {
        $this->securityFacade->expects($this->once())
            ->method('getOrganization')
            ->will($this->returnValue($this->getOrganizationMock(true)));
        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->willReturn($configProvider);
        $config = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $configProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->assertFalse($this->extension->isApplicable($this->getDatagridConfiguration([])));
    }

    /**
     * @dataProvider parametersProvider
     */
    public function testProcessConfig($sorters, $expectedSorters)
    {
        $config = $this->getDatagridConfiguration($sorters);

        $this->extension->processConfigs($config);

        $this->assertEquals(
            [
                'extended_entity_name' => 'Acme\Bundle\UserBundle\Entity\User',
                'options' => [],
                'source' => [
                    'query' => [
                        'select' => [],
                        'from' => [
                            'table' => 'Acme\Bundle\UserBundle\Entity\User',
                            'alias' => 'u',
                        ]
                    ]
                ],
                'columns' => [
                    OrganizationColumnExtension::COLUMN_NAME => [
                        'label'         => 'oro.organization.entity_label',
                        'type'          => 'field',
                        'frontend_type' => 'string',
                        'translatable'  => true,
                        'editable'      => false,
                        'renderable'    => true
                    ],
                ],
                'sorters' => $expectedSorters,
                'filters' => [
                    'columns' => [
                        OrganizationColumnExtension::COLUMN_NAME => [
                            'type'         => 'entity',
                            'data_name'    => 'org.id',
                            'enabled'      => true,
                            'translatable' => true,
                            'options'      => [
                                'field_options' => [
                                    'class'                => 'OroOrganizationBundle:Organization',
                                    'property'             => 'name',
                                    'multiple'             => true,
                                    'translatable_options' => true
                                ]
                            ]
                        ]
                    ]
                ],
                'name' => 'acme_user'
            ],
            $config->toArray()
        );
    }

    /**
     * @param bool $isGlobal
     *
     * @return GlobalOrganization
     */
    protected function getOrganizationMock($isGlobal = false)
    {
        $organization = new GlobalOrganization();
        $organization->setIsGlobal($isGlobal);

        return $organization;
    }

    /**
     * @return DatagridConfiguration
     */
    protected function getDatagridConfiguration($sorters)
    {
        $params = [
            'extended_entity_name' => 'Acme\Bundle\UserBundle\Entity\User',
            'options' => [],
            'source' => [
                'query' => [
                    'select' => [],
                    'from' => [
                        'table' => 'Acme\Bundle\UserBundle\Entity\User',
                        'alias' => 'u',
                    ]
                ]
            ],
            'columns' => [],
            'sorters' => $sorters,
            'filters' => []
        ];

        return DatagridConfiguration::createNamed('acme_user', $params);
    }

    /**
     * Parameters provider
     *
     * @return array
     */
    public function parametersProvider()
    {
        return [
            //without default sorting
            [
                [],
                [
                    'columns' => [
                        OrganizationColumnExtension::COLUMN_NAME => [
                            'data_name' => OrganizationColumnExtension::COLUMN_NAME
                        ]
                    ],
                    'default' => [
                        OrganizationColumnExtension::COLUMN_NAME => 'ASC'
                    ]
                ]
            ],
            //with default sorting
            [
                ['default' => ['test' => 'ASC']],
                [
                    'columns' => [
                        OrganizationColumnExtension::COLUMN_NAME => [
                            'data_name' => OrganizationColumnExtension::COLUMN_NAME
                        ]
                    ],
                    'default' => [
                        'test' => 'ASC'
                    ]
                ]
            ],
            //without default sorting and multiple_sorting=true
            [
                ['multiple_sorting' => true, 'default' => ['test' => 'ASC']],
                [
                    'multiple_sorting' => true,
                    'default' => [
                        OrganizationColumnExtension::COLUMN_NAME => 'ASC',
                        'test' => 'ASC'
                    ],
                    'columns' => [
                        OrganizationColumnExtension::COLUMN_NAME => [
                            'data_name' => OrganizationColumnExtension::COLUMN_NAME
                        ]
                    ],
                ]
            ],

        ];
    }
}
