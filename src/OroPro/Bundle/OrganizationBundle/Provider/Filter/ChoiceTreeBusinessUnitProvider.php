<?php

namespace OroPro\Bundle\OrganizationBundle\Provider\Filter;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Provider\Filter\ChoiceTreeBusinessUnitProvider as BaseChoiceTreeBusinessUnitProvider;

class ChoiceTreeBusinessUnitProvider extends BaseChoiceTreeBusinessUnitProvider
{
    /**
     * @param BusinessUnit $rootBusinessUnit
     *
     * @return string
     */
    protected function getBusinessUnitName(BusinessUnit $rootBusinessUnit)
    {
        $currentOrganization = $this->securityFacade->getOrganization();
        if ($currentOrganization && $currentOrganization->getIsGlobal()) {
            $name = sprintf(
                '%s (%s)',
                $rootBusinessUnit->getName(),
                $rootBusinessUnit->getOrganization()->getName()
            );
        } else {
            $name = $rootBusinessUnit->getName();
        }

        return  $name;
    }
}
