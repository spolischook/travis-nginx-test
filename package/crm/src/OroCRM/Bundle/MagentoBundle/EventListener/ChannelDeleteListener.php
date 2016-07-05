<?php

namespace OroCRM\Bundle\MagentoBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use JMS\JobQueueBundle\Entity\Job;

use Oro\Bundle\SearchBundle\Command\ReindexCommand;

use OroCRM\Bundle\ChannelBundle\Event\ChannelDeleteEvent;
use OroCRM\Bundle\MagentoBundle\Manager\MagentoDeleteProvider;

class ChannelDeleteListener
{
    /** @var  Registry */
    protected $doctrine;

    /**
     * ChannelDeleteListener constructor.
     *
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Handler event ChannelDeleteEvent
     *
     * @param ChannelDeleteEvent $event
     */
    public function onDelete(ChannelDeleteEvent $event)
    {
        $channel = $event->getChannel();
        
        if ($channel->getChannelType() === MagentoDeleteProvider::SUPPORTS_CHANNEL_TYPE) {
            $entityNameToReindex = $this->getEntityNameForReindex();
            $om = $this->doctrine->getManager();

            foreach ($entityNameToReindex as $entityName) {
                $job = new Job(ReindexCommand::COMMAND_NAME, [$entityName]);
                $om->persist($job);
            }

            $om->flush();
        }
    }

    /**
     * @return array
     */
    protected function getEntityNameForReindex()
    {
        return [
            'OroCRM\Bundle\MagentoBundle\Entity\Cart',
            'OroCRM\Bundle\MagentoBundle\Entity\Order',
            'OroCRM\Bundle\MagentoBundle\Entity\Customer'
        ];
    }
}
