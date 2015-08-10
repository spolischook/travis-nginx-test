<?php

namespace OroPro\Bundle\SecurityBundle\Twig;

use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

use Oro\Bundle\SecurityBundle\Twig\OroSecurityExtension as BaseExtension;

use OroPro\Bundle\SecurityBundle\Acl\Domain\OrganizationSecurityIdentity;

class OroProSecurityExtension extends BaseExtension
{
    /**
     * @param SecurityIdentityInterface $sid
     *
     * @return string
     */
    protected function getFormattedName(SecurityIdentityInterface $sid)
    {
        $result = parent::getFormattedName($sid);

        if (!$result && $sid instanceof OrganizationSecurityIdentity) {
            $organization = $this->manager->getRepository('OroOrganizationBundle:Organization')->find($sid->getId());
            if ($organization) {
                return $organization->getName();
            }
        }

        return $result;
    }
}
