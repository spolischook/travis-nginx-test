<?php

namespace OroCRM\Bundle\CallBundle\Provider;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\ActivityListBundle\Entity\ActivityOwner;
use Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface;
use Oro\Bundle\CommentBundle\Model\CommentProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

use OroCRM\Bundle\CallBundle\Entity\Call;

class CallActivityListProvider implements
    ActivityListProviderInterface,
    CommentProviderInterface,
    ActivityListDateProviderInterface
{
    const ACTIVITY_CLASS = 'OroCRM\Bundle\CallBundle\Entity\Call';
    const ACL_CLASS = 'OroCRM\Bundle\CallBundle\Entity\Call';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ServiceLink */
    protected $entityOwnerAccessorLink;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ServiceLink    $entityOwnerAccessorLink
     */
    public function __construct(DoctrineHelper $doctrineHelper, ServiceLink $entityOwnerAccessorLink)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityOwnerAccessorLink = $entityOwnerAccessorLink;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicableTarget(ConfigIdInterface $configId, ConfigManager $configManager)
    {
        $provider = $configManager->getProvider('activity');
        return $provider->hasConfigById($configId)
            && $provider->getConfigById($configId)->has('activities')
            && in_array(self::ACTIVITY_CLASS, $provider->getConfigById($configId)->get('activities'));
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject($entity)
    {
        /** @var $entity Call */
        return $entity->getSubject();
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription($entity)
    {
        /** @var $entity Call */
        return $entity->getNotes();
    }

    /**
     * {@inheritdoc}
     */
    public function getOwner($entity)
    {
        /** @var $entity Call */
        return $entity->getOwner();
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt($entity)
    {
        /** @var $entity Call */
        return $entity->getCreatedAt();
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatedAt($entity)
    {
        /** @var $entity Call */
        return $entity->getUpdatedAt();
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ActivityList $activityListEntity)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganization($activityEntity)
    {
        /** @var $activityEntity Call */
        return $activityEntity->getOrganization();
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'OroCRMCallBundle:Call:js/activityItemTemplate.js.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes()
    {
        return [
            'itemView'   => 'orocrm_call_widget_info',
            'itemEdit'   => 'orocrm_call_update',
            'itemDelete' => 'oro_api_delete_call'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityClass()
    {
        return self::ACTIVITY_CLASS;
    }

    /**
     * {@inheritdoc}
     */
    public function getAclClass()
    {
        return self::ACL_CLASS;
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityId($entity)
    {
        return $this->doctrineHelper->getSingleEntityIdentifier($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable($entity)
    {
        if (is_object($entity)) {
            $entity = $this->doctrineHelper->getEntityClass($entity);
        }

        return $entity == self::ACTIVITY_CLASS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetEntities($entity)
    {
        return $entity->getActivityTargetEntities();
    }

    /**
     * {@inheritdoc}
     */
    public function hasComments(ConfigManager $configManager, $entity)
    {
        $config = $configManager->getProvider('comment')->getConfig($entity);

        return $config->is('enabled');
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityOwners($entity, ActivityList $activityList)
    {
        $organization = $this->getOrganization($entity);
        $owner = $this->entityOwnerAccessorLink->getService()->getOwner($entity);

        if (!$organization || !$owner) {
            return [];
        }

        $activityOwner = new ActivityOwner();
        $activityOwner->setActivity($activityList);
        $activityOwner->setOrganization($organization);
        $activityOwner->setUser($owner);
        return [$activityOwner];
    }
}
