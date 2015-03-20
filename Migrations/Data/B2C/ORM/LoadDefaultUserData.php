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
        $organization = $this->getMainOrganization();

        /** @var Role $marketingRole */
        $marketingRole = $this->roleRepository->findOneBy(['role' => 'ROLE_MARKETING_MANAGER']);
        /** @var Role $saleRole */
        $saleRole = $this->roleRepository->findOneBy(['role' => LoadRolesData::ROLE_MANAGER]);

        $businessUnits = new ArrayCollection($this->businessRepository->findAll());

        $data = $this->getData();
        foreach ($data['sales'] as $userData) {
            $userData['created'] = new \DateTime($userData['created'], new \DateTimeZone('UTC'));
            $user = $this->createUser($manager, $userData, $saleRole, $organization, $businessUnits);
            $this->userManager->updateUser($user);
            $this->setReference('User:' . $userData['uid'], $user);
        }

        foreach ($data['marketing'] as $userData) {
            $userData['created'] = new \DateTime($userData['created'], new \DateTimeZone('UTC'));
            $user = $this->createUser($manager, $userData, $marketingRole, $organization, $businessUnits);
            $this->userManager->updateUser($user);
            $this->setReference('User:' . $userData['uid'], $user);
        }

        $mainBusinessUnit = new ArrayCollection([$this->getReferenceByName('BusinessUnit:0')]);
        foreach ($data['users'] as $userData) {
            $userData['created'] = $this->generateCreatedDate();
            $user = $this->createUser($manager, $userData, $saleRole, $organization, $mainBusinessUnit);
            $user->setPlainPassword($userData['username']);
            $this->userManager->updatePassword($user);
            $this->userManager->updateUser($user);
            $this->setReference('User:' . $userData['uid'], $user);
        }
    }

    /**
     * @param ObjectManager $manager
     * @param array $userData
     * @param Role $role
     * @param Organization $organization
     * @param ArrayCollection $businessUnits
     * @return User
     */
    protected function createUser(
        ObjectManager $manager,
        $userData = [],
        Role $role,
        Organization $organization,
        ArrayCollection $businessUnits
    ) {
        /** @var User $user */
        $user = $this->userManager->createUser();
        if (!empty($userData['group uid'])) {
            $user->addGroup($this->getGroupReference($userData['group uid']));
        }
        if (!empty($userData['birthday'])) {
            $userData['birthday'] = new \DateTime($userData['birthday'], new \DateTimeZone('UTC'));
        }
        $user->addRole($role);
        $uid = $userData['uid'];
        $userData['updated'] = $this->generateUpdatedDate($userData['created']);

        /**
         * Setup manual properties(Created, Updated, LoginCount) for entity
         */
        $manager->getClassMetadata(get_class($user))->setLifecycleCallbacks([]);
        $user->setCreatedAt($userData['created']);
        $user->setUpdatedAt($userData['updated']);
        $user->setLoginCount(0);

        unset($userData['uid'], $userData['group uid'], $userData['group'], $userData['created'], $userData['updated']);
        $this->setObjectValues($user, $userData);

        $user->setOrganization($organization);
        $user->addOrganization($organization);
        $user->setBusinessUnits($businessUnits);

        $user->setOwner($this->getBusinessUnitReference());
        $this->setReference('DefaultUser:' . $uid, $user);

        return $user;
    }

    /**
     * @param $uid
     * @return BusinessUnit
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    protected function getBusinessUnitReference($uid = 0)
    {
        $reference = 'BusinessUnit:' . $uid;
        return $this->getReferenceByName($reference);
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
}
