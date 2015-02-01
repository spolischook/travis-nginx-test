<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Config;

use Oro\Bundle\ConfigBundle\Config\AbstractScopeManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class OrganizationScopeManager extends AbstractScopeManager
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var int */
    protected $scopeId;

    /**
     * {@inheritdoc}
     */
    public function getSettingValue($name, $full = false)
    {
        if (is_null($this->scopeId) || $this->scopeId == 0) {
            $this->setScopeId();
        }

        return parent::getSettingValue($name, $full);
    }

    /**
     * @param SecurityFacade $securityFacade
     */
    public function setSecurity(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param null $scopeId
     * @return $this
     */
    public function setScopeId($scopeId = null)
    {
        if (is_null($scopeId)) {
            $scopeId = $this->securityFacade->getOrganizationId() ? : 0;
        }

        $this->scopeId = $scopeId;
        $this->loadStoredSettings($this->getScopedEntityName(), $this->scopeId);

        return $this;
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
        return $this->scopeId;
    }
}
