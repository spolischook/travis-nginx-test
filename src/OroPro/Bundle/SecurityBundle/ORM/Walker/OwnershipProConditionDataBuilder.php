<?php

namespace OroPro\Bundle\SecurityBundle\ORM\Walker;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\OwnershipConditionDataBuilder;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;

class OwnershipProConditionDataBuilder extends OwnershipConditionDataBuilder
{
    /** @var SystemAccessModeOrganizationProvider */
    protected $organizationProvider;

    /** @var Organization */
    protected $globalOrganization;

    /** @var RegistryInterface */
    protected $registry;

    /**
     * @param SystemAccessModeOrganizationProvider $organizationProvider
     */
    public function setOrganizationProvider(SystemAccessModeOrganizationProvider $organizationProvider)
    {
        $this->organizationProvider = $organizationProvider;
    }

    /**
     * @param RegistryInterface $registry
     */
    public function setRegistry(RegistryInterface $registry)
    {
        $this->registry = $registry;
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
    protected function getOrganizationId(OwnershipMetadata $metadata = null)
    {
        $token = $this->getSecurityContext()->getToken();
        if ($token instanceof OrganizationContextTokenInterface
            && $token->getOrganizationContext()->getIsGlobal()
            && $this->organizationProvider->getOrganizationId()
        ) {
            return $this->organizationProvider->getOrganizationId();
        }

        if ($this->hasGlobalAccess($metadata)) {
            $globalOrganization = $this->getGlobalOrganizationId();

            if (!empty($globalOrganization)) {
                $result = [];
                array_push($result, $globalOrganization);
                array_push($result, parent::getOrganizationId());

                return $result;
            }
        }

        return parent::getOrganizationId();
    }

    /**
     * @param OwnershipMetadata $metadata
     *
     * @return bool
     */
    protected function hasGlobalAccess(OwnershipMetadata $metadata = null)
    {
        if (null !== $metadata) {
            $parameters = $metadata->getAdditionalParameters();
            return (array_key_exists('global_view', $parameters) && 'true' === $parameters['global_view']);
        }

        return false;
    }

    /**
     * @return int|null
     */
    protected function getGlobalOrganizationId()
    {
        if (!$this->globalOrganization instanceof Organization) {
            $globalOrganization       = $this->getObjectManager()
                ->getRepository('OroOrganizationBundle:Organization')
                ->findOneBy(['is_global' => 1]);
            $this->globalOrganization = $globalOrganization;
        }

        if ($this->globalOrganization instanceof Organization) {
            return $this->globalOrganization->getId();
        }

        return null;
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        return $this->registry->getManager();
    }
}
