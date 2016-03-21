<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Twig;

use Oro\Component\Layout\BlockView;

use Oro\Bundle\LayoutBundle\Twig\LayoutExtension;

class LayoutExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $renderer;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $textHelper;

    /** @var LayoutExtension */
    protected $extension;

    protected function setUp()
    {
        $this->renderer   = $this->getMock('Symfony\Bridge\Twig\Form\TwigRendererInterface');
        $this->textHelper = $this->getMockBuilder('Oro\Component\Layout\Templating\TextHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new LayoutExtension($this->renderer, $this->textHelper);
    }

    public function testGetName()
    {
        $this->assertEquals('layout', $this->extension->getName());
    }

    public function testInitRuntime()
    {
        $environment = $this->getMockBuilder('\Twig_Environment')
            ->getMock();

        $this->renderer->expects($this->once())
            ->method('setEnvironment')
            ->with($this->identicalTo($environment));

        $this->extension->initRuntime($environment);
    }

    public function testGetTokenParsers()
    {
        $tokenParsers = $this->extension->getTokenParsers();

        $this->assertCount(1, $tokenParsers);

        $this->assertInstanceOf(
            'Oro\Bundle\LayoutBundle\Twig\TokenParser\BlockThemeTokenParser',
            $tokenParsers[0]
        );
    }

    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();

        $this->assertCount(3, $functions);

        /** @var \Twig_SimpleFunction $function */
        $this->assertInstanceOf('Twig_SimpleFunction', $functions[0]);
        $function = $functions[0];
        $this->assertEquals('block_widget', $function->getName());
        $this->assertNull($function->getCallable());
        $this->assertEquals(LayoutExtension::RENDER_BLOCK_NODE_CLASS, $function->getNodeClass());
        $function = $functions[1];
        $this->assertEquals('block_label', $function->getName());
        $this->assertNull($function->getCallable());
        $this->assertEquals(LayoutExtension::RENDER_BLOCK_NODE_CLASS, $function->getNodeClass());
        $function = $functions[2];
        $this->assertEquals('block_row', $function->getName());
        $this->assertNull($function->getCallable());
        $this->assertEquals(LayoutExtension::RENDER_BLOCK_NODE_CLASS, $function->getNodeClass());
    }

    public function testGetFilters()
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(2, $filters);

        /** @var \Twig_SimpleFilter $blockTextFilter */
        $blockTextFilter = $filters[0];
        $this->assertInstanceOf('Twig_SimpleFilter', $blockTextFilter);
        $this->assertEquals('block_text', $blockTextFilter->getName());
        $this->assertEquals([$this->textHelper, 'processText'], $blockTextFilter->getCallable());

        /** @var \Twig_SimpleFilter $mergeContextFilter */
        $mergeContextFilter = $filters[1];
        $this->assertInstanceOf('Twig_SimpleFilter', $mergeContextFilter);
        $this->assertEquals('merge_context', $mergeContextFilter->getName());
        $this->assertEquals([$this->extension, 'mergeContext'], $mergeContextFilter->getCallable());
    }

    public function testMergeContext()
    {
        $parent = new BlockView();
        $firstChild = new BlockView();
        $secondChild = new BlockView();

        $parent->children['first'] = $firstChild;
        $parent->children['second'] = $secondChild;

        $name = 'name';
        $value = 'value';

        $this->assertEquals($parent, $this->extension->mergeContext($parent, [$name => $value]));

        /** @var BlockView $view */
        foreach ([$parent, $firstChild, $secondChild] as $view) {
            $this->assertArrayHasKey($name, $view->vars);
            $this->assertEquals($value, $view->vars[$name]);
        }
    }
}
