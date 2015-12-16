<?php
namespace OroCRMPro\Bundle\DemoDataBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use OroCRM\Bundle\MagentoBundle\Entity\Cart;
use OroCRMPro\Bundle\DemoDataBundle\Exception\EntityNotFoundException;

class CartSubscriber implements EventSubscriber
{
    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        // @codingStandardsIgnoreStart
        return [Events::preUpdate];
        // @codingStandardsIgnoreEnd
    }

    /** @var EntityManager */
    protected $em;

    /** @var  WorkflowStep[] */
    protected $workflowSteps;

    /** @var  WorkflowDefinition */
    protected $definition;

    /** @var array */
    protected $statuses = [
        'open'                     => 'open',
        'expired'                  => 'abandoned',
        'lost'                     => 'abandoned',
        'purchased'                => 'converted',
        'converted_to_opportunity' => 'converted',
    ];

    /**
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $this->em = $args->getEntityManager();
        $entity   = $args->getEntity();

        /** @var Cart $entity */
        if ($entity instanceof Cart) {
            $workflowItem = $entity->getWorkflowItem();
            if ($workflowItem) {
                $step = $this->getWorkflowStep($entity->getStatus()->getName());
                $entity->setWorkflowStep($step);
                $workflowItem->setCurrentStep($step);
                $this->em->persist($workflowItem);
            }

        }
    }

    /**
     * @param $name
     * @return WorkflowStep
     * @throws EntityNotFoundException
     */
    protected function getWorkflowStep($name)
    {
        $workflowName = $this->getWorkflowStepName($name);

        if (!$this->workflowSteps || !$this->definition) {
            $this->definition    = $this->getCartWorkflowDefinition();
            $this->workflowSteps = $this->em->getRepository('OroWorkflowBundle:WorkflowStep')->findAll();
        }

        $steps = array_filter(
            $this->workflowSteps,
            function (WorkflowStep $workflowStep) use ($workflowName) {
                return (
                    $workflowStep->getName() == $workflowName
                    && $workflowStep->getDefinition() == $this->definition
                );
            }
        );

        $steps = array_values($steps);
        if (empty($steps)) {
            throw new EntityNotFoundException('WorkflowStep by cart status ' . $name . 'not found');
        }

        /** @var WorkflowStep $step */
        return $steps[0];
    }

    /**
     * @param $name
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function getWorkflowStepName($name)
    {
        if (empty($this->statuses[$name])) {
            throw new \InvalidArgumentException('Invalid cart status ' . $name);
        }

        $workflowName = $this->statuses[$name];

        /** Random set for open WorkflowStep */
        if ($name === 'open' && rand(0, 1)) {
            $workflowName = 'contacted';
        }

        return $workflowName;
    }

    /**
     * @return WorkflowDefinition
     */
    protected function getCartWorkflowDefinition()
    {
        return $this->em->getRepository('OroWorkflowBundle:WorkflowDefinition')->findOneBy(
            ['name' => 'b2c_flow_abandoned_shopping_cart']
        );
    }
}
