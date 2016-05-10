<?php

namespace Oro\Bundle\TestGeneratorBundle\Tests\Unit\Command;

use Oro\Bundle\TestGeneratorBundle\Command\CreateTestCommand;

class CreateTestCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $name;

    /**
     * @var CreateTestCommand
     */
    protected $createTestCommand;

    protected function setUp()
    {
        $this->createTestCommand = new CreateTestCommand($this->name);
    }
}
