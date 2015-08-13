<?php

namespace OroPro\Bundle\OrganizationBundle\Provider;

use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper as BaseEmailRecipientsHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class EmailRecipientsHelper extends BaseEmailRecipientsHelper
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * {@inheritdoc}
     */
    public function createRecipientData(Recipient $recipient)
    {
        $result = parent::createRecipientData($recipient);
        if ($this->isCurrentOrganizationGlobal()) {
            $result['text'] = $recipient->getLabel();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isObjectAllowedForOrganization($object = null, Organization $organization = null)
    {
        $result = parent::isObjectAllowedForOrganization($object, $organization);
        if ($organization ||
            $this->isCurrentOrganizationGlobal() ||
            !$this->getPropertyAccessor()->isReadable($object, static::ORGANIZATION_PROPERTY)
        ) {
            return $result;
        }

        $currentOrganization = $this->securityFacade->getOrganization();
        $objectOrganization = $this->getPropertyAccessor()->getValue($object, static::ORGANIZATION_PROPERTY);
        if (!$objectOrganization) {
            return true;
        }

        return $objectOrganization === $currentOrganization;
    }

    /**
     * @return bool
     */
    protected function isCurrentOrganizationGlobal()
    {
        if (!$this->securityFacade) {
            return false;
        }

        $organization = $this->securityFacade->getOrganization();
        if (!$organization) {
            return false;
        }

        return $organization->getIsGlobal();
    }

    /**
     * @param SecurityFacade $securityFacade
     */
    public function setSecurityFacade(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }
}
