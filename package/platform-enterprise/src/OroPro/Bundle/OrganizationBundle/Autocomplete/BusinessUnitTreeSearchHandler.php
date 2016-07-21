<?php

namespace OroPro\Bundle\OrganizationBundle\Autocomplete;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Autocomplete\BusinessUnitTreeSearchHandler as BaseBusinessUnitTreeSearchHandler;

class BusinessUnitTreeSearchHandler extends BaseBusinessUnitTreeSearchHandler
{
    /**
     * @param BusinessUnit $businessUnit
     * @param $path
     *
     * @return mixed
     */
    protected function getPath($businessUnit, $path)
    {
        array_unshift($path, ['name'=> $businessUnit->getName()]);

        $owner = $businessUnit->getOwner();
        if ($owner) {
            $path = $this->getPath($owner, $path);
        } else {
            $organization = $this->securityFacade->getOrganization();
            if ($organization && $organization->getIsGlobal()) {
                array_unshift($path, ['name'=> $businessUnit->getOrganization()->getName()]);
            }
        }
        
        return $path;
    }
}
