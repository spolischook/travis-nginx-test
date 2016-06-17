<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Exception;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use OroPro\Bundle\OrganizationBundle\Exception\OrganizationAwareException;
use OroPro\Bundle\OrganizationBundle\Exception\OrganizationAwareExceptionListener;

class OrganizationAwareExceptionListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var OrganizationAwareExceptionListener */
    protected $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    public function setUp()
    {
        $this->router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new OrganizationAwareExceptionListener($this->router);
    }

    /**
     * @dataProvider onKernelProvider
     *
     * @param object $exception
     * @param bool $isRedirect
     */
    public function testOnKernelException($exception, $isRedirect)
    {
        $request = new Request();
        $kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\Kernel')
            ->disableOriginalConstructor()
            ->getMock();
        $event = new GetResponseForExceptionEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $exception);
        $this->router->expects($isRedirect ? $this->once() : $this->never())
            ->method('generate')
            ->willReturn('http://localhost');

        $this->listener->onKernelException($event);

        if ($isRedirect) {
            $response = $event->getResponse();
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        } else {
            $this->assertNull($event->getResponse());
        }
    }

    public function onKernelProvider()
    {
        return [
            [new \Exception(), false],
            [new OrganizationAwareException(), true]
        ];
    }
}
