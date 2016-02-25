<?php

namespace OroCRMPro\Bundle\OutlookBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\UserBundle\Entity\UserApi;
use Oro\Bundle\UserBundle\Entity\Role;

class LoadOutlookUser extends AbstractFixture implements ContainerAwareInterface
{
    const ROLE_OUTLOOK = 'ROLE_OUTLOOK';

    /** @var ContainerInterface */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $rand = mt_rand(0, 1000);

        /**
         * Create Role
         */
        $roleName    = self::ROLE_OUTLOOK . '_' . $rand;
        $outlookRole = new Role($roleName);
        $outlookRole->setLabel($roleName);

        $manager->persist($outlookRole);
        $manager->flush();

        /**
         * Setting up role permissions
         */

        /** @var AclManager $aclManager */
        $aclManager = $this->container->get('oro_security.acl.manager');
        $sid        = $aclManager->getSid($roleName);

        $oid  = $aclManager->getOid('action:orocrmpro_outlook_integration');
        $mask = $aclManager->getMaskBuilder($oid)->add('EXECUTE')->get();
        $aclManager->setPermission($sid, $oid, $mask);

        $oid  = $aclManager->getOid('entity:Oro\Bundle\UserBundle\Entity\User');
        $mask = $aclManager->getMaskBuilder($oid)
            ->add('VIEW_SYSTEM')
            ->get();
        $aclManager->setPermission($sid, $oid, $mask);

        $oid  = $aclManager->getOid('entity:Oro\Bundle\EmailBundle\Entity\EmailUser');
        $mask = $aclManager->getMaskBuilder($oid)
            ->add('CREATE_SYSTEM')
            ->add('VIEW_SYSTEM')
            ->add('EDIT_SYSTEM')
            ->get();
        $aclManager->setPermission($sid, $oid, $mask);

        $aclManager->flush();

        /**
         * Create new user with assignment to created role.
         */
        $userManager  = $this->container->get('oro_user.manager');
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();
        $role         = $manager->getRepository('OroUserBundle:Role')->findOneBy(['role' => $roleName]);
        $user         = $userManager->createUser();
        $username     = 'outlook_user_' . $rand;

        $api = new UserApi();
        $api->setApiKey($username)
            ->setOrganization($organization)
            ->setUser($user);

        $group = $manager->getRepository('OroUserBundle:Group')->findOneBy(['name' => 'Administrators']);

        $user->setUsername($username)
            ->setFirstname($username)
            ->setLastname($username)
            ->setPlainPassword($username)
            ->setEmail($username . '@example.com')
            ->setEnabled(true)
            ->addRole($role)
            ->addGroup($group)
            ->setOrganization($organization)
            ->setOrganizations(new ArrayCollection([$organization]))
            ->addApiKey($api)
            ->setSalt('');

        $userManager->updateUser($user);

        $this->setReference('outlook_user', $user);
    }
}
