<?php

namespace OroPro\Bundle\EmailBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroProEmailBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'OroEmailBundle';
    }
}
