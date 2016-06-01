<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2B\ORM\B2B;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;

use OroCRM\Bundle\SalesBundle\Entity\Lead;
use OroCRM\Bundle\SalesBundle\Entity\Opportunity;
use OroCRM\Bundle\SalesBundle\Entity\SalesFunnel;

use OroCRMPro\Bundle\DemoDataBundle\Exception\EntityNotFoundException;
use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadSalesFunnelData extends AbstractFixture implements OrderedFixtureInterface
{
    /** @var WorkFlowStep[] */
    protected $workflowSteps;

    /** @var WorkflowDefinition */
    protected $workflowDefinition;

    /** @var WorkflowManager */
    protected $workflowManager;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->workflowManager = $container->get('oro_workflow.manager');
    }

    /**
     * @return array
     */
    protected function getData()
    {
        return [
            'leads'         => $this->loadData('b2b/sales/leads.csv'),
            'opportunities' => $this->loadData('b2b/sales/opportunities.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();
        $manager->getClassMetadata('OroCRM\Bundle\SalesBundle\Entity\SalesFunnel')->setLifecycleCallbacks([]);

        $this->loadLeadsFunnels($data['leads']);
        $this->loadOpportunitiesFunnels($data['opportunities']);
    }

    /**
     * @param array $opportunities
     * @throws WorkflowException
     */
    protected function loadOpportunitiesFunnels($opportunities = [])
    {
        foreach ($opportunities as $funnelData) {
            $opportunity = $this->getOpportunityReference($funnelData['opportunity uid']);
            $salesFunnel = $this->createSalesFunnel($funnelData);
            $step        = 'start_from_opportunity';
            $parameters  = $this->makeFLowParameters(
                ['opportunity' => $opportunity],
                $salesFunnel->getOwner(),
                $opportunity->getCreatedAt()
            );

            $salesFunnelItem = $this->getSalesFunnelItem($step, $salesFunnel, $parameters, $opportunity);
            if (null !== $salesFunnelItem) {
                $salesFunnelItem->getData()
                    ->set('customer_need', $funnelData['customer need'])
                    ->set('proposed_solution', $funnelData['proposed solution']);
                $stepName    = !empty($funnelData['step name'])
                    ? $funnelData['step name']
                    : $this->getOpportunityWorkflowStepName($opportunity);
                $currentStep = $this->getWorkflowStep($stepName);
                if ($currentStep->getName() === 'won_opportunity') {
                    $salesFunnelItem->getData()
                        ->set('close_revenue', $funnelData['close revenue'])
                        ->set('probability', 1);
                } elseif ($currentStep->getName() === 'lost_opportunity') {
                    $salesFunnelItem->getData()
                        ->set('close_revenue', 0)
                        ->set('probability', 0);
                }
                $salesFunnelItem->setCurrentStep($currentStep);
                $opportunity->setWorkflowItem($salesFunnelItem);
                $salesFunnel->setWorkflowStep($currentStep);
            }
            $this->em->persist($salesFunnel);
        }
        $this->em->flush();
    }

    /**
     * @param array $leads
     */
    protected function loadLeadsFunnels($leads = [])
    {
        foreach ($leads as $funnelData) {
            $lead        = $this->getLeadReference($funnelData['lead uid']);
            $salesFunnel = $this->createSalesFunnel($funnelData);
            $salesFunnel->setLead($lead);
            $step       = 'start_from_lead';
            $parameters = $this->makeFLowParameters(
                ['lead' => $lead],
                $salesFunnel->getOwner(),
                $lead->getCreatedAt()
            );

            if (null !== $salesFunnelItem = $this->getSalesFunnelItem($step, $salesFunnel, $parameters, $lead)) {
                $currentStep = $this->getWorkflowStep($this->getLeadWorkflowStepName($lead));
                $salesFunnelItem->setCurrentStep($currentStep);
                $lead->setWorkflowItem($salesFunnelItem);
                $salesFunnel->setWorkflowStep($currentStep);
            }
            $this->em->persist($salesFunnel);
        }
        $this->em->flush();
    }

    /**
     * @param array $funnelData
     * @return SalesFunnel
     */
    protected function createSalesFunnel(array $funnelData)
    {
        $user = $this->getUserReference($funnelData['user uid']);
        $this->setSecurityContext($user);
        /** @var Organization $organization */
        $organization = $user->getOrganization();

        $salesFunnel = new SalesFunnel();
        $createdAt   = $this->generateCreatedDate();
        $salesFunnel
            ->setOwner($user)
            ->setStartDate($createdAt)
            ->setOrganization($organization)
            ->setCreatedAt($createdAt)
            ->setUpdatedAt($this->generateUpdatedDate($createdAt));

        if (!empty($funnelData['channel uid'])) {
            $salesFunnel->setDataChannel($this->getChannelReference($funnelData['channel uid']));
        }

        return $salesFunnel;
    }

    /**
     * @param                  $step
     * @param SalesFunnel      $funnel
     * @param array            $parameters
     * @param Opportunity|Lead $entity
     * @return null|WorkflowItem
     * @throws \Exception
     * @throws \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     */
    protected function getSalesFunnelItem($step, SalesFunnel $funnel, array $parameters, $entity)
    {
        if (!$this->workflowManager->isStartTransitionAvailable(
            'b2b_flow_sales_funnel',
            $step,
            $funnel,
            $parameters
        )
        ) {
            return null;
        }
        $salesFunnelItem = $this->workflowManager->startWorkflow(
            'b2b_flow_sales_funnel',
            $funnel,
            $step,
            $parameters
        );
        $salesFunnelItem->getData()
            ->set('new_opportunity_name', $entity->getName())
            ->set('new_company_name', $entity->getName());

        return $salesFunnelItem;
    }

    /**
     * @param array     $parameters
     * @param User      $owner
     * @param \DateTime $startDate
     * @return array
     */
    protected function makeFlowParameters(array $parameters, User $owner, \DateTime $startDate)
    {
        return array_merge(
            [
                'sales_funnel'            => null,
                'sales_funnel_owner'      => $owner,
                'sales_funnel_start_date' => $startDate,
            ],
            $parameters
        );
    }

    /**
     * @param $name
     * @return WorkflowStep
     * @throws EntityNotFoundException
     */
    protected function getWorkflowStep($name)
    {
        $steps = $this->getFlowSalesFunnelsSteps();
        if (!isset($steps[$name])) {
            throw new \InvalidArgumentException(sprintf('Invalid workflow step %s', $name));
        }

        return $steps[$name];
    }

    /**
     * @return WorkflowStep[]
     */
    protected function getFlowSalesFunnelsSteps()
    {
        if (count($this->workflowSteps) === 0) {
            $workflowSteps       = $this->em
                ->getRepository('OroWorkflowBundle:WorkflowStep')
                ->findAll();
            $this->workflowSteps = array_reduce(
                $workflowSteps,
                function ($steps, $step) {
                    /** @var WorkflowStep $step */
                    if ($step->getDefinition() === $this->getSalesFunnelWorkflowDefinition()) {
                        $steps[$step->getName()] = $step;
                    }

                    return $steps;
                },
                []
            );
        }

        return $this->workflowSteps;
    }

    /**
     * @return WorkflowDefinition
     */
    protected function getSalesFunnelWorkflowDefinition()
    {
        if (null === $this->workflowDefinition) {
            $this->workflowDefinition = $this->em
                ->getRepository('OroWorkflowBundle:WorkflowDefinition')
                ->find('b2b_flow_sales_funnel');
        }

        return $this->workflowDefinition;
    }

    /**
     * @param Lead $lead
     * @return string
     */
    protected function getLeadWorkflowStepName(Lead $lead)
    {
        $name = 'new_lead';
        if ($lead->getStatus()->getName() === 'canceled') {
            $name = 'disqualified_lead';
        }

        return $name;
    }

    /**
     * @param Opportunity $opportunity
     * @return string
     */
    protected function getOpportunityWorkflowStepName(Opportunity $opportunity)
    {
        $opportunityStatus = $opportunity->getStatus();
        if (null === $opportunityStatus) {
            return 'new_opportunity';
        }

        $name       = 'developed_opportunity';
        $statusName = $opportunityStatus->getName();
        if ($statusName === 'lost') {
            $name = 'lost_opportunity';
        }
        if ($statusName === 'won') {
            $name = 'won_opportunity';
        }

        return $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 55;
    }
}
