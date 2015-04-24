<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2B\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

use OroCRM\Bundle\SalesBundle\Entity\Lead;
use OroCRM\Bundle\SalesBundle\Entity\Opportunity;
use OroCRM\Bundle\SalesBundle\Entity\SalesFunnel;

use Oro\Bundle\UserBundle\Entity\User;
use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadSalesFunnelData extends AbstractFixture implements DependentFixtureInterface
{
    /** @var WorkflowManager */
    protected $workflowManager;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\\LoadLeadsData',
            __NAMESPACE__ . '\\LoadOpportunitiesData'
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->workflowManager = $container->get('oro_workflow.manager');
        parent::setContainer($container);
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();
        $manager->getClassMetadata('OroCRM\Bundle\SalesBundle\Entity\SalesFunnel')->setLifecycleCallbacks([]);
        foreach ($data['leads'] as $funnelData) {
            if (empty($funnelData['lead uid'])) {
                throw new \InvalidArgumentException('Lead uid can n ot be empty.');
            }
            $salesFunnel = $this->createSalesFunnel($funnelData);
            $manager->persist($salesFunnel);
            //$this->loadLeadFlow($salesFunnel, $funnelData);
        }
        $manager->flush();

        foreach ($data['opportunities'] as $funnelData) {
            if (empty($funnelData['opportunity uid'])) {
                throw new \InvalidArgumentException('Opportunity uid can not be empty.');
            }
            $salesFunnel = $this->createSalesFunnel($funnelData);
            $manager->persist($salesFunnel);
            //$this->loadOpportunityFlow($salesFunnel, $funnelData);
        }
        $manager->flush();
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
        $createdAt = $this->generateCreatedDate();
        $salesFunnel
            ->setOwner($user)
            ->setStartDate($createdAt)
            ->setOrganization($organization)
            ->setCreatedAt($createdAt)
            ->setUpdatedAt($this->generateUpdatedDate($createdAt));

        if (!empty($funnelData['channel uid'])) {
            $salesFunnel->setDataChannel($this->getDataChannelReference($funnelData['channel uid']));
        }
        if (!empty($funnelData['lead uid'])) {
            $salesFunnel->setLead($this->getLeadReference($funnelData['lead uid']));
        }
        if (!empty($funnelData['opportunity uid'])) {
            $salesFunnel->setOpportunity($this->getOpportunityReference($funnelData['opportunity uid']));
        }

        return $salesFunnel;
    }

    /**
     * @param SalesFunnel $funnel
     * @param array       $funnelData
     * @throws \Exception
     */
    protected function loadLeadFlow(SalesFunnel $funnel, array $funnelData)
    {
        $step       = 'start_from_lead';
        $lead       = $funnel->getLead();
        $parameters = $this->makeFLowParameters(['lead' => $lead], $funnel->getOwner());

        if (null === $salesFunnelItem = $this->getSalesFunnelItem($step, $funnel, $parameters, $lead)) {
            return;
        }

        if ($this->isTransitionAllowed($salesFunnelItem, 'qualify')) {
            $this->workflowManager->transit($salesFunnelItem, 'qualify');
        } else {
            return;
        }
        $this->processSalesFunnelItem($salesFunnelItem, $funnelData, $lead);
    }

    /**
     * @param SalesFunnel $funnel
     * @param array       $funnelData
     * @throws \Exception
     */
    protected function loadOpportunityFlow(SalesFunnel $funnel, array $funnelData)
    {
        $step        = 'start_from_opportunity';
        $opportunity = $funnel->getOpportunity();
        $parameters  = $this->makeFLowParameters(['opportunity' => $opportunity], $funnel->getOwner());

        if (null === $salesFunnelItem = $this->getSalesFunnelItem($step, $funnel, $parameters, $opportunity)) {
            return;
        }
        $this->processSalesFunnelItem($salesFunnelItem, $funnelData, $opportunity);
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
     * @param array $parameters
     * @param User  $owner
     * @return array
     */
    protected function makeFlowParameters(array $parameters, User $owner)
    {
        return array_merge(
            [
                'sales_funnel'            => null,
                'sales_funnel_owner'      => $owner,
                'sales_funnel_start_date' => new \DateTime(),
            ],
            $parameters
        );
    }

    /**
     * @param WorkflowItem     $salesFunnelItem
     * @param array            $funnelData
     * @param Opportunity|Lead $entity
     * @throws \Exception
     * @throws \Oro\Bundle\WorkflowBundle\Exception\WorkflowException
     */
    protected function processSalesFunnelItem(WorkflowItem $salesFunnelItem, array $funnelData, $entity)
    {
        $transition = $funnelData['transition'];
        $salesFunnelItem->getData()
            ->set('budget_amount', $funnelData['budget amount'])
            ->set('customer_need', $funnelData['customer need'])
            ->set('proposed_solution', $funnelData['proposed solution'])
            ->set('probability', $funnelData['probability']);

        if ($this->isTransitionsAllowed($salesFunnelItem, $transition)) {
            $this->workflowManager->transit($salesFunnelItem, 'develop');

            if (in_array($transition, ['close_as_won', 'close_as_lost'])) {
                $closeDate = $this->generateCloseDate($entity->getUpdatedAt());
                if ($funnelData['close_revenue'] > 0) {
                    $salesFunnelItem->getData()
                        ->set('close_revenue', $funnelData['close revenue'])
                        ->set('close_date', $closeDate);
                }
                if ($transition === 'close_as_won') {
                    if ($this->isTransitionAllowed($salesFunnelItem, 'close_as_won')) {
                        $this->workflowManager->transit($salesFunnelItem, 'close_as_won');
                    }
                } elseif ($transition === 'close_as_lost') {
                    $salesFunnelItem->getData()
                        ->set('close_reason_name', 'cancelled')
                        ->set('close_date', $closeDate);
                    if ($this->isTransitionAllowed($salesFunnelItem, 'close_as_lost')) {
                        $this->workflowManager->transit($salesFunnelItem, 'close_as_lost');
                    }
                }
            }
        }
    }

    /**
     * @param WorkflowItem $workflowItem
     * @param string       $transition
     * @return bool
     */
    protected function isTransitionAllowed(WorkflowItem $workflowItem, $transition)
    {
        $workflow = $this->workflowManager->getWorkflow($workflowItem);

        return $workflow->isTransitionAllowed($workflowItem, $transition);
    }

    /**
     * @return array
     */
    protected function getData()
    {
        return [
            'leads'         => $this->loadData('sales_funnels/leads.csv'),
            'opportunities' => $this->loadData('sales_funnels/opportunities.csv'),
        ];
    }

    /**
     * @param WorkflowItem $salesFunnelItem
     * @param              $transition
     * @return bool
     */
    private function isTransitionsAllowed(WorkflowItem $salesFunnelItem, $transition)
    {
        return $this->isTransitionAllowed($salesFunnelItem, 'develop')
        && in_array($transition, ['develop', 'close_as_won', 'close_as_lost']);
    }
}
