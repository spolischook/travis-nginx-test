<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Acl\Extension;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;

use OroPro\Bundle\SecurityBundle\Tests\Unit\Fixture\GlobalOrganization;
use OroPro\Bundle\SecurityBundle\Tests\Unit\TestHelper;
use OroPro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\OwnershipMetadataProProviderStub;
use OroPro\Bundle\SecurityBundle\Acl\Extension\EntityAclProExtension;

class EntityAclProExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityAclProExtension */
    protected $extension;

    /** @var OwnershipMetadataProProviderStub */
    protected $metadataProvider;

    /** @var OwnerTree */
    protected $tree;

    public function setUp()
    {
        $this->tree = new OwnerTree();
        $this->metadataProvider = new OwnershipMetadataProProviderStub($this);
        $this->extension = TestHelper::get($this)->createEntityAclExtension($this->metadataProvider, $this->tree);
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

    /**
     * @return array
     */
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

    /**
     * @return array
     */
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

    public function testGetMaxAccessLevelInGlobalMode()
    {
        $organization = new GlobalOrganization();
        $token = new UsernamePasswordOrganizationToken('admin', 'admin', 'key', $organization);
        $this->metadataProvider->getSecurityContext()->expects($this->once())->method('getToken')->willReturn($token);

        $this->assertEquals(
            AccessLevel::SYSTEM_LEVEL,
            $this->extension->getAccessLevel(EntityMaskBuilder::MASK_VIEW_SYSTEM, null, new \stdClass())
        );
    }
}
