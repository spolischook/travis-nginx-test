<?php

namespace OroPro\Bundle\SecurityBundle\ORM\Walker;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;

use OroPro\Bundle\OrganizationBundle\Provider\OrganizationIdProvider;

class OwnershipProConditionDataBuilder extends OwnershipConditionDataBuilder
{
    /** @var OrganizationIdProvider */
    protected $organizationIdProvider;

    /**
     * @param OrganizationIdProvider $organizationIdProvider
     */
    public function setOrganizationIdProvider(OrganizationIdProvider $organizationIdProvider)
    {
        $this->organizationIdProvider = $organizationIdProvider;
    }

    protected function buildConstraintIfAccessIsGranted(
        $targetEntityClassName,
        $accessLevel,
        OwnershipMetadata $metadata
    ) {
        $token = $this->getSecurityContext()->getToken();
        if ($token instanceof OrganizationContextTokenInterface) {
            $organization = $token->getOrganizationContext();
            // in System mode if additional organization was set - we should limit data by this organization
            if ($organization->getIsGlobal() && $this->organizationIdProvider->getOrganizationId()) {
                if (!$metadata->hasOwner()) {
                    if ($this->metadataProvider->getOrganizationClass() === $targetEntityClassName) {
                        $tree       = $this->treeProvider->getTree();
                        $orgIds     = $tree->getUserOrganizationIds($this->getUserId());
                        $constraint = $this->getCondition($orgIds, $metadata, 'id');
                    } else {
                        $constraint = [];
                    }
                } else {
                    if ($metadata->isOrganizationOwned()) {
                        $constraint = $this->getCondition(
                            [$this->organizationIdProvider->getOrganizationId()],
                            $metadata
                        );
                    } else {
                        $constraint = $this->getCondition(null, $metadata, null, true);
                    }
                }

                return $constraint;
            }
        }

        return parent::buildConstraintIfAccessIsGranted($targetEntityClassName, $accessLevel, $metadata);
    }

    /**
     * @return int|null
     */
    protected function getOrganizationId()
    {
        $token = $this->getSecurityContext()->getToken();
        if ($token instanceof OrganizationContextTokenInterface
            && $token->getOrganizationContext()->getIsGlobal()
            && $this->organizationIdProvider->getOrganizationId()
        ) {
            return $this->organizationIdProvider->getOrganizationId();
        }

        return parent::getOrganizationId();
    }
}
