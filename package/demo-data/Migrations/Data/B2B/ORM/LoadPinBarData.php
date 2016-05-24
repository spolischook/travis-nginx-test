<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2B\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\ReportBundle\Entity\Report;

use OroCRM\Bundle\SalesBundle\Entity\B2bCustomer;
use OroCRM\Bundle\SalesBundle\Entity\Lead;
use OroCRM\Bundle\SalesBundle\Entity\Opportunity;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadPinBarData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var User[]
     */
    protected $users;

    /** @var  ItemFactory */
    protected $navigationFactory;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->navigationFactory = $container->get('oro_navigation.item.factory');
        $this->userManager       = $container->get('oro_user.manager');
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $userStorageManager = $this->userManager->getStorageManager();
        $users              = $userStorageManager->getRepository('OroUserBundle:User')->findAll();
        /** @var B2bCustomer $customer */
        $customer = $this->getCustomerReference('B2B:3');
        $customerName = $customer->getName();
        /** @var Lead $lead */
        $lead = $this->getLeadReference(3);
        $leadName = $lead->getName();
        /** @var Opportunity $opportunity */
        $opportunity = $this->getOpportunityReference(13);
        $opportunityName = $opportunity->getName();
        /** @var Report $customer */
        $report = $this->getReportReference(2);
        $reportName = $report->getName();
        $params = [
            'customer' => [
                "url" => "/b2bcustomer/view/" . $customer->getId(),
                "title_rendered" => "{$customerName} - Business Customers - Customers",
                "title_rendered_short" => "{$customerName}",
                "title" => "{\"template\":\"%b2bcustomer.name% - orocrm.sales.b2bcustomer.entity_plural_label " .
                    "- orocrm.account.menu.customers_tab.label\",\"short_template\":\"%b2bcustomer.name%\"" .
                    ",\"params\":{\"%b2bcustomer.name%\":\"{$customerName}\"}}",
                "position" => 0,
                "type" => "pinbar",
                "display_type" => "list",
                "maximized" => false,
                "remove" => false
            ],
            'leads' => [
                "url" => "/lead/view/" . $lead->getId(),
                "title_rendered" => "{$leadName} - Leads - Sales",
                "title" => "{\"template\":\"%lead.name% - orocrm.sales.lead.entity_plural_label " .
                    "- orocrm.sales.menu.sales_tab.label\",\"short_template\":\"%lead.name%\"," .
                    "\"params\":{\"%lead.name%\":\"{$leadName}\"}}",
                "position" => 1,
                "type" => "pinbar",
                "display_type" => "list",
                "maximized" => false,
                "remove" => false
            ],
            'opportunities' => [
                "url" => "/opportunity/view/" . $opportunity->getId(),
                "title_rendered" => "{$opportunityName} - Opportunities - Sales",
                "title" => "{\"template\":\"%opportunity.name% - orocrm.sales.opportunity.entity_plural_label " .
                    "- orocrm.sales.menu.sales_tab.label\",\"short_template\":\"%opportunity.name%\"," .
                    "\"params\":{\"%opportunity.name%\":\"{$opportunityName}\"}}",
                "position" => 2,
                "type" => "pinbar",
                "display_type" => "list",
                "maximized" => false,
                "remove" => false
            ],
            'report' => [
                "url" => "/report/view/" . $report->getId(),
                "title_rendered" => "{$reportName} -  Magento Customers - Reports",
                "title_rendered_short" => "{$reportName} -  Magento Customers - Reports",
                "title" => "{\"template\":\"%report.name% - %report.group% " .
                    "- Reports\",\"short_template\":\"%report.name% - %report.group% - Reports\"" .
                    ",\"params\":{\"%report.name%\":\"{$reportName}\",\"%report.group%\":\"Magento Customers\"}}",
                "position" => 3,
                "type" => "pinbar",
                "display_type" => "list",
                "maximized" => false,
                "remove" => false
            ],
        ];
        $tokenStorage = $this->container->get('security.token_storage');

        /** @var User $user */
        /** @var Organization $organization */
        foreach ($users as $user) {
            $organization = $user->getOrganization();
            $token        = new UsernamePasswordOrganizationToken($user, $user->getUsername(), 'main', $organization);
            $tokenStorage->setToken($token);
            foreach ($params as $param) {
                $param['user'] = $user;
                $pinTab        = $this->navigationFactory->createItem($param['type'], $param);
                $pinTab->getItem()->setOrganization($organization);
                $manager->persist($pinTab);
            }
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 56;
    }
}
