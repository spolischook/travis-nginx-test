<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Acl\Extension;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User;

use OroPro\Bundle\SecurityBundle\Owner\Metadata\OwnershipProMetadata;
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
        $this->metadataProvider = $this->getOwnershipMetadataProviderStub();
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
            [1 << 4 /* MASK_VIEW_SYSTEM */, true],
            [1 << 0 /*MASK_VIEW_BASIC */, false],
            [1 << 2 /*MASK_VIEW_DEEP */, false],
            [1 << 1 /*MASK_VIEW_LOCAL */, false],
            [1 << 3 /*MASK_VIEW_GLOBAL */, false],
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
        $metaData = new OwnershipProMetadata($ownerType, 'owner', 'owner');
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
            $this->extension->getAccessLevel(1 << 4 /* MASK_VIEW_SYSTEM */, null, new \stdClass())
        );
    }

    /**
     * @dataProvider adaptRootMaskProvider
     *
     * @param object $object
     * @param string $ownerType
     * @param int $aceMask
     * @param int $expectedMask
     */
    public function testAdaptRootMask($object, $ownerType, $aceMask, $expectedMask)
    {
        if ($ownerType !== null) {
            $this->metadataProvider->setMetadata(
                'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\TestEntity',
                new OwnershipProMetadata($ownerType, 'owner', 'owner_id')
            );
        }

        $resultMask = $this->extension->adaptRootMask($aceMask, $object);
        $this->assertEquals(
            $expectedMask,
            $resultMask,
            sprintf(
                'Expected "%s" -> "%s"; Actual: "%s"',
                $this->extension->getMaskPattern($aceMask),
                $this->extension->getMaskPattern($expectedMask),
                $this->extension->getMaskPattern($resultMask)
            )
        );
    }

    /**
     * @return array
     */
    public static function adaptRootMaskProvider()
    {
        return [
            [
                new TestEntity(),
                null,
                (1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 9) /* MASK_CREATE_SYSTEM */,
                (1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 9) /* MASK_CREATE_SYSTEM */
            ],
            [
                new TestEntity(),
                null,
                (1 << 0) /* MASK_VIEW_BASIC */ | (1 << 6) /* MASK_CREATE_LOCAL */,
                (1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 9) /* MASK_CREATE_SYSTEM */
            ],
            [
                new TestEntity(),
                null,
                ((1 << 9) | 32768) /* MASK_ASSIGN_SYSTEM */ | ((1 << 10) | 32768) /* MASK_SHARE_BASIC */,
                32768 /* GROUP_NONE */
            ],
            [
                new Organization(),
                null,
                (1 << 0) /* MASK_VIEW_BASIC */ | (1 << 6) /* MASK_CREATE_LOCAL */,
                (1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 9) /* MASK_CREATE_SYSTEM */
            ],
            [
                new BusinessUnit(),
                null,
                (1 << 0) /* MASK_VIEW_BASIC */ | (1 << 6) /* MASK_CREATE_LOCAL */,
                (1 << 1) /* MASK_VIEW_LOCAL */ | (1 << 6) /* MASK_CREATE_LOCAL */
            ],
            [
                new BusinessUnit(),
                null,
                (1 << 2) /* MASK_VIEW_DEEP */ | (1 << 6) /* MASK_CREATE_LOCAL */,
                (1 << 2) /* MASK_VIEW_DEEP */ | (1 << 6) /* MASK_CREATE_LOCAL */
            ],
            [
                new User(),
                null,
                (1 << 0) /* MASK_VIEW_BASIC */ | (1 << 6) /* MASK_CREATE_LOCAL */,
                (1 << 1) /* MASK_VIEW_LOCAL */ | (1 << 6) /* MASK_CREATE_LOCAL */
            ],
            [
                new TestEntity(),
                'ORGANIZATION',
                (1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 7) /* MASK_CREATE_DEEP */,
                (1 << 4) /* MASK_VIEW_SYSTEM */ | (1 << 8) /* MASK_CREATE_GLOBAL */
            ],
            [
                new TestEntity(),
                'BUSINESS_UNIT',
                (1 << 2) /* MASK_VIEW_DEEP */ | (1 << 5) /* MASK_CREATE_BASIC */,
                (1 << 2) /* MASK_VIEW_DEEP */ | (1 << 6) /* MASK_CREATE_LOCAL */
            ],
            [
                new TestEntity(),
                'USER',
                (1 << 3) /* MASK_VIEW_GLOBAL */ | (1 << 5) /* MASK_CREATE_BASIC */,
                (1 << 3) /* MASK_VIEW_GLOBAL */ | (1 << 5) /* MASK_CREATE_BASIC */
            ]
        ];
    }

    /**
     * @dataProvider validateMaskForOrganizationInvalidProvider
     * @expectedException \Oro\Bundle\SecurityBundle\Acl\Exception\InvalidAclMaskException
     *
     * @param int $mask
     */
    public function testValidateMaskForOrganizationInvalid($mask)
    {
        $this->extension->validateMask($mask, new Organization());
    }

    /**
     * @return array
     */
    public function validateMaskForOrganizationInvalidProvider()
    {
        return [
            [(1 << 9) + 32768 /*MASK_ASSIGN_SYSTEM*/],
            [(1 << 14) + 32768 /*MASK_SHARE_SYSTEM*/],
            [1 << 3 /*MASK_VIEW_GLOBAL*/],
            [1 << 2 /*MASK_VIEW_DEEP*/],
            [1 << 1 /*MASK_VIEW_LOCAL*/],
            [1 << 0 /*MASK_VIEW_BASIC*/]
        ];
    }

    /**
     * @return OwnershipMetadataProProviderStub
     */
    protected function getOwnershipMetadataProviderStub()
    {
        $metadataProvider = new OwnershipMetadataProProviderStub($this);
        $metadataProvider->setMetadata($metadataProvider->getGlobalLevelClass(), new OwnershipProMetadata());
        $metadataProvider->setMetadata(
            $metadataProvider->getLocalLevelClass(),
            new OwnershipProMetadata('BUSINESS_UNIT', 'owner', 'owner_id')
        );
        $metadataProvider->setMetadata(
            $metadataProvider->getBasicLevelClass(),
            new OwnershipProMetadata('BUSINESS_UNIT', 'owner', 'owner_id')
        );

        return $metadataProvider;
    }
}
