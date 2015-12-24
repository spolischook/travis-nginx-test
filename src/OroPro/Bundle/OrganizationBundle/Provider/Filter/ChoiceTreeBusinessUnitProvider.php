<?php

namespace OroPro\Bundle\OrganizationBundle\Provider\Filter;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Provider\Filter\ChoiceTreeBusinessUnitProvider as BaseChoiceTreeBusinessUnitProvider;
use Oro\Bundle\SecurityBundle\Owner\OwnerTree;

class ChoiceTreeBusinessUnitProvider extends BaseChoiceTreeBusinessUnitProvider
{
    /**
     * {@inheritdoc}
     */
    protected function getBusinessUnitName(BusinessUnit $businessUnit)
    {
        $currentOrganization = $this->securityFacade->getOrganization();
        if ($currentOrganization && $currentOrganization->getIsGlobal()) {
            $name = sprintf(
                '%s (%s)',
                $businessUnit->getName(),
                $businessUnit->getOrganization()->getName()
            );
        } else {
            $name = $businessUnit->getName();
        }

        return  $name;
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
