<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Twig;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Helper\OptionsHelper;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\ActionBundle\Twig\ActionExtension;

class ActionExtensionTest extends \PHPUnit_Framework_TestCase
{
    const ROUTE = 'test_route';
    const REQUEST_URI = '/test/request/uri';

    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionManager */
    protected $actionManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ApplicationsHelper */
    protected $appsHelper;

    /** @var ActionExtension */
    protected $extension;

    /** @var ContextHelper */
    protected $contextHelper;

    /** @var OptionsHelper */
    protected $optionsHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->actionManager = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->appsHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ApplicationsHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ContextHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->optionsHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\OptionsHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new ActionExtension(
            $this->actionManager,
            $this->appsHelper,
            $this->contextHelper,
            $this->optionsHelper
        );
    }

    protected function tearDown()
    {
        unset($this->extension, $this->actionManager, $this->appsHelper, $this->contextHelper, $this->optionsHelper);
    }

    public function testGetName()
    {
        $this->assertEquals(ActionExtension::NAME, $this->extension->getName());
    }

    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();
        $this->assertCount(4, $functions);

        $expectedFunctions = [
            'oro_action_widget_parameters' => [
                true,
                'Oro\Bundle\ActionBundle\Helper\ContextHelper',
                'getActionParameters',
            ],
            'oro_action_widget_route' => [
                false,
                'Oro\Bundle\ActionBundle\Helper\ApplicationsHelper',
                'getWidgetRoute',
            ],
            'has_actions' => [
                false,
                'Oro\Bundle\ActionBundle\Model\ActionManager',
                'hasActions',
            ],
            'oro_action_frontend_options' => [
                false,
                'Oro\Bundle\ActionBundle\Helper\OptionsHelper',
                'getFrontendOptions',
            ],
        ];

        /** @var \Twig_SimpleFunction $function */
        foreach ($functions as $function) {
            $this->assertInstanceOf('\Twig_SimpleFunction', $function);
            $this->assertArrayHasKey($function->getName(), $expectedFunctions);

            $expectedFunction = $expectedFunctions[$function->getName()];
            $this->assertEquals($expectedFunction[0], $function->needsContext());

            $callable = $function->getCallable();

            $this->assertInstanceOf($expectedFunction[1], $callable[0]);
            $this->assertEquals($expectedFunction[2], $callable[1]);
        }
    }
}
