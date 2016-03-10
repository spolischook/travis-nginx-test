<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Twig;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\ActionBundle\Twig\ActionExtension;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ActionExtensionTest extends \PHPUnit_Framework_TestCase
{
    const ROUTE = 'test_route';
    const REQUEST_URI = '/test/request/uri';

    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionManager */
    protected $actionManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ApplicationsHelper */
    protected $appsHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var ActionExtension */
    protected $extension;

    /** @var  ContextHelper */
    protected $contextHelper;

    protected function setUp()
    {
        $this->actionManager = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->appsHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ApplicationsHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ContextHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new ActionExtension(
            $this->actionManager,
            $this->appsHelper,
            $this->doctrineHelper,
            $this->contextHelper
        );
    }

    protected function tearDown()
    {
        unset($this->extension, $this->actionManager, $this->appsHelper, $this->doctrineHelper, $this->requestStack);
    }

    public function testGetName()
    {
        $this->assertEquals(ActionExtension::NAME, $this->extension->getName());
    }

    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();
        $this->assertCount(3, $functions);

        $expectedFunctions = [
            'oro_action_widget_parameters' => true,
            'oro_action_widget_route' => false,
            'has_actions' => false,
        ];

        /** @var \Twig_SimpleFunction $function */
        foreach ($functions as $function) {
            $this->assertInstanceOf('\Twig_SimpleFunction', $function);
            $this->assertArrayHasKey($function->getName(), $expectedFunctions);
            $this->assertEquals($expectedFunctions[$function->getName()], $function->needsContext());
        }
    }

    public function testGetWidgetRoute()
    {
        $this->appsHelper->expects($this->once())
            ->method('getWidgetRoute')
            ->withAnyParameters()
            ->willReturn('test_route');

        $this->assertSame('test_route', $this->extension->getWidgetRoute());
    }

    /**
     * @dataProvider hasActionsDataProvider
     *
     * @param bool $result
     */
    public function testHasActions($result)
    {
        $params = ['test_param' => 'test_param_value'];

        $this->actionManager->expects($this->once())
            ->method('hasActions')
            ->with($params)
            ->willReturn($result);

        $this->assertEquals($result, $this->extension->hasActions($params));
    }

    /**
     * @return array
     */
    public function hasActionsDataProvider()
    {
        return [
            [true],
            [false],
        ];
    }
}
