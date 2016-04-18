<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization as ParentOrganization;

class Organization extends ParentOrganization
{
    protected $id;

    protected $global;

    public function __construct($id = 0)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getIsGlobal()
    {
        return $this->global;
    }
    public function setIsGlobal($value)
    {
        $this->global = $value;
    }
}
