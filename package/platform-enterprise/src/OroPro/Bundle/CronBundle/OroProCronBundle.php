<?php

namespace OroPro\Bundle\CronBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroProCronBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'OroCronBundle';
    }
}
