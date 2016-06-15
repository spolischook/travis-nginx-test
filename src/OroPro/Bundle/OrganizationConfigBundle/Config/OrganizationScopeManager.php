<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Config;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\ConfigBundle\Config\AbstractScopeManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

class OrganizationScopeManager extends AbstractScopeManager
{
    /** @var TokenStorageInterface */
    protected $securityContext;

    /** @var int */
    protected $scopeId;

    /**
     * Sets the security context
     *
     * @param TokenStorageInterface $securityContext
     */
    public function setSecurityContext(TokenStorageInterface $securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getScopedEntityName()
    {
        return 'organization';
    }

    /**
     * {@inheritdoc}
     */
    public function getScopeId()
    {
        $this->ensureScopeIdInitialized();

        return $this->scopeId;
    }

    /**
     * {@inheritdoc}
     */
    public function setScopeId($scopeId)
    {
        $this->scopeId = $scopeId;
    }

    /**
     * Makes sure that the scope id is set
     */
    protected function ensureScopeIdInitialized()
    {
        if (null === $this->scopeId) {
            $scopeId = 0;

            $token = $this->securityContext->getToken();
            if ($token instanceof OrganizationContextTokenInterface) {
                $organization = $token->getOrganizationContext();
                if ($organization instanceof Organization && $organization->getId()) {
                    $scopeId = $organization->getId();
                }
            }

            $this->scopeId = $scopeId;
        }
    }
}
