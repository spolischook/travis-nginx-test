<?php

namespace OroPro\Bundle\SecurityBundle\Acl\Extension;

interface AceShareDecisionInterface
{
    /**
     * Return TRUE if decision maker decided that entity is shared
     *
     * @return bool
     */
    public function isEntityShared();
}
