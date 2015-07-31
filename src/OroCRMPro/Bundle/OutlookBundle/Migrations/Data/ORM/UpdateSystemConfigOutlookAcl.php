<?php

namespace OroCRMPro\Bundle\OutlookBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\UserBundle\Entity\Role;

class UpdateSystemConfigOutlookAcl extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\SecurityBundle\Migrations\Data\ORM\LoadAclRoles'];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Load ACL for security roles
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->objectManager = $manager;

        /** @var AclManager $manager */
        $manager = $this->container->get('oro_security.acl.manager');

        if ($manager->isAclEnabled()) {
            $this->updateRoles($manager);
            $manager->flush();
        }
    }

    protected function updateRoles(AclManager $manager)
    {
        $roles = $this->getRoles();
        foreach ($roles as $role) {
            $sid = $manager->getSid($role);

            $oid         = $manager->getOid('action:orocrmpro_outlook_integration');
            $maskBuilder = $manager->getMaskBuilder($oid)
                ->add('EXECUTE');
            $manager->setPermission($sid, $oid, $maskBuilder->get());
        }
    }

    /**
     * @return Role[]
     */
    protected function getRoles()
    {
        $queryBuilder = $this->objectManager->getRepository('OroUserBundle:Role')->createQueryBuilder('e');
        return $queryBuilder
            ->andWhere(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->like('e.role', ':prefix'),
                    $queryBuilder->expr()->neq('e.role', ':admin')
                )
            )
            ->setParameter('prefix', Role::PREFIX_ROLE . '%')
            ->setParameter('admin', 'ROLE_ADMINISTRATOR')
            ->getQuery()
            ->getResult();
    }
}
