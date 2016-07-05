<?php

namespace OroCRM\Bundle\MagentoBundle\Tests\Unit\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\SearchBundle\Command\ReindexCommand;
use OroCRM\Bundle\ChannelBundle\Event\ChannelDeleteEvent;
use OroCRM\Bundle\MagentoBundle\EventListener\ChannelDeleteListener;
use OroCRM\Bundle\ChannelBundle\Entity\Channel;

class ChannelDeleteListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|Registry */
    private $doctrine;

    /** @var ChannelDeleteListener */
    private $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()->getMock();

        $this->doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()->getMock();

        $this->listener = new ChannelDeleteListener($this->doctrine);
    }

    public function testIncorrectChannelType()
    {
        $channel = new Channel();
        $channel->setChannelType('testChannel');
        $event = new ChannelDeleteEvent($channel);

        $this->doctrine->expects(self::never())->method('getManager');
        $this->objectManager->expects(self::never())->method('persist');
        $this->objectManager->expects(self::never())->method('flush');

        $this->listener->onDelete($event);
    }

    public function testCreateJob()
    {
        $channel = new Channel();
        $channel->setChannelType('magento');
        $event = new ChannelDeleteEvent($channel);

        $this->doctrine->expects(self::once())->method('getManager')->willReturn($this->objectManager);
        $this->objectManager->expects(self::exactly(3))->method('persist')->withConsecutive(
            [new Job(ReindexCommand::COMMAND_NAME, ['OroCRM\Bundle\MagentoBundle\Entity\Cart'])],
            [new Job(ReindexCommand::COMMAND_NAME, ['OroCRM\Bundle\MagentoBundle\Entity\Order'])],
            [new Job(ReindexCommand::COMMAND_NAME, ['OroCRM\Bundle\MagentoBundle\Entity\Customer'])]
        );
        $this->objectManager->expects(self::once())->method('flush');

        $this->listener->onDelete($event);
    }
}
