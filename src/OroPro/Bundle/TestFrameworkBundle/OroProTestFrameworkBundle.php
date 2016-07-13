<?php

namespace OroPro\Bundle\TestFrameworkBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroProTestFrameworkBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'OroTestFrameworkBundle';
    }
}
