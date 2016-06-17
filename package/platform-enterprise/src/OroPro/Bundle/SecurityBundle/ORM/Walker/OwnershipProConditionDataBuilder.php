<?php

namespace OroPro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;

class OwnershipProConditionDataBuilder extends OwnershipConditionDataBuilder
{
    /** @var RegistryInterface */
    protected $registry;

    /** @var SystemAccessModeOrganizationProvider */
    protected $organizationProvider;

    /** @var int */
    protected $globalOrganizationId;

    /**
    * @param RegistryInterface $registry
    */
    public function setRegistry(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        return $this->registry->getManager();
    }

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
}
