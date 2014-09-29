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
        $minLevel = AccessLevel::BASIC_LEVEL;
        $maxLevel = AccessLevel::SYSTEM_LEVEL;

        if ($this->getObjectClassName($object) !== ObjectIdentityFactory::ROOT_IDENTITY_TYPE) {
            $metadata = $this->getMetadata($object);
            if (!$metadata->hasOwner()) {
                return array(
                    AccessLevel::NONE_LEVEL   => AccessLevel::NONE_LEVEL_NAME,
                    AccessLevel::SYSTEM_LEVEL => AccessLevel::getAccessLevelName(AccessLevel::SYSTEM_LEVEL)
                );
            }
            if ($metadata->isUserOwned()) {
                $maxLevel = AccessLevel::GLOBAL_LEVEL;
                $minLevel = AccessLevel::BASIC_LEVEL;
            } elseif ($metadata->isBusinessUnitOwned()) {
                $maxLevel = AccessLevel::GLOBAL_LEVEL;
                $minLevel = AccessLevel::LOCAL_LEVEL;
            } elseif ($metadata->isOrganizationOwned()) {
                $maxLevel = AccessLevel::GLOBAL_LEVEL;
                $minLevel = AccessLevel::GLOBAL_LEVEL;
            }
        }

        return AccessLevel::getAccessLevelNames($minLevel, $maxLevel);
    }
}
