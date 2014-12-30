<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\ContentProvider;

use OroPro\Bundle\OrganizationBundle\ContentProvider\OrganizationSwitchContentProvider;

class OrganizationSwitchContentProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Twig_Environment|\PHPUnit_Framework_MockObject_MockObject */
    protected $twig;

    /** @var OrganizationSwitchContentProvider */
    protected $provider;

    protected function setUp()
    {
        $this->twig     = $this->getMockBuilder('\Twig_Environment')->disableOriginalConstructor()->getMock();
        $this->provider = new OrganizationSwitchContentProvider($this->twig);
    }

    protected function tearDown()
    {
        unset($this->provider, $this->twig);
    }

    public function testGetName()
    {
        $this->assertEquals('organization_switch', $this->provider->getName());
    }

    public function testGetContent()
    {
        $testContent = uniqid('content', true);

        $extensionMock = $this->getMockBuilder('Oro\Bundle\UIBundle\Twig\PlaceholderExtension')
            ->disableOriginalConstructor()->getMock();

        $this->twig->expects($this->once())->method('getExtension')->with('oro_placeholder')
            ->willReturn($extensionMock);
        $extensionMock->expects($this->once())->method('renderPlaceholder')->with('organization_selector')
            ->willReturn($testContent);

        $this->assertEquals($testContent, $this->provider->getContent());
    }
}
