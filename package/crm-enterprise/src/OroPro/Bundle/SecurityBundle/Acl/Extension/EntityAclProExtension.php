<?php

namespace OroPro\Bundle\SecurityBundle\Acl\Extension;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

class EntityAclProExtension extends EntityAclExtension
{
    /**
     * {@inheritdoc}
     */
    public function decideIsGranting($triggeredMask, $object, TokenInterface $securityToken)
    {
        // check if we are in global mode - return false in case if Access Level < AccessLevel::SYSTEM_LEVEL
        if ($securityToken instanceof OrganizationContextTokenInterface) {
            $organization = $securityToken->getOrganizationContext();

            if ($organization->getIsGlobal() && $this->getAccessLevel($triggeredMask) !== AccessLevel::SYSTEM_LEVEL) {
                return false;
            }
        }

        return parent::decideIsGranting($triggeredMask, $object, $securityToken);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessLevelNames($object)
    {
        $minLevel = AccessLevel::BASIC_LEVEL;

        if ($this->getObjectClassName($object) === ObjectIdentityFactory::ROOT_IDENTITY_TYPE) {
            return AccessLevel::getAccessLevelNames($minLevel);
        } else {
            $metadata = $this->getMetadata($object);
            if (!$metadata->hasOwner()) {
                return [
                    AccessLevel::NONE_LEVEL   => AccessLevel::NONE_LEVEL_NAME,
                    AccessLevel::SYSTEM_LEVEL => AccessLevel::getAccessLevelName(AccessLevel::SYSTEM_LEVEL)
                ];
            }
            if ($metadata->isBasicLevelOwned()) {
                $minLevel = AccessLevel::BASIC_LEVEL;
            } elseif ($metadata->isLocalLevelOwned()) {
                $minLevel = AccessLevel::LOCAL_LEVEL;
            } elseif ($metadata->isGlobalLevelOwned()) {
                $minLevel = AccessLevel::GLOBAL_LEVEL;
            }
        }

        return AccessLevel::getAccessLevelNames($minLevel);
    }

    /**
     * {@inheritdoc}
     */
    protected function isAccessDeniedByOrganizationContext($object, OrganizationContextTokenInterface $securityToken)
    {
        if ($securityToken->getOrganizationContext()->getIsGlobal()) {
            return false;
        }

        return parent::isAccessDeniedByOrganizationContext($object, $securityToken);
    }
}
