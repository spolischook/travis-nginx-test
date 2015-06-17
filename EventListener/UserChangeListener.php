<?php

namespace Oro\Bundle\LDAPBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LDAPBundle\Provider\ChannelManagerProvider;
use Oro\Bundle\IntegrationBundle\Manager\SyncScheduler;
use Oro\Bundle\LDAPBundle\Provider\Connector\UserLdapConnector;
use Oro\Bundle\UserBundle\Entity\User;

class UserChangeListener
{
    /** @var ServiceLink */
    private $managerProviderLink;

    /** @var User[] */
    protected $newUsers = [];

    /** @var User[] */
    protected $updatedUsers = [];

    /** @var integer[] */
    protected $scheduledUserIds = [];

    /** @var ServiceLink */
    private $syncShedulerLink;

    /**
     * @param ServiceLink $managerProviderLink Service link with ChannelManagerProvider
     * @param ServiceLink $syncShedulerLink Service link with SyncScheduler
     */
    public function __construct(ServiceLink $managerProviderLink, ServiceLink $syncShedulerLink)
    {
        $this->managerProviderLink = $managerProviderLink;
        $this->syncShedulerLink = $syncShedulerLink;
    }

    protected function processNew()
    {
        /** @var ChannelManagerProvider $managerProvider */
        $managerProvider = $this->managerProviderLink->getService();

        $ids = [];

        foreach ($this->newUsers as $user) {
            $ids[] = $user->getId();
        }

        $channels = $managerProvider->getChannels();

        foreach ($channels as $channel) {
            if (!empty($ids) && $this->isTwoWaySyncEnabled($channel)) {
                $this->scheduledUserIds[$channel->getId()] = $ids;
            }
        }

        $this->newUsers = [];
    }

    /**
     * @param UnitOfWork $uow
     */
    protected function processUpdated(UnitOfWork $uow)
    {
        /** @var ChannelManagerProvider $provider */
        $provider = $this->managerProviderLink->getService();
        $channels = $provider->getChannels();

        foreach ($this->updatedUsers as $user) {
            $mappings = (array)$user->getLdapMappings();

            foreach ($mappings as $channelId => $dn) {
                $changedFields = $uow->getEntityChangeSet($user);
                $channel = $channels[$channelId];
                $mappedFields = $provider->channel($channel)->getSynchronizedFields();
                $common = array_intersect($mappedFields, array_keys($changedFields));

                if (!empty($common)) {
                    if (!isset($this->scheduledUserIds[$channelId])) {
                        $this->scheduledUserIds[$channelId] = [];
                    }
                    $this->scheduledUserIds[$channelId][] = $user->getId();
                }
            }
        }

        $this->updatedUsers = [];
    }

    public function exportScheduled()
    {
        /** @var ChannelManagerProvider $provider */
        $provider = $this->managerProviderLink->getService();
        /** @var SyncScheduler $syncScheduler */
        $syncScheduler = $this->syncShedulerLink->getService();
        $channels = $provider->getChannels();

        foreach ($channels as $channel) {
            if (isset($this->scheduledUserIds[$channel->getId()]) && $this->isTwoWaySyncEnabled($channel)) {
                $syncScheduler->schedule(
                    $channel,
                    UserLdapConnector::TYPE,
                    ['id' => $this->scheduledUserIds[$channel->getId()]]
                );
            }
        }

        $this->scheduledUserIds = [];
    }

    /**
     * Happens after entity gets flushed.
     *
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        $uow = $args->getEntityManager()->getUnitOfWork();

        $this->processNew();

        $this->processUpdated($uow);

        $this->exportScheduled();
    }

    /**
     * Happens before flush.
     *
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $uow = $args->getEntityManager()->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof User) {
                $this->newUsers[] = $entity;
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof User) {
                $this->updatedUsers[] = $entity;
            }
        }
    }

    private function isTwoWaySyncEnabled(Channel $channel)
    {
        return $channel->getSynchronizationSettings()->offsetGetOr('isTwoWaySyncEnabled', false);
    }
}
