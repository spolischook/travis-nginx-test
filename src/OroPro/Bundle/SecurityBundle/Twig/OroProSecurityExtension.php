<?php

namespace OroPro\Bundle\SecurityBundle\Twig;

use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

use Oro\Bundle\SecurityBundle\Acl\Domain\BusinessUnitSecurityIdentity;
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

    /**
     * {@inheritdoc}
     */
    protected function compareEntries(Entry $entryA, Entry $entryB)
    {
        $result = parent::compareEntries($entryA, $entryB);

        $sidA = $entryA->getSecurityIdentity();
        $sidB = $entryB->getSecurityIdentity();
        if ($sidA instanceof OrganizationSecurityIdentity && $sidB instanceof OrganizationSecurityIdentity) {
            $idA = (int) $sidA->getId();
            $idB = (int) $sidB->getId();

            return $idA < $idB ? -1 : 1;
        } elseif ($sidA instanceof OrganizationSecurityIdentity &&
            ($sidB instanceof UserSecurityIdentity || $sidB instanceof BusinessUnitSecurityIdentity)
        ) {
            return -1;
        } elseif ($sidA instanceof UserSecurityIdentity && $sidB instanceof OrganizationSecurityIdentity) {
            return 1;
        } elseif ($sidA instanceof BusinessUnitSecurityIdentity && $sidB instanceof OrganizationSecurityIdentity) {
            return 1;
        }

        return $result;
    }
}
