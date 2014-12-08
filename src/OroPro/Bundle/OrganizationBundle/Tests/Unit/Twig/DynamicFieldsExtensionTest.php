<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Twig;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use OroPro\Bundle\OrganizationBundle\Tests\Unit\Fixture\GlobalOrganization;
use OroPro\Bundle\OrganizationBundle\Twig\DynamicFieldsExtension;

class DynamicFieldsExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var DynamicFieldsExtension */
    protected $twigExtension;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $fieldTypeHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->fieldTypeHelper = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        unset($this->configManager);
        unset($this->fieldTypeHelper);
        unset($this->dateTimeFormatter);
        unset($this->router);
        unset($this->securityFacade);
    }

    /**
     * @dataProvider dataProvider
     *
     * @param integer $organizationId     Tested organization ID
     * @param integer $calls              How many times securityFacade::getOrganizationId() should be invoked
     * @param bool    $expected           Expected result
     * @param string  $entityName         Entity class name
     * @param Config  $organizationConfig Organization config
     */
    public function testFilterFields($organizationId, $calls, $expected, $entityName, $organizationConfig)
    {
        $this->securityFacade
            ->expects($this->exactly($calls))
            ->method('getOrganizationId')
            ->will($this->returnValue($organizationId));

        $organization = new GlobalOrganization();
        $organization->setIsGlobal(false);
        $this->securityFacade
            ->expects($this->exactly($calls))
            ->method('getOrganization')
            ->will($this->returnValue($organization));

        $this->prepareConfigs($entityName, $organizationConfig);
        $this->twigExtension = new DynamicFieldsExtension(
            $this->configManager,
            $this->fieldTypeHelper,
            $this->eventDispatcher,
            $this->securityFacade
        );

        $this->assertEquals(
            $expected,
            $this->twigExtension->filterFields(
                $this->getEntityConfig(
                    $entityName,
                    'extend',
                    ['owner' => 'Custom', 'state' => 'Active', 'is_deleted' => false]
                )
            )
        );
    }

    public function dataProvider()
    {
        $entityName1 = 'Test\Entity\Entity1';
        $config1  = $this->getEntityConfig(
            $entityName1,
            'organization',
            [ 'applicable' => ['all' => true, 'selective' => []] ]
        );
        $entityName2 = 'Test\Entity\Entity2';
        $config2  = $this->getEntityConfig(
            $entityName2,
            'organization',
            [ 'applicable' => ['all' => false, 'selective' => [1, 2]] ]
        );
        $entityName3 = 'Test\Entity\Entity3';
        $config3  = $this->getEntityConfig(
            $entityName3,
            'organization',
            [ 'applicable' => ['all' => false, 'selective' => []] ]
        );
        $entityName4 = 'Test\Entity\Entity4';
        $config4  = $this->getEntityConfig(
            $entityName4,
            'organization',
            []
        );
        return [
            [1, 0, true, $entityName1, $config1],
            [1, 1, true, $entityName2, $config2],
            [2, 1, true, $entityName2, $config2],
            [3, 1, false, $entityName2, $config2],
            [1, 1, false, $entityName3, $config3],
            [1, 0, false, $entityName4, $config4],
        ];
    }

    /**
     * @param string $className
     * @param Config $organizationConfig
     */
    protected function prepareConfigs($className, Config $organizationConfig)
    {
        $entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $entityConfigProvider
            ->expects($this->any())
            ->method('getConfigById')
            ->will(
                $this->returnValue(
                    $this->getEntityConfig(
                        $className,
                        'entity',
                        []
                    )
                )
            );

        $viewConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $viewConfigProvider
            ->expects($this->any())
            ->method('getConfigById')
            ->will(
                $this->returnValue(
                    $this->getEntityConfig(
                        $className,
                        'view',
                        ['is_displayable' => true]
                    )
                )
            );

        $extendConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $extendConfigProvider
            ->expects($this->any())
            ->method('getConfigById')
            ->will(
                $this->returnValue(
                    $this->getEntityConfig(
                        $className,
                        'extend',
                        ['owner' => 'Custom', 'state' => 'Active', 'is_deleted' => false]
                    )
                )
            );

        $organizationConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $organizationConfigProvider
            ->expects($this->any())
            ->method('getConfigById')
            ->will($this->returnValue($organizationConfig));

        $this->configManager
            ->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['entity', $entityConfigProvider],
                        ['extend', $extendConfigProvider],
                        ['view', $viewConfigProvider],
                        ['organization', $organizationConfigProvider]
                    ]
                )
            );
    }

    protected function getEntityConfig($entityClassName, $scope, $values = [])
    {
        $entityConfigId = new FieldConfigId($scope, $entityClassName, 'testField', 'string');
        $entityConfig   = new Config($entityConfigId);
        $entityConfig->setValues($values);

        return $entityConfig;
    }
}
