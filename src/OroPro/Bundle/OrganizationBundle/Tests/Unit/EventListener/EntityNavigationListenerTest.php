<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

use OroPro\Bundle\OrganizationBundle\EventListener\EntityNavigationListener;
use OroPro\Bundle\SecurityBundle\Tests\Unit\Fixture\GlobalOrganization;

class EntityNavigationListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityNavigationListener */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new EntityNavigationListener(
            $this->securityFacade,
            $this->configManager,
            $this->translator
        );
    }

    public function testCheckAvailabilityInSystemAccessOrg()
    {
        $entityName = 'Test\Entity\Entity1';
        $organizationConfig  = $this->getEntityConfig(
            $entityName,
            'organization',
            [ 'applicable' => ['all' => false, 'selective' => [1]] ]
        );

        $organization = new GlobalOrganization();
        $organization->setId(1);

        $this->securityFacade->expects($this->any())
            ->method('getOrganization')
            ->will($this->returnValue($organization));

        $this->assertEquals(true, $this->listener->checkAvailability($organizationConfig));
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
        return [
            [1, 1, true, $config1, $orgConfig1],
            [3, 0, true, $config2, $orgConfig2],
            [3, 1, true, $config3, $orgConfig3],
            [4, 1, false, $config3, $orgConfig3],
        ];
    }

    protected function getOrganizationConfig(Config $extendConfig, $organizationConfig)
    {
        $className = $extendConfig->getId()->getClassname();
        $organizationConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $organizationConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->will($this->returnValue($organizationConfig));

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('organization')
            ->will($this->returnValue($organizationConfigProvider));
    }

    protected function getEntityConfig($entityClassName, $scope, $values = [])
    {
        $extend = [
            'is_extend' => true,
            'owner'     => ExtendScope::OWNER_CUSTOM,
            'state'     => ExtendScope::STATE_ACTIVE
        ];
        $entityConfigId = new EntityConfigId($scope, $entityClassName);
        $entityConfig   = new Config($entityConfigId);
        $entityConfig->setValues(array_merge($extend, $values));

        return $entityConfig;
    }
}
