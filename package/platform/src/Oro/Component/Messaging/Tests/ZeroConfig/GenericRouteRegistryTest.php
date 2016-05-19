<?php
namespace Oro\Component\Messaging\Tests\ZeroConfig;

use Oro\Component\Messaging\ZeroConfig\GenericRouteRegistry;
use Oro\Component\Messaging\ZeroConfig\Route;

class GenericRouteRegistryTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithoutAttributes()
    {
        new GenericRouteRegistry();
    }

    public function testCouldAddRoute()
    {
        $registry = new GenericRouteRegistry();

        // guard
        $this->assertEmpty($registry->getRoutes('message'));

        $route = new Route();
        $route->setMessageName('message');

        // test
        $registry->addRoute($route);

        $this->assertCount(1, $registry->getRoutes('message'));
        $this->assertSame($route, $registry->getRoutes('message')[0]);
    }

    public function testCouldSetManyRoutes()
    {
        $registry = new GenericRouteRegistry();

        // guard
        $this->assertEmpty($registry->getRoutes('message'));

        $route1 = new Route();
        $route1->setMessageName('message');
        $route2 = new Route();
        $route2->setMessageName('message');

        // test
        $registry->setRoutes([$route1, $route2]);

        $this->assertCount(2, $registry->getRoutes('message'));
        $this->assertSame($route1, $registry->getRoutes('message')[0]);
        $this->assertSame($route2, $registry->getRoutes('message')[1]);
    }
}
