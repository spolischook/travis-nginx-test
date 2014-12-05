<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Twig;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

use OroPro\Bundle\OrganizationBundle\Twig\SearchResultOrganizationExtension;
use OroPro\Bundle\SecurityBundle\Tests\Unit\Fixture\GlobalOrganization;

class SearchResultOrganizationExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var SearchResultOrganizationExtension */
    protected $searchExtension;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    protected function setUp()
    {
        $this->configProvider  = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper  = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade  = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchExtension = new SearchResultOrganizationExtension(
            $this->securityFacade,
            $this->configProvider,
            $this->doctrineHelper
        );
    }

    public function testGetFunctions()
    {
        $result = $this->searchExtension->getFunctions();
        $this->assertCount(1, $result);
        /** @var \Twig_SimpleFunction $function */
        $function = $result[0];
        $this->assertEquals('oropro_search_entity_organization_info', $function->getName());
    }

    public function testGetName()
    {
        $this->assertEquals('oropro_search_organization', $this->searchExtension->getName());
    }

    /**
     * @dataProvider dataProvider
     *
     * @param bool        $isGlobal
     * @param Config|null $config
     * @param object      $testEntity
     * @param mixed       $expectedResult
     */
    public function testGetOrganizationInfo($isGlobal, $config, $testEntity, $expectedResult)
    {
        $organization = new GlobalOrganization();
        $organization->setIsGlobal($isGlobal);
        $this->securityFacade->expects($this->any())->method('getOrganization')->willReturn($organization);
        $this->doctrineHelper->expects($this->any())->method('getEntityClass')->willReturn('Acme\Test\TestEntity');
        $this->configProvider->expects($this->any())->method('hasConfig')->with('Acme\Test\TestEntity')
            ->willReturn(is_object($config));
        $this->configProvider->expects($this->any())->method('getConfig')->with('Acme\Test\TestEntity')
            ->willReturn($config);
        $this->assertEquals($expectedResult, $this->searchExtension->getOrganizationInfo($testEntity));
    }

    public function dataProvider()
    {
        $testEntity = new \stdClass();
        $configId   = new EntityConfigId('ownership', 'Acme\Test\TestEntity');

        $userOwningConfig = new Config($configId);
        $userOwningConfig->set('owner_type', 'USER');
        $userOwningConfig->set('organization_field_name', 'organization');
        $userTestOrg = new GlobalOrganization();
        $userTestOrg->setName('User Owning Test Org');
        $userOwningTestEntity               = new \stdClass();
        $userOwningTestEntity->organization = $userTestOrg;

        $buOwningConfig = new Config($configId);
        $buOwningConfig->set('owner_type', 'BUSINESS_UNIT');
        $buOwningConfig->set('organization_field_name', 'organization');
        $buTestOrg = new GlobalOrganization();
        $buTestOrg->setName('Business Unit Owning Test Org');
        $buOwningTestEntity               = new \stdClass();
        $buOwningTestEntity->organization = $buTestOrg;

        $organizationOwningConfig = new Config($configId);
        $organizationOwningConfig->set('owner_type', 'ORGANIZATION');
        $organizationOwningConfig->set('owner_field_name', 'owner');
        $orgTestOrg = new GlobalOrganization();
        $orgTestOrg->setName('Organization Test Org');
        $orgwningTestEntity        = new \stdClass();
        $orgwningTestEntity->owner = $orgTestOrg;

        $systemOwningConfig = new Config($configId);
        $systemOwningConfig->set('owner_type', 'SYSTEM');

        return [
            'Non global mode'             => [
                false,
                null,
                $testEntity,
                null
            ],
            'Non configurable entity'     => [
                true,
                null,
                $testEntity,
                null
            ],
            'User owning entity'          => [
                true,
                $userOwningConfig,
                $userOwningTestEntity,
                'User Owning Test Org'
            ],
            'Business Unit owning entity' => [
                true,
                $buOwningConfig,
                $buOwningTestEntity,
                'Business Unit Owning Test Org'
            ],
            'Organization owning entity'  => [
                true,
                $organizationOwningConfig,
                $orgwningTestEntity,
                'Organization Test Org'
            ],
            'System owning entity'        => [
                true,
                $systemOwningConfig,
                $testEntity,
                null
            ],
        ];
    }
}
