<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Data\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Yaml\Yaml;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Owner\Metadata\FrontendOwnershipMetadataProvider;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class LoadAccountUserRoles extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    const ADMINISTRATOR = 'ADMINISTRATOR';
    const BUYER = 'BUYER';

    /**
     * @var array
     */
    protected $defaultRoles = [
        self::ADMINISTRATOR => 'Administrator',
        self::BUYER => 'Buyer',
    ];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['OroB2B\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadWebsiteData'];
    }

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
        $aclManager = $this->container->get('oro_security.acl.manager');
        $chainMetadataProvider = $this->container->get('oro_security.owner.metadata_provider.chain');

        $roleData = $this->loadRolesData();

        $chainMetadataProvider->startProviderEmulation(FrontendOwnershipMetadataProvider::ALIAS);

        foreach ($roleData as $roleName => $roleConfigData) {
            $role = $this->createEntity($roleName, $roleConfigData['label']);
            if (!empty($roleConfigData['website_default_role'])) {
                $this->setWebsiteDefaultRoles($role);
            }
            $organization = $manager->getRepository('OroOrganizationBundle:Organization')->findOneBy([]);
            $role->setOrganization($organization);
            $manager->persist($role);

            if (!$aclManager->isAclEnabled()) {
                continue;
            }

            $sid = $aclManager->getSid($role);

            if (!empty($roleConfigData['max_permissions'])) {
                $this->setPermissionGroup($aclManager, $sid);
            }

            if (empty($roleConfigData['permissions']) || !is_array($roleConfigData['permissions'])) {
                continue;
            }

            $this->setPermissions($aclManager, $sid, $roleConfigData['permissions']);
        }

        $chainMetadataProvider->stopProviderEmulation();

        $manager->flush();
        $aclManager->flush();
    }

    /**
     * @param AclManager $aclManager
     * @param SecurityIdentityInterface $sid
     * @param array $permissions
     */
    protected function setPermissions(AclManager $aclManager, SecurityIdentityInterface $sid, array $permissions)
    {
        foreach ($permissions as $permission => $acls) {
            $oid = $aclManager->getOid(str_replace('|', ':', $permission));
            $extension = $aclManager->getExtensionSelector()->select($oid);
            $maskBuilders = $extension->getAllMaskBuilders();

            foreach ($maskBuilders as $maskBuilder) {
                $maskBuilder->reset();

                if ($acls) {
                    foreach ($acls as $acl) {
                        if ($maskBuilder->hasMask('MASK_' . $acl)) {
                            $maskBuilder->add($acl);
                        }
                    }
                }

                $aclManager->setPermission($sid, $oid, $maskBuilder->get());
            }
        }
    }

    /**
     * @return array
     */
    protected function loadRolesData()
    {
        $rolesData = [];
        /** @var Kernel $kernel */
        $kernel = $this->container->get('kernel');
        $bundles = array_keys($this->container->getParameter('kernel.bundles'));
        foreach ($bundles as $bundle) {
            $fileName = $this->getFileName($bundle);
            try {
                $file = $kernel->locateResource($fileName);
                $rolesData = array_merge_recursive($rolesData, Yaml::parse($file));
            } catch (\InvalidArgumentException $e) {
            }
        }

        return $rolesData;
    }

    /**
     * @param string $bundle
     * @return string
     */
    protected function getFileName($bundle)
    {
        return sprintf('@%s%s', $bundle, '/Migrations/Data/ORM/data/frontend_roles.yml');
    }

    /**
     * @param AclManager $aclManager
     * @param SecurityIdentityInterface $sid
     */
    protected function setPermissionGroup(AclManager $aclManager, SecurityIdentityInterface $sid)
    {
        foreach ($aclManager->getAllExtensions() as $extension) {
            $rootOid = $aclManager->getRootOid($extension->getExtensionKey());
            foreach ($extension->getAllMaskBuilders() as $maskBuilder) {
                $fullAccessMask = $maskBuilder->hasMask('GROUP_SYSTEM')
                    ? $maskBuilder->getMask('GROUP_SYSTEM')
                    : $maskBuilder->getMask('GROUP_ALL');
                $aclManager->setPermission($sid, $rootOid, $fullAccessMask, true);
            }
        }
    }

    /**
     * @param string $name
     * @param string $label
     * @return AccountUserRole
     */
    protected function createEntity($name, $label)
    {
        $role = new AccountUserRole(AccountUserRole::PREFIX_ROLE . $name);
        $role->setLabel($label);
        return $role;
    }

    /**
     * @param AccountUserRole $role
     */
    protected function setWebsiteDefaultRoles(AccountUserRole $role)
    {
        $websites = $this->container->get('doctrine')
            ->getManagerForClass('OroB2BWebsiteBundle:Website')
            ->getRepository('OroB2BWebsiteBundle:Website')
            ->findAll();

        foreach ($websites as $website) {
            $role->addWebsite($website);
        }
    }
}
