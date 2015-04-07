<?php
namespace OroCRMPro\Bundle\DemoDataBundle\EventListener;

use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;

class ActivityListSubscriber implements EventSubscriber
{
    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            Events::prePersist
        ];
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $entityManager = $args->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        $entity = $args->getEntity();

        /** @var ActivityList $entity */
        if ($entity instanceof ActivityList) {
            $identityMap = $unitOfWork->getIdentityMap();
            $relatedClass = $entity->getRelatedActivityClass();
            foreach ($identityMap[$relatedClass] as $activity) {
                if ($activity->getId() == $entity->getRelatedActivityId()) {
                    if (method_exists($activity, 'getCreatedAt')) {
                        $entity->setCreatedAt($activity->getCreatedAt());
                    }
                    if (method_exists($activity, 'getUpdatedAt')) {
                        $entity->setUpdatedAt($activity->getUpdatedAt());
                    }
                    break;
                }
            }
        }
    }
}
