<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

use OroPro\Bundle\OrganizationBundle\EventListener\ReportNavigationListener;

class ReportNavigationListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ReportNavigationListener */
    protected $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject  */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $aclHelper;

    public function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclHelper = $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new ReportNavigationListener(
            $this->entityManager,
            $this->configProvider,
            $this->securityFacade,
            $this->aclHelper
        );

        $organizationProvider = $this
            ->getMock('OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider');
        $organizationProvider->expects($this->any())
            ->method('getOrganizationId')
            ->will($this->returnValue(null));

        $this->listener->setOrganizationProvider($organizationProvider);
    }

    /**
     * @dataProvider dataProvider
     *
     * @param integer $organizationId     Tested organization ID
     * @param integer $calls              How many times getOrganizationConfig() was invoked
     * @param bool    $expected           Expected result
     * @param Config  $config             Given config
     * @param Config  $organizationConfig Organization config
     */
    public function testCheckAvailability($organizationId, $calls, $expected, $config, $organizationConfig)
    {
        $this->securityFacade->expects($this->exactly($calls))
            ->method('getOrganizationId')
            ->will($this->returnValue($organizationId));
        $this->getOrganizationConfig($config, $organizationConfig);

        $this->assertEquals($expected, $this->listener->checkAvailability($config));
    }


    public function dataProvider()
    {
        $entityName1 = 'Test\Entity\Entity1';
        $config1     = $this->getEntityConfig($entityName1, 'entity');
        $orgConfig1  = $this->getEntityConfig(
            $entityName1,
            'organization',
            [ 'applicable' => ['all' => false, 'selective' => [1]] ]
        );
        $entityName2 = 'Test\Entity\Entity2';
        $config2     = $this->getEntityConfig($entityName2, 'entity');
        $orgConfig2  = $this->getEntityConfig(
            $entityName2,
            'organization',
            [ 'applicable' => ['all' => true] ]
        );
        $entityName3 = 'Test\Entity\Entity3';
        $config3     = $this->getEntityConfig($entityName3, 'entity');
        $orgConfig3  = $this->getEntityConfig(
            $entityName3,
            'organization',
            [ 'applicable' => ['all' => false, 'selective' => [1, 3, 5]] ]
        );
        $entityName4 = 'Test\Entity\Entity4';
        $config4     = $this->getEntityConfig($entityName4, 'entity');
        $orgConfig4  = $this->getEntityConfig(
            $entityName4,
            'organization',
            []
        );
        return [
            [1, 1, true, $config1, $orgConfig1],
            [3, 0, true, $config2, $orgConfig2],
            [3, 1, true, $config3, $orgConfig3],
            [4, 1, false, $config3, $orgConfig3],
            [1, 0, false, $config4, $orgConfig4]
        ];
    }

    protected function getOrganizationConfig(Config $extendConfig, $organizationConfig)
    {
        $className  = $extendConfig->getId()->getClassname();
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $organizationConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $organizationConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->will($this->returnValue($organizationConfig));

        $configManager->expects($this->once())
            ->method('getProvider')
            ->with('organization')
            ->will($this->returnValue($organizationConfigProvider));

        $this->configProvider->expects($this->once())
            ->method('getConfigManager')
            ->will($this->returnValue($configManager));

        return $organizationConfig;
    }

    protected function getEntityConfig($entityClassName, $scope, $values = [])
    {
        $entityConfigId = new EntityConfigId($scope, $entityClassName);
        $entityConfig   = new Config($entityConfigId);
        $entityConfig->setValues($values);

        return $entityConfig;
    }
}
