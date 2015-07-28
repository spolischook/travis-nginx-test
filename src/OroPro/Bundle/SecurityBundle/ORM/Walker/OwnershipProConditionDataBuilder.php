<?php

namespace OroPro\Bundle\SecurityBundle\ORM\Walker;

use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\BusinessUnitSecurityIdentity;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;
use OroPro\Bundle\SecurityBundle\Acl\Domain\OrganizationSecurityIdentity;
use OroPro\Bundle\SecurityBundle\Form\Model\Share;

class OwnershipProConditionDataBuilder extends OwnershipConditionDataBuilder
{
    protected $shareAccessLevels = [
        AccessLevel::BASIC_LEVEL,
        AccessLevel::LOCAL_LEVEL,
        AccessLevel::DEEP_LEVEL,
        AccessLevel::GLOBAL_LEVEL,
    ];

    /** @var SystemAccessModeOrganizationProvider */
    protected $organizationProvider;

    /** @var int */
    protected $globalOrganizationId;

    /**
     * @param SystemAccessModeOrganizationProvider $organizationProvider
     */
    public function setOrganizationProvider(SystemAccessModeOrganizationProvider $organizationProvider)
    {
        $this->organizationProvider = $organizationProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function buildConstraintIfAccessIsGranted(
        $targetEntityClassName,
        $accessLevel,
        OwnershipMetadataInterface $metadata
    ) {
        $token = $this->getSecurityContext()->getToken();
        // in System mode if additional organization was set - we should limit data by this organization
        if ($token instanceof OrganizationContextTokenInterface
            && $token->getOrganizationContext()->getIsGlobal()
            && $this->organizationProvider->getOrganizationId()
        ) {
            $accessLevel = AccessLevel::GLOBAL_LEVEL;
        }

        return parent::buildConstraintIfAccessIsGranted($targetEntityClassName, $accessLevel, $metadata);
    }

    /**
     * {@inheritdoc}
     */
    protected function getOrganizationId(OwnershipMetadataInterface $metadata = null)
    {
        $token = $this->getSecurityContext()->getToken();
        if ($token instanceof OrganizationContextTokenInterface
            && $token->getOrganizationContext()->getIsGlobal()
            && $this->organizationProvider->getOrganizationId()
        ) {
            return $this->organizationProvider->getOrganizationId();
        }

        if ($this->hasGlobalAccess($metadata)) {
            $globalOrganizationId = $this->getGlobalOrganizationId();

            if (!empty($globalOrganizationId)) {
                $result = [];
                array_push($result, $globalOrganizationId);
                array_push($result, parent::getOrganizationId());

                return $result;
            }
        }

        return parent::getOrganizationId();
    }

    /**
     * @param OwnershipMetadataInterface $metadata
     *
     * @return bool
     */
    protected function hasGlobalAccess(OwnershipMetadataInterface $metadata = null)
    {
        if (null !== $metadata) {
            return $metadata->isGlobalView();
        }

        return false;
    }

    /**
     * @return int|null
     */
    protected function getGlobalOrganizationId()
    {
        if (!$this->globalOrganizationId) {
            $globalOrganization       = $this->getObjectManager()
                ->getRepository('OroOrganizationBundle:Organization')
                ->findOneBy(['is_global' => 1]);

            if ($globalOrganization instanceof Organization) {
                $this->globalOrganizationId = $globalOrganization->getid();
            }
        }

        return $this->globalOrganizationId;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSecurityIdentityId(SecurityIdentityInterface $sid)
    {
        if ($sid instanceof UserSecurityIdentity) {
            $identifier = $sid->getClass() . '-' . $sid->getUsername();
            $username = true;
        } elseif ($sid instanceof RoleSecurityIdentity) {
            //skip Role SID because we didn't share records for Role
            return null;
        } elseif ($sid instanceof BusinessUnitSecurityIdentity || $sid instanceof OrganizationSecurityIdentity) {
            $identifier = $sid->getClass() . '-' . $sid->getId();
            $username = false;
        } else {
            throw new \InvalidArgumentException(
                '$sid must either be an instance of UserSecurityIdentity or RoleSecurityIdentity ' .
                'or BusinessUnitSecurityIdentity or OrganizationSecurityIdentity.'
            );
        }

        return $this->getObjectManager()->getRepository('OroSecurityBundle:AclSecurityIdentity')
            ->findOneBy([
                'identifier' => $identifier,
                'username' => $username,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getSecurityIdentityIdsByScope(array $sids, array $shareScope)
    {
        $sidIds = [];

        foreach ($sids as $key => $sid) {
            $sharedToScope = false;

            if ($sid instanceof UserSecurityIdentity) {
                $sharedToScope = Share::SHARE_SCOPE_USER;
            } elseif ($sid instanceof BusinessUnitSecurityIdentity) {
                $sharedToScope = Share::SHARE_SCOPE_BUSINESS_UNIT;
            } elseif ($sid instanceof OrganizationSecurityIdentity) {
                $sharedToScope = Share::SHARE_SCOPE_ORGANIZATION;
            }

            if (in_array($sharedToScope, $shareScope)) {
                $sidIds[] = $key;
            }
        }

        return $sidIds;
    }
}
