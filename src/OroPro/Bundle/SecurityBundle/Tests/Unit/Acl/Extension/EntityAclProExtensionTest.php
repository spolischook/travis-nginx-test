<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Acl\Extension;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Tests\Unit\Stub\OwnershipMetadataProviderStub;

use OroPro\Bundle\SecurityBundle\Tests\Unit\Fixture\GlobalOrganization;
use OroPro\Bundle\SecurityBundle\Tests\Unit\TestHelper;
use OroPro\Bundle\SecurityBundle\Acl\Extension\EntityAclProExtension;

class EntityAclProExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityAclProExtension */
    protected $extension;

    /** @var OwnershipMetadataProviderStub */
    protected $metadataProvider;

    /** @var OwnerTree */
    protected $tree;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $contextLink;

    public function setUp()
    {
        $this->tree             = new OwnerTree();
        $this->metadataProvider = new OwnershipMetadataProviderStub($this);
        $this->extension        = TestHelper::get($this)->createEntityAclExtension(
            $this->metadataProvider,
            $this->tree
        );

        $this->contextLink = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension->setContextLink($this->contextLink);
    }

    /**
     * @dataProvider decideIsGrantingDataProvider
     *
     * @param int  $accessLevel
     * @param bool $isGranted
     */
    public function testDecideIsGranting($accessLevel, $isGranted)
    {
        $organization = new GlobalOrganization();
        $token        = new UsernamePasswordOrganizationToken('admin', '', 'key', $organization);

        $this->assertEquals($isGranted, $this->extension->decideIsGranting($accessLevel, null, $token));
    }

    public function decideIsGrantingDataProvider()
    {
        return [
            [EntityMaskBuilder::MASK_VIEW_SYSTEM, true],
            [EntityMaskBuilder::MASK_VIEW_BASIC, false],
            [EntityMaskBuilder::MASK_VIEW_DEEP, false],
            [EntityMaskBuilder::MASK_VIEW_LOCAL, false],
            [EntityMaskBuilder::MASK_VIEW_GLOBAL, false],
        ];
    }

    public function testGetAccessLevelNamesForRoot()
    {
        $object = new ObjectIdentity('entity', ObjectIdentityFactory::ROOT_IDENTITY_TYPE);
        $this->assertEquals(
            [
                0 => 'NONE',
                1 => 'BASIC',
                2 => 'LOCAL',
                3 => 'DEEP',
                4 => 'GLOBAL',
                5 => 'SYSTEM'
            ],
            $this->extension->getAccessLevelNames($object)
        );
    }

    /**
     * @dataProvider getAccessLevelNamesDataProvider
     * @param string $ownerType
     * @param array  $accessLevels
     */
    public function testGetAccessLevelNames($ownerType, $accessLevels)
    {
        $object   = new ObjectIdentity('entity', 'Acme\TestBundle\TestEntity');
        $metaData = new OwnershipMetadata($ownerType, 'owner', 'owner');
        $this->metadataProvider->setMetadata('Acme\TestBundle\TestEntity', $metaData);
        $this->assertEquals($accessLevels, $this->extension->getAccessLevelNames($object));
    }

    public function getAccessLevelNamesDataProvider()
    {
        return [
            [
                'ORGANIZATION',
                [
                    0 => 'NONE',
                    4 => 'GLOBAL',
                    5 => 'SYSTEM'
                ],
            ],
            [
                'BUSINESS_UNIT',
                [
                    0 => 'NONE',
                    2 => 'LOCAL',
                    3 => 'DEEP',
                    4 => 'GLOBAL',
                    5 => 'SYSTEM'
                ],
            ],
            [
                'USER',
                [
                    0 => 'NONE',
                    1 => 'BASIC',
                    2 => 'LOCAL',
                    3 => 'DEEP',
                    4 => 'GLOBAL',
                    5 => 'SYSTEM'
                ],
            ],
            [
                'NONE',
                [
                    0 => 'NONE',
                    5 => 'SYSTEM'
                ],
            ],
        ];
    }

    public function testFixMaxAccessLevelInGlobalMode()
    {
        $organization = new GlobalOrganization();
        $token = new UsernamePasswordOrganizationToken('admin', 'admin', 'key', $organization);
        $securityContext = $this
            ->getMockBuilder('Symfony\Component\Security\Core\SecurityContext')
            ->disableOriginalConstructor()
            ->getMock();
        $securityContext->expects($this->once())->method('getToken')->willReturn($token);
        $this->contextLink->expects($this->once())->method('getService')->willReturn($securityContext);

        $this->assertEquals(
            AccessLevel::SYSTEM_LEVEL,
            $this->extension->getAccessLevel(EntityMaskBuilder::MASK_VIEW_SYSTEM, null, new \stdClass())
        );
    }
}
