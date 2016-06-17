<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use OroPro\Bundle\OrganizationBundle\Provider\OrganizationExclusionProvider;
use OroPro\Bundle\OrganizationBundle\Tests\Unit\Fixture\GlobalOrganization;

class OrganizationExclusionProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var OrganizationExclusionProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ServiceLink */
    private $securityFacadeLink;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider */
    protected $organizationProvider;

    public function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacadeLink = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();

        $this->organizationProvider = $this
            ->getMockBuilder('OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new OrganizationExclusionProvider(
            $this->securityFacadeLink,
            $this->configProvider,
            $this->organizationProvider
        );
    }

    /**
     * @param int $organizationId
     * @param int $calls
     * @param string $className
     * @param bool $expected
     * @param Config $entityConfig
     *
     * @dataProvider dataProvider
     */
    public function testIsIgnoredEntity($organizationId, $calls, $className, $expected, $entityConfig)
    {
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->will($this->returnValue($entityConfig));

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->will($this->returnValue(true));

        $securityFacade = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacadeLink
            ->expects($this->any())
            ->method('getService')
            ->will($this->returnValue($securityFacade));

        $securityFacade
            ->expects($this->exactly($calls))
            ->method('getOrganizationId')
            ->will($this->returnValue($organizationId));

        $organization = new GlobalOrganization();
        $organization->setIsGlobal(false);
        $securityFacade
            ->expects($this->any())
            ->method('getOrganization')
            ->will($this->returnValue($organization));

        $this->assertEquals($expected, $this->provider->isIgnoredEntity($className));
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            [
                1,                     //organization Id
                1,                     //getOrganizationId calls num
                'Test\Entity\Entity1', //Entity class name
                false,                 //expectation
                $this->getEntityConfig(
                    'Test\Entity\Entity1',
                    [ 'applicable' => ['all' => false, 'selective' => [1]] ]
                )
            ],
            [
                1,
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
                0,
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
