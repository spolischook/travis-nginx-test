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

        $this->extension = new OrganizationColumnExtension(
            $this->securityFacade,
            $this->configManager,
            $this->entityClassResolver
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

        $this->assertFalse($this->extension->isApplicable($this->getDatagridConfiguration()));
    }

    public function testProcessConfig()
    {
        $config = $this->getDatagridConfiguration();

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
                'sorters' => [
                    'columns' => [
                        OrganizationColumnExtension::COLUMN_NAME => [
                            'data_name' => OrganizationColumnExtension::COLUMN_NAME
                        ]
                    ],
                    'default' => [
                        'name' => 'ASC'
                    ]
                ],
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
    protected function getDatagridConfiguration()
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
            'sorters' => [],
            'filters' => []
        ];

        return DatagridConfiguration::createNamed('acme_user', $params);
    }
}
