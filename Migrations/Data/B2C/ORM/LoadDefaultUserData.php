<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;

class LoadDefaultUserData extends AbstractFixture implements DependentFixtureInterface
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

        $this->roleRepository = $this->em->getRepository('OroUserBundle:Role');
        $this->businessRepository = $this->em->getRepository('OroOrganizationBundle:BusinessUnit');
        $this->userManager = $this->container->get('oro_user.manager');
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\\LoadGroupData',
            __NAMESPACE__ . '\\LoadOrganizationData',
            __NAMESPACE__ . '\\LoadBusinessUnitData',
        ];
    }

    public function getData()
    {
        return [
            'users' => $this->loadData('users/users.csv'),
            'sales' => $this->loadData('users/default_sales.csv'),
            'marketing' => $this->loadData('users/default_marketing.csv'),
        ];
    }


    public function load(ObjectManager $manager)
    {
        /** @var Role $marketingRole */
        $marketingRole = $this->roleRepository->findOneBy(['role' => 'ROLE_MARKETING_MANAGER']);
        /** @var Role $saleRole */
        $saleRole = $this->roleRepository->findOneBy(['role' => LoadRolesData::ROLE_MANAGER]);

        $businessUnits = new ArrayCollection($this->businessRepository->findAll());

        $data = $this->getData();

        $this->addUsers($data['sales'], $saleRole, $businessUnits);
        $this->addUsers($data['marketing'], $marketingRole, $businessUnits);

        foreach ($data['users'] as $userData) {
            $businessUnit = new ArrayCollection([$this->getBusinessUnitReference($userData['business unit uid'])]);
            $this->createUser($this->em, $userData, $saleRole, $businessUnit);
        }

        $mainUser = $this->getMainUser();
        $this->setUserReference('main', $mainUser);
    }

    /**
     * @param array $data
     * @param Role $role
     * @param ArrayCollection $businessUnits
     */
    protected function addUsers($data = [],Role $role, ArrayCollection $businessUnits)
    {
        foreach ($data as $userData) {
            $this->createUser($this->em, $userData, $role, $businessUnits);
        }
    }

    /**
     * @param ObjectManager $manager
     * @param array $userData
     * @param Role $role
     * @param ArrayCollection $businessUnits
     */
    protected function createUser(ObjectManager $manager, $userData = [], Role $role, ArrayCollection $businessUnits)
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
        $manager->getClassMetadata(get_class($user))->setLifecycleCallbacks([]);
        $user->setCreatedAt($this->generateCreatedDate());
        $user->setUpdatedAt($this->generateUpdatedDate($user->getCreatedAt()));
        $user->setLoginCount(0);

        $user->setOrganization($organization);
        $user->addOrganization($organization);
        $user->setBusinessUnits($businessUnits);
        $user->setFirstName($userData['firstname']);
        $user->setLastName($userData['lastname']);
        $user->setEmail($userData['email']);
        $user->setUsername($userData['username']);

        $user->setPlainPassword($userData['username']);
        $this->userManager->updatePassword($user);
        $this->userManager->updateUser($user);

        $this->setUserReference($userData['uid'], $user);
        $manager->flush();
    }
}
