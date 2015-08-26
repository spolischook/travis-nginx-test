<?php

namespace OroPro\Bundle\SecurityBundle\Extension;

use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\SecurityBundle\Extension\ShareDatasource as BaseDatasource;

use OroPro\Bundle\SecurityBundle\Acl\Domain\OrganizationSecurityIdentity;

class ShareDatasource extends BaseDatasource
{
    /**
     * {@inheritDoc}
     */
    public function getResults()
    {
        $rows = parent::getResults();

        $objectIdentity = ObjectIdentity::fromDomainObject($this->object);
        try {
            $acl = $this->aclProvider->findAcl($objectIdentity);
        } catch (AclNotFoundException $e) {
            // no ACL found, do nothing
            $acl = null;
        }
        if ($acl) {
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
                $className = 'Oro\Bundle\OrganizationBundle\Entity\Organization';
                $entityConfigId = new EntityConfigId('entity', $className);
                $classLabel = $this->translator->trans($this->configManager->getConfig($entityConfigId)->get('label'));
                foreach ($organizations as $organization) {
                    /* @var $organization Organization */
                    $details = $classLabel;
                    array_unshift(
                        $rows,
                        new ResultRecord(
                            [
                                'id' => json_encode([
                                    'entityId' => $organization->getId(),
                                    'entityClass' => $className,
                                ]),
                                'entity' => [
                                    'id' => $organization->getId(),
                                    'label' => $organization->getName(),
                                    'details' => $details,
                                    'image' => 'avatar-organization-xsmall.png',
                                ],
                            ]
                        )
                    );
                }
            }
        }

        return $rows;
    }
}
