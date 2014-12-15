<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\Sync\Fixtures;

use OroPro\Bundle\EwsBundle\Sync\EwsEmailSynchronizer;

class TestEwsEmailSynchronizer extends EwsEmailSynchronizer
{
    public function callCheckConfiguration()
    {
        return $this->checkConfiguration();
    }

    public function callCreateSynchronizationProcessor($origin)
    {
        return $this->createSynchronizationProcessor($origin);
    }

    public function callInitializeOrigins()
    {
        $this->initializeOrigins();
    }
}
