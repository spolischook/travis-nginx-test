<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\ReportBundle\Entity\Report;
use OroCRM\Bundle\AccountBundle\Entity\Account;
use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\MagentoBundle\Entity\Customer;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

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
        $defaultUser = $userStorageManager->getRepository('OroUserBundle:User')->findOneBy(['username' => 'admin']);
        /** @var Account $account */
        $account = $this->getAccountReference(35);
        $accountName = $account->getName();
        /** @var Contact $contact */
        $contact  = $this->getContactReference(46);
        $contactName = $contact->getNamePrefix() . " " . $contact->getFirstName() . " " . $contact->getLastName();
        /** @var Customer $customer */
        $customer = $this->getCustomerReference(37);
        $customerName = $customer->getNamePrefix() . " " . $customer->getFirstName() . " " . $customer->getLastName();
        /** @var Report $customer */
        $report = $this->getReportReference(6);
        $reportName = $report->getName();
        $params = [
            'account' => [
                "url" => "/account/view/" . $account->getId(),
                "title_rendered" => "{$accountName} - Accounts - Customers",
                "title_rendered_short" => "{$accountName}",
                "title" => "{\"template\":\"%account.name% - orocrm.account.entity_plural_label " .
                    "- orocrm.account.menu.customers_tab.label\",\"short_template\":\"%account.name%\"," .
                    "\"params\":{\"%account.name%\":\"{$accountName}\"}}",
                "position" => 0,
                "type" => "pinbar",
                "display_type" => "list",
                "maximized" => false,
                "remove" => false
            ],
            'contact' => [
                "url" => "/contact/view/" . $contact->getId(),
                "title_rendered" => "{$contactName } - Contacts - Customers",
                "title_rendered_short" => "{$contactName}",
                "title" => "{\"template\":\"%contact.name% - orocrm.contact.entity_plural_label " .
                    "- orocrm.account.menu.customers_tab.label\",\"short_template\":\"%contact.name%\"" .
                    ",\"params\":{\"%contact.name%\":\"{$contactName}\"}}",
                "position" => 1,
                "type" => "pinbar",
                "display_type" => "list",
                "maximized" => false,
                "remove" => false
            ],
            'customer' => [
                "url" => "/magento/customer/view/" . $customer->getId(),
                "title_rendered" => "{$customerName} - Magento Customers - Customers",
                "title_rendered_short" => "{$customerName}",
                "title" => "{\"template\":\"%customer.name% - orocrm.magento.customer.entity_plural_label " .
                    "- orocrm.account.menu.customers_tab.label\",\"short_template\":\"%customer.name%\"" .
                    ",\"params\":{\"%customer.name%\":\"{$customerName}\"}}",
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

        foreach ([$defaultUser, $this->getUserReference(2001), $this->getUserReference(1001)] as $user) {
            $this->createPinbar($manager, $params, $user);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param array $params
     * @param User $user
     * @return null|object
     */
    protected function createPinbar(ObjectManager $manager, $params, $user)
    {
        $pinTab = null;
        foreach ($params as $param) {
            $param['user'] = $user;
            $pinTab        = $this->navigationFactory->createItem($param['type'], $param);
            $pinTab->getItem()->setOrganization($user->getOrganization());
            $manager->persist($pinTab);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 55;
    }
}
