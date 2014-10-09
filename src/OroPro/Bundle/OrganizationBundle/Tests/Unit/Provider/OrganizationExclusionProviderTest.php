<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use OroPro\Bundle\OrganizationBundle\Provider\OrganizationExclusionProvider;

class OrganizationExclusionProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrganizationExclusionProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    public function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new OrganizationExclusionProvider($this->securityFacade, $this->configProvider);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testIsIgnoredEntity($organizationId, $className, $expected, $entity)
    {
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->will($this->returnValue($entity));

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->will($this->returnValue(true));

        $this->securityFacade->expects($this->any())
            ->method('getOrganizationId')
            ->will($this->returnValue($organizationId));

        $this->assertEquals($expected, $this->provider->isIgnoredEntity($className));
    }

    public function dataProvider()
    {
        return [
            [
                1,
                'Test\Entity\Entity1',
                false,
                $this->getEntityConfig(
                    'Test\Entity\Entity1',
                    [ 'applicable' => ['all' => false, 'selective' => [1]] ]
                )
            ],
            [
                1,
                'Test\Entity\Entity2',
                true,
                $this->getEntityConfig(
                    'Test\Entity\Entity2',
                    [ 'applicable' => ['all' => false, 'selective' => [2]] ]
                )
            ],
            [
                1,
                'Test\Entity\Entity3',
                false,
                $this->getEntityConfig(
                    'Test\Entity\Entity2',
                    [ 'applicable' => ['all' => true, 'selective' => [1, 2]] ]
                )
            ],
            [
                4,
                'Test\Entity\Entity4',
                false,
                $this->getEntityConfig(
                    'Test\Entity\Entity2',
                    [  ]
                )
            ],
        ];
    }

    protected function getEntityConfig($entityClassName, $values)
    {
        $entityConfigId = new EntityConfigId('organization', $entityClassName);
        $entityConfig   = new Config($entityConfigId);
        $entityConfig->setValues($values);

        return $entityConfig;
    }
}
