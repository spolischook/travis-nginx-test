<?php

namespace OroPro\Bundle\OrganizationBundle\Provider\Filter;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Provider\Filter\ChoiceTreeBusinessUnitProvider as BaseChoiceTreeBusinessUnitProvider;

class ChoiceTreeBusinessUnitProvider extends BaseChoiceTreeBusinessUnitProvider
{
    /**
     * @param BusinessUnit $businessUnit
     *
     * @return string
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
}
