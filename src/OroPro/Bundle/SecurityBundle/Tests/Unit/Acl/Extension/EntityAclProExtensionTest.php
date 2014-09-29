<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Acl\Extension;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;

use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\OwnershipMetadataProviderStub;

use OroPro\Bundle\SecurityBundle\Tests\Unit\TestHelper;
use OroPro\Bundle\SecurityBundle\Acl\Extension\EntityAclProExtension;

class EntityAclProExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityAclProExtension */
    private $extension;

    /** @var OwnershipMetadataProviderStub */
    private $metadataProvider;

    /** @var OwnerTree */
    private $tree;

    public function testGetAccessLevelNamesForRoot()
    {
        $this->tree             = new OwnerTree();
        $this->metadataProvider = new OwnershipMetadataProviderStub($this);
        $this->extension        = TestHelper::get($this)->createEntityAclExtension(
            $this->metadataProvider,
            $this->tree
        );

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
}
