<?php
namespace OroCRMPro\Bundle\DemoDataBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAwareManager;
use OroCRM\Bundle\MagentoBundle\Entity\Cart;
use OroCRMPro\Bundle\DemoDataBundle\Exception\EntityNotFoundException;

class CartSubscriber implements EventSubscriber
{
    /** @var WorkflowAwareManager */
    private $flow;

    /** @var EntityManager */
    protected $em;

    /** @var  WorkflowStep[] */
    protected $workflowSteps;

    /** @var  Workflow */
    protected $workflow;

    /** @var array */
    protected $statuses = [
        'open' => 'open',
        'expired' => 'abandoned',
        'lost' => 'abandoned',
        'purchased' => 'converted',
        'converted_to_opportunity' => 'converted',
    ];

    public function __construct(WorkflowAwareManager $cardFlowManager)
    {
        $this->flow = $cardFlowManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return [
            Events::preUpdate
        ];
    }

    /**
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $this->em = $args->getEntityManager();
        $entity = $args->getEntity();

        /** @var Cart $entity */
        if ($entity instanceof Cart) {
            $workflowItem = $this->flow->getWorkflowItem($entity);
            if ($workflowItem) {
                $step = $this->getWorkflowStep($entity->getStatus()->getName());
                $workflowItem->setCurrentStep($step);
                $this->em->persist($workflowItem);
            }
        }
    }

    /**
     * @param $name
     *
     * @return WorkflowStep
     * @throws EntityNotFoundException
     */
    protected function getWorkflowStep($name)
    {
        $workflowStepName = $this->getWorkflowStepName($name);

        $this->workflow = $this->flow->getWorkflow();

        $step = $this->flow->getWorkflow()->getDefinition()->getSteps()->filter(
            function (WorkflowStep $step) use ($workflowStepName) {
                return $step->getName() === $workflowStepName;
            }
        )->first();

        if (!$step) {
            throw new EntityNotFoundException('WorkflowStep by cart status ' . $name . ' not found');
        }

        /** @var WorkflowStep $step */
        return $step;
    }

    /**
     * @param $name
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function getWorkflowStepName($name)
    {
        if (empty($this->statuses[$name])) {
            throw new \InvalidArgumentException('Invalid cart status ' . $name);
        }
        $workflowName = $this->statuses[$name];

        return $workflowName;
    }
}
