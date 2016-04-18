<?php

namespace OroPro\Bundle\OrganizationBundle\Provider\Filter;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\OrganizationBundle\Provider\Filter\ChoiceTreeBusinessUnitProvider as BaseChoiceTreeBusinessUnitProvider;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;

class ChoiceTreeBusinessUnitProvider extends BaseChoiceTreeBusinessUnitProvider
{
    /**
     * {@inheritdoc}
     */
    protected function addBusinessUnitName(QueryBuilder $qb)
    {
        $currentOrganization = $this->securityFacade->getOrganization();
        if (!$currentOrganization || !$currentOrganization->getIsGlobal()) {
            parent::addBusinessUnitName($qb);

            return;
        }

        $qb
            ->addSelect(<<<'DQL'
                CASE WHEN o.id IS NOT NULL
                    THEN businessUnit.name
                    ELSE CONCAT(businessUnit.name, ' (', org.name, ')')
                END AS name
DQL
            )
            ->leftJoin('businessUnit.organization', 'org')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function getBusinessUnitIds()
    {
        $user = $this->getUser();
        /** @var OwnerTree $tree */
        $tree = $this->treeProvider->getTree();

        return $tree->getBusinessUnitsIdByUserOrganizations($user->getId());
    }
}
