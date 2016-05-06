<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Unit\EventListener;

use Oro\Component\DependencyInjection\ServiceLink;

use Doctrine\ORM\Event\LifecycleEventArgs;

use OroB2B\Bundle\MenuBundle\EventListener\LocaleListener;

class LocaleListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ServiceLink|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $menuProviderLink;

    /**
     * @var LocaleListener
     */
    protected $localeListener;

    protected function setUp()
    {
        $this->menuProviderLink = $this->getMockBuilder('Oro\Component\DependencyInjection\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeListener = new LocaleListener($this->menuProviderLink);
    }

    public function testPostPersist()
    {
        /** @var LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject $args **/
        $args = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeListener->postPersist($args);
    }

    public function testPostUpdate()
    {
        /** @var LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject $args **/
        $args = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeListener->postUpdate($args);
    }

    public function testPostRemove()
    {
        /** @var LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject $args **/
        $args = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeListener->postRemove($args);
    }
}
