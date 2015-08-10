<?php

namespace OroPro\Bundle\OrganizationBundle\Provider;

use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper as BaseEmailRecipientsHelper;
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
        if (!$this->isCurrentOrganizationGlobal() ||
            !$recipient->getEntity() ||
            !$recipient->getEntity()->getOrganization()
        ) {
            $result['text'] = $recipient->getName();
        }

        return $result;
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
