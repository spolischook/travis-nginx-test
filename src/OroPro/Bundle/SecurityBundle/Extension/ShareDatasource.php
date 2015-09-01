<?php

namespace OroPro\Bundle\SecurityBundle\Extension;

use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\SecurityBundle\Extension\ShareDatasource as BaseDatasource;

use OroPro\Bundle\SecurityBundle\Acl\Domain\OrganizationSecurityIdentity;

class ShareDatasource extends BaseDatasource
{
    /**
     * {@inheritDoc}
     */
    protected function getObjects()
    {
        $objects = parent::getObjects();
        $objectIdentity = ObjectIdentity::fromDomainObject($this->object);
        try {
            $acl = $this->aclProvider->findAcl($objectIdentity);
        } catch (AclNotFoundException $e) {
            // no ACL found, do nothing
            $acl = null;
        }

        if (!$acl) {
            return $objects;
        }

        $orgIds = [];
        foreach ($acl->getObjectAces() as $ace) {
            /** @var $ace Entry */
            $securityIdentity = $ace->getSecurityIdentity();
            if ($securityIdentity instanceof OrganizationSecurityIdentity) {
                $orgIds[] = $securityIdentity->getId();
            }
        }
        if ($orgIds) {
            /** @var $repo OrganizationRepository */
            $repo = $this->objectManager->getRepository('OroOrganizationBundle:Organization');
            $organizations = $repo->getEnabledOrganizations($orgIds);
            $objects = array_merge($organizations, $objects);
        }

        return $objects;
    }
}
