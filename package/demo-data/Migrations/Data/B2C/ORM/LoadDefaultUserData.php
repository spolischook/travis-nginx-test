<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;

class LoadDefaultUserData extends AbstractFixture implements OrderedFixtureInterface
{
    /** @var  EntityRepository */
    protected $roleRepository;

    /** @var  EntityRepository */
    protected $businessRepository;

    /** @var  UserManager */
    protected $userManager;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);

        $this->roleRepository     = $this->em->getRepository('OroUserBundle:Role');
        $this->businessRepository = $this->em->getRepository('OroOrganizationBundle:BusinessUnit');
        $this->userManager        = $this->container->get('oro_user.manager');
    }

    /**
     * @return array
     */
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'organization uid',
                'business unit uid',
                'birthday',
                'group uid',
            ]
        );
    }

    public function getData()
    {
        return [
            'users'     => $this->loadData('users/users.csv'),
            'sales'     => $this->loadData('users/default_sales.csv'),
            'marketing' => $this->loadData('users/default_marketing.csv'),
        ];
    }


    public function load(ObjectManager $manager)
    {
        /** @var Role $marketingRole */
        $marketingRole = $this->roleRepository->findOneBy(['role' => 'ROLE_MARKETING_MANAGER']);
        /** @var Role $saleRole */
        $saleRole = $this->roleRepository->findOneBy(['role' => LoadRolesData::ROLE_MANAGER]);

        /** @var Role $userRole */
        $userRole = $this->roleRepository->findOneBy(['role' => LoadRolesData::ROLE_USER]);

        $businessUnits = new ArrayCollection($this->businessRepository->findAll());

        $data = $this->getData();

        $this->addUsers($saleRole, $businessUnits, $data['sales']);
        $this->addUsers($marketingRole, $businessUnits, $data['marketing']);

        foreach ($data['users'] as $userData) {
            $businessUnit = new ArrayCollection([$this->getBusinessUnitReference($userData['business unit uid'])]);
            $this->createUser($userRole, $businessUnit, $userData);
        }

        $mainUser = $this->getMainUser();
        $mainUser->setCreatedAt(new \DateTime('now - 1 month'));
        $this->setUserReference('main', $mainUser);
        $manager->persist($mainUser);
        $manager->flush();
    }

    /**
     * @param array           $data
     * @param Role            $role
     * @param ArrayCollection $businessUnits
     */
    protected function addUsers(Role $role, ArrayCollection $businessUnits, array $data = [])
    {
        foreach ($data as $userData) {
            $this->createUser($role, $businessUnits, $userData);
        }
    }

    /**
     * @param array           $userData
     * @param Role            $role
     * @param ArrayCollection $businessUnits
     */
    protected function createUser(Role $role, ArrayCollection $businessUnits, array $userData = [])
    {
        $organization = $this->getOrganizationReference($userData['organization uid']);
        /** @var User $user */
        $user = $this->userManager->createUser();
        if (!empty($userData['group uid'])) {
            $user->addGroup($this->getGroupReference($userData['group uid']));
        }
        if (!empty($userData['birthday'])) {
            $birthday = new \DateTime($userData['birthday'], new \DateTimeZone('UTC'));
            $user->setBirthday($birthday);
        }
        $user->addRole($role);

        /**
         * Setup manual properties(Created, Updated, LoginCount) for entity
         */
        $this->em->getClassMetadata(get_class($user))->setLifecycleCallbacks([]);
        $user->setCreatedAt($this->generateCreatedDate());
        $user->setUpdatedAt($this->generateUpdatedDate($user->getCreatedAt()));
        $user->setLoginCount(0);

        $user->setOrganization($organization);
        $user->addOrganization($organization);
        $user->setBusinessUnits($businessUnits);

        $this->setObjectValues($user, $userData);
        $user->setPlainPassword($userData['username']);
        $this->userManager->updatePassword($user);
        $this->userManager->updateUser($user);

        $this->setUserReference($userData['uid'], $user);
        $this->em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 5;
    }
}
