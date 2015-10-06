<?php

namespace OroPro\Bundle\OrganizationBundle\Entity\Manager;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager as BaseBusinessUnitManager;

class BusinessUnitManager extends BaseBusinessUnitManager
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
