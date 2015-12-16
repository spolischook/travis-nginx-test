<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Fixture;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class GlobalOrganization extends Organization
{
    /** @var bool */
    protected $isGlobal = true;

    /**
     * @return bool
     */
    public function getIsGlobal()
    {
        return $this->isGlobal;
    }

    /**
     * @param bool $isGlobal
     */
    public function setIsGlobal($isGlobal)
    {
        $this->isGlobal = $isGlobal;
    }
}
