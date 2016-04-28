<?php

namespace OroCRM\Bundle\ChannelBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroCRM\Bundle\ChannelBundle\Entity\Channel;
use OroCRM\Bundle\ChannelBundle\Entity\Repository\ChannelRepository;

class ChannelsByEntitiesProvider
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var ChannelRepository
     */
    protected $channelRepository;

    /**
     * @var array [{parameters_hash} => {Channel[]}, ...]
     */
    protected $channelsCache = [];

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param array $entities
     * @param bool  $status
     *
     * @return mixed
     */
    public function getChannelsByEntities(array $entities = [], $status = Channel::STATUS_ACTIVE)
    {
        sort($entities);
        $hash = md5(serialize([$entities, $status]));
        if (empty($this->channelsCache[$hash])) {
            $this->channelsCache[$hash] = $this->getChannelRepository()->getChannelsByEntities($entities, $status);
        }

        return $this->channelsCache[$hash];
    }

    /**
     * @return ChannelRepository
     */
    protected function getChannelRepository()
    {
        if (null === $this->channelRepository) {
            $this->channelRepository = $this->doctrineHelper
                ->getEntityRepositoryForClass('OroCRMChannelBundle:Channel');
        }

        return $this->channelRepository;
    }
}
