<?php

namespace OroPro\Bundle\SecurityBundle\Form\Model;

use Oro\Bundle\SecurityBundle\Form\Model\Factory as BaseFactory;

class Factory extends BaseFactory
{
    /**
     * @return Share
     */
    public function getShare()
    {
        return new Share();
    }
}
