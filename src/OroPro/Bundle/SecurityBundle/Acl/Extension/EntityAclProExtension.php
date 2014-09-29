<?php

namespace OroPro\Bundle\SecurityBundle\Acl\Extension;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;

class EntityAclProExtension extends EntityAclExtension
{
    /**
     * {@inheritdoc}
     */
    public function getAccessLevelNames($object)
    {
        if ($this->getObjectClassName($object) === ObjectIdentityFactory::ROOT_IDENTITY_TYPE) {
            return AccessLevel::getAccessLevelNames(AccessLevel::BASIC_LEVEL, AccessLevel::SYSTEM_LEVEL);
        }

        return parent::getAccessLevelNames($object);
    }
}
