<?php

namespace OroPro\Bundle\SecurityBundle\ORM\Walker;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;

use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;

class OwnershipProConditionDataBuilder extends OwnershipConditionDataBuilder
{
    /** @var SystemAccessModeOrganizationProvider */
    protected $organizationProvider;

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
        OwnershipMetadata $metadata
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
    protected function getOrganizationId()
    {
        $token = $this->getSecurityContext()->getToken();
        if ($token instanceof OrganizationContextTokenInterface
            && $token->getOrganizationContext()->getIsGlobal()
            && $this->organizationProvider->getOrganizationId()
        ) {
            return $this->organizationProvider->getOrganizationId();
        }

        return parent::getOrganizationId();
    }
}
