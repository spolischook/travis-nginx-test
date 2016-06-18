<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get;

use Oro\Bundle\ApiBundle\Config\CustomizeLoadedDataConfigExtra;
use Oro\Bundle\ApiBundle\Config\DataTransformersConfigExtra;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Get\InitializeConfigExtras;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigExtra;

class InitializeConfigExtrasTest extends GetProcessorTestCase
{
    /** @var InitializeConfigExtras */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new InitializeConfigExtras();
    }

    public function testProcess()
    {
        $this->context->setConfigExtras([]);

        $existingExtra = new TestConfigExtra('test');
        $this->context->addConfigExtra($existingExtra);

        $this->processor->process($this->context);

        $this->assertEquals(
            [
                new TestConfigExtra('test'),
                new EntityDefinitionConfigExtra($this->context->getAction()),
                new CustomizeLoadedDataConfigExtra(),
                new DataTransformersConfigExtra(),
                new FiltersConfigExtra()
            ],
            $this->context->getConfigExtras()
        );
    }
}
