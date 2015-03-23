<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

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
    public function getDependencies()
    {
        return [
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadGroupData',
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadBusinessUnitData'
        ];
    }

    public function getData()
    {
        return [
            'users' => $this->loadData('users/users.csv'),
            'sales' => $this->loadData('users/default_sales.csv'),
            'marketing' => $this->loadData('users/default_marketing.csv')
        ];
    }

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

    public function load(ObjectManager $manager)
    {
        /** @var Role $marketingRole */
        $marketingRole = $this->roleRepository->findOneBy(['role' => 'ROLE_MARKETING_MANAGER']);
        /** @var Role $saleRole */
        $saleRole = $this->roleRepository->findOneBy(['role' => LoadRolesData::ROLE_MANAGER]);

        $businessUnits = new ArrayCollection($this->businessRepository->findAll());

        $data = $this->getData();
        foreach ($data['sales'] as $userData) {
            $userData['created'] = new \DateTime($userData['created'], new \DateTimeZone('UTC'));
            $this->createUser($manager, $userData, $saleRole, $businessUnits);
        }

        foreach ($data['marketing'] as $userData) {
            $userData['created'] = new \DateTime($userData['created'], new \DateTimeZone('UTC'));
            $this->createUser($manager, $userData, $marketingRole, $businessUnits);
        }

        foreach ($data['users'] as $userData) {
            $userData['created'] = $this->generateCreatedDate();
            $uid = $userData['business unit uid'];
            $businessUnit = new ArrayCollection([$this->getReferenceByName('BusinessUnit:'. $uid)]);
            $this->createUser($manager, $userData, $saleRole, $businessUnit);
        }
    }

    /**
     * @param ObjectManager $manager
     * @param array $userData
     * @param Role $role
     * @param ArrayCollection $businessUnits
     */
    protected function createUser(
        ObjectManager $manager,
        $userData = [],
        Role $role,
        ArrayCollection $businessUnits
    ) {
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
        $this->setReference('User:' . $userData['uid'], $user);
    }

    /**
     * @param $uid
     * @return Group
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    protected function getGroupReference($uid)
    {
        $reference = 'Group:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @return BusinessUnit
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    protected function getBusinessUnitReference($uid)
    {
        $reference = 'BusinessUnit:' . $uid;
        return $this->getReferenceByName($reference);
    }
}
