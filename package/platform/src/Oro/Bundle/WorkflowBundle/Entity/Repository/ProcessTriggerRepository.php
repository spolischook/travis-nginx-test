<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;

class ProcessTriggerRepository extends EntityRepository
{
    /**
     * @param ProcessTrigger $trigger
     * @return null|ProcessTrigger
     */
    public function findEqualTrigger(ProcessTrigger $trigger)
    {
        return $this->findOneBy(
            array(
                'event' => $trigger->getEvent(),
                'field' => $trigger->getField(),
                'definition' => $trigger->getDefinition(),
            )
        );
    }

    /**
     * @param ProcessTrigger $trigger
     * @return bool
     */
    public function isEqualTriggerExists(ProcessTrigger $trigger)
    {
        $equalTrigger = $this->findEqualTrigger($trigger);

        return !empty($equalTrigger);
    }

    /**
     * @param bool|null $enabled
     * @return ProcessTrigger[]
     */
    public function findAllWithDefinitions($enabled = null)
    {
        $queryBuilder = $this->createQueryBuilder('trigger')
            ->select('trigger, definition')
            ->innerJoin('trigger.definition', 'definition')
            ->orderBy('definition.executionOrder');

        if (null !== $enabled) {
            $queryBuilder->andWhere('definition.enabled = :enabled')->setParameter('enabled', $enabled);
        }

        return $queryBuilder->getQuery()->execute();
    }
}
