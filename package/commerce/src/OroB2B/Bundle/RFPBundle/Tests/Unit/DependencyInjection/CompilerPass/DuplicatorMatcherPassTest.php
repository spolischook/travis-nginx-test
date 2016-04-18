<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\DependencyInjection\CompilerPass;

use OroB2B\Bundle\RFPBundle\DependencyInjection\CompilerPass\DuplicatorMatcherPass;

class DuplicatorMatcherPassTest extends AbstractDuplicatorPassTest
{
    public function setUp()
    {
        $this->compilerPass = new DuplicatorMatcherPass();
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getServiceId()
    {
        return DuplicatorMatcherPass::FACTORY_SERVICE_ID;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTag()
    {
        return DuplicatorMatcherPass::TAG_NAME;
    }
}
