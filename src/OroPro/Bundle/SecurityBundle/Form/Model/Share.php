<?php

namespace OroPro\Bundle\SecurityBundle\Form\Model;

use Oro\Bundle\SecurityBundle\Form\Model\Share as BaseModel;

class Share extends BaseModel
{
    /** @var array */
    protected $organizations = [];

    /**
     * Returns array of organizations
     *
     * @return array
     */
    public function getOrganizations()
    {
        return $this->organizations;
    }

    /**
     * Sets array of organizations
     *
     * @param array $organizations
     *
     * @return self
     */
    public function setOrganizations(array $organizations)
    {
        $this->organizations = $organizations;

        return $this;
    }
}
