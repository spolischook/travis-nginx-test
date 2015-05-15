<?php
namespace OroCRMPro\Bundle\DemoDataBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;

class ActivityListSubscriber implements EventSubscriber
{
    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        // @codingStandardsIgnoreStart
        return [Events::prePersist];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $entityManager = $args->getEntityManager();
        $unitOfWork    = $entityManager->getUnitOfWork();
        $entity        = $args->getEntity();

        /** @var ActivityList $entity */
        if ($entity instanceof ActivityList) {
            $identityMap  = $unitOfWork->getIdentityMap();
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
