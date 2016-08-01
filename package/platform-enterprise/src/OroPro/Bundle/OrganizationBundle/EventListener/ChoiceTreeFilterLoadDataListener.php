<?php

namespace OroPro\Bundle\OrganizationBundle\EventListener;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\EventListener\ChoiceTreeFilterLoadDataListener as
    BaseChoiceTreeFilterLoadDataListener;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class ChoiceTreeFilterLoadDataListener extends BaseChoiceTreeFilterLoadDataListener
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function setSecurityFacade(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

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
            if ($this->securityFacade->getOrganization()->getIsGlobal()) {
                array_unshift($path, ['name'=> $businessUnit->getOrganization()->getName()]);
            }
        }

        return $path;
    }
}
