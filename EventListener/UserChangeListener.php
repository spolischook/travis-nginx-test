<?php

namespace Oro\Bundle\LDAPBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\SyncScheduler;
use Oro\Bundle\LDAPBundle\ImportExport\Utils\LdapUtils;
use Oro\Bundle\LDAPBundle\Provider\ChannelType;
use Oro\Bundle\LDAPBundle\Provider\Connector\UserLdapConnector;
use Oro\Bundle\UserBundle\Entity\User;

class UserChangeListener
{
    /** @var Channel[] */
    protected $channels = null;
    /** @var User[] */
    protected $newUsers = [];
    /** @var User[] */
    protected $updatedUsers = [];
    /** @var integer[] */
    protected $scheduledUserIds = [];
    /** @var ServiceLink */
    private $syncShedulerLink;
    /** @var ServiceLink */
    private $registryLink;

    /**
     * @param ServiceLink   $registry
     * @param ServiceLink            $syncShedulerLink Service link with SyncScheduler
     *
     * @internal param ServiceLink $managerProviderLink Service link with ChannelManagerProvider
     */
    public function __construct(
        ServiceLink $registry,
        ServiceLink $syncShedulerLink
    )
    {
        $this->registryLink = $registry;
        $this->syncShedulerLink = $syncShedulerLink;
    }

    /**
     * Processes new entities.
     */
    protected function processNew()
    {
        if (empty($this->newUsers)) {
            return;
        }

        $channels = $this->getEnabledChannels();

        $ids = [];

        foreach ($this->newUsers as $user) {
            $ids[] = $user->getId();
            $distinguishedNames = [];
            foreach($channels as $channel) {
                if ($this->isTwoWaySyncEnabled($channel)) {
                    $distinguishedNames[$channel->getId()] = LdapUtils::createDn(
                        $channel->getMappingSettings()->offsetGet('userMapping')['username'],
                        $user->getUsername(),
                        $channel->getMappingSettings()->offsetGet('exportUserBaseDn')
                    );
                }
            }
            $user->setLdapDistinguishedNames($distinguishedNames);
        }

        foreach ($channels as $channel) {
            if (!empty($ids) && $this->isTwoWaySyncEnabled($channel)) {
                $this->scheduledUserIds[$channel->getId()] = $ids;
            }
        }

        $this->newUsers = [];
    }

    /**
     * Processes updated entities.
     *
     * @param UnitOfWork $uow
     */
    protected function processUpdated(UnitOfWork $uow)
    {
        if (empty($this->updatedUsers)) {
            return;
        }

        $channels = $this->getEnabledChannels();

        foreach ($this->updatedUsers as $user) {
            $mappings = (array)$user->getLdapDistinguishedNames();

            foreach ($mappings as $channelId => $dn) {
                $changedFields = $uow->getEntityChangeSet($user);
                $channel = $channels[$channelId];
                $mappedFields = $this->getMappedFields($channel);
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

    /**
     * Schedules export job for updated/new entities.
     */
    protected function exportScheduled()
    {
        if (empty($this->scheduledUserIds)) {
            return;
        }

        /** @var SyncScheduler $syncScheduler */
        $syncScheduler = $this->syncShedulerLink->getService();
        $channels = $this->getEnabledChannels();

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

    private function getEnabledChannels()
    {
        if ($this->channels === null) {
            $channels = $this->registryLink->getService()
                ->getRepository('OroIntegrationBundle:Channel')
                ->findBy(['type' => ChannelType::TYPE, 'enabled' => true]);
            foreach($channels as $channel) {
                $this->channels[$channel->getId()] = $channel;
            }
        }
        return $this->channels;
    }

    private function getMappedFields(Channel $channel)
    {
        return array_keys(array_filter($channel->getMappingSettings()->offsetGet('userMapping')));
    }
}
