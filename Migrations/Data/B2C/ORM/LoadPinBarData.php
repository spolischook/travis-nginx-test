<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory;

class LoadPinBarData extends AbstractFixture implements DependentFixtureInterface
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
    public function getDependencies()
    {
        return [
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadDefaultUserData'
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->navigationFactory = $container->get('oro_navigation.item.factory');
        $this->userManager = $container->get('oro_user.manager');
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $userStorageManager = $this->userManager->getStorageManager();
        $users = $userStorageManager->getRepository('OroUserBundle:User')->findAll();

        $params = [
            'account' => [
                "url" => "/account",
                "title_rendered" => "Accounts - Customers",
                "title" => "{\"template\":\"Accounts - Customers\",\"short_template\":\"Accounts\",\"params\":[]}",
                "position" => 0,
                "type" => "pinbar",
                "display_type" => "list",
                "maximized" => false,
                "remove" => false
            ],
            'contact' => [
                "url" => "/contact",
                "title_rendered" => "Contacts - Customers",
                "title" => "{\"template\":\"Contacts - Customers\",\"short_template\":\"Contacts\",\"params\":[]}",
                "position" => 1,
                "type" => "pinbar",
                "display_type" => "list",
                "maximized" => false,
                "remove" => false
            ],
            'leads' => [
                "url" => "/lead",
                "title_rendered" => "Leads - Sales",
                "title" => "{\"template\":\"Leads - Sales\",\"short_template\":\"Leads\",\"params\":[]}",
                "position" => 2,
                "type" => "pinbar",
                "display_type" => "list",
                "maximized" => false,
                "remove" => false
            ],
            'opportunities' => [
                "url" => "/opportunity",
                "title_rendered" => "Opportunities - Sales",
                "title"
                => "{\"template\":\"Opportunities - Sales\",\"short_template\":\"Opportunities\",\"params\":[]}",
                "position" => 3,
                "type" => "pinbar",
                "display_type" => "list",
                "maximized" => false,
                "remove" => false
            ]
        ];
        $organization = $this->getMainOrganization();
        foreach ($users as $user) {
            $this->setSecurityContext($user);
            foreach ($params as $param) {
                $param['user'] = $user;
                $pinTab = $this->navigationFactory->createItem($param['type'], $param);
                $pinTab->getItem()->setOrganization($organization);
                $manager->persist($pinTab);
            }
        }
        $manager->flush();
    }
}
