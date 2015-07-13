<?php

namespace Oro\Bundle\LDAPBundle\ImportExport;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\LDAPBundle\Provider\Transport\LdapTransportInterface;
use Oro\Bundle\UserBundle\Entity\User;

class LdapHelper implements ContextAwareInterface
{
    /** @var  ContextInterface */
    protected $context;
    /** @var string */
    protected $roleIdAttr;
    /** @var string */
    protected $roleUserIdAttr;
    /** @var ConnectorContextMediator */
    private $contextMediator;
    /** @var LdapTransportInterface */
    private $transport;
    /** @var Channel */
    private $channel;
    /** @var array */
    private $roleMapping;
    /** @var Registry */
    private $registry;

    /**
     * @param ConnectorContextMediator $contextMediator
     * @param Registry                 $registry
     */
    public function __construct(ConnectorContextMediator $contextMediator, Registry $registry)
    {
        $this->contextMediator = $contextMediator;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
        $this->transport = $this->contextMediator->getTransport($this->context);
        $this->channel = $this->contextMediator->getChannel($this->context);
        $this->transport->init($this->channel->getTransport());

        $this->roleMapping = [];
        $mapping = $this->channel->getMappingSettings()->offsetGet('roleMapping');
        foreach ($mapping as $map) {
            $this->roleMapping[$map['ldapName']] = $map['crmRoles'];
        }
        $this->roleIdAttr = $this->channel->getMappingSettings()->offsetGet('roleIdAttribute');
        $this->roleUserIdAttr = $this->channel->getMappingSettings()->offsetGet('roleUserIdAttribute');
    }

    /**
     * Searches LDAP for users roles.
     *
     * @param $userDn
     *
     * @return \Iterator
     */
    protected function getRoles($userDn)
    {
        $filter = $this->channel->getMappingSettings()->offsetGet('roleFilter');
        if (!preg_match('/^\(.+\)$/', $filter)) {
            $filter = "($filter)";
        }

        $filter = sprintf('(&%s(%s=%s))', $filter, $this->roleUserIdAttr, $userDn);

        return $this->transport->search($filter, [$this->roleIdAttr]);
    }

    /**
     * Updates roles of a user
     *
     * @param User      $user
     * @param \Iterator $roles LDAP role records
     */
    protected function updateRoles(User $user, $roles)
    {
        $ldapRoles = [];
        foreach ($roles as $role) {
            if (!array_key_exists($this->roleIdAttr, $role)) {
                continue;
            }

            $ldapValue = $role[$this->roleIdAttr];

            $value = null;
            if (!array_key_exists('count', $ldapValue) || $ldapValue['count'] == 1) {
                $value = $ldapValue[0];
            } else {
                $value = array_slice($ldapValue, 1);
            }
            if ($value) {
                $ldapRoles[] = $value;
            }
        }

        $roles = [];
        foreach ($ldapRoles as $ldapRole) {
            if (!isset($this->roleMapping[$ldapRole])) {
                continue;
            }

            $roles = array_merge($roles, $this->roleMapping[$ldapRole]);
        }

        $uniqueRoles = array_unique($roles);
        if (!$uniqueRoles) {
            return;
        }

        $this->setUserRoles($user, $uniqueRoles);
    }

    /**
     * @param User  $user
     * @param array $roles
     */
    protected function setUserRoles($user, array $roles)
    {
        $em = $this->getRoleEntityManager();
        $roleReferences = [];
        foreach ($roles as $role) {
            $roleReferences[] = $em->getReference('Oro\Bundle\UserBundle\Entity\Role', $role);
        }

        array_map([$user, 'addRole'], $roleReferences);
    }

    /**
     * Populates roles of a user.
     *
     * @param User $user
     */
    public function populateUserRoles(User $user)
    {
        $dns = (array)$user->getLdapDistinguishedNames();
        $roles = $this->getRoles($dns[$this->channel->getId()]);

        $this->updateRoles($user, $roles);
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    private function getRoleEntityManager()
    {
        return $this->registry->getManagerForClass('OroUserBundle:Role');
    }

    /**
     * Populates owner of user.
     *
     * @param User $entity
     */
    public function populateBusinessUnitOwner($entity)
    {
        if ($entity->getOwner() === null) {
            $entity->setOwner($this->channel->getDefaultUserOwner()->getOwner());
        }
    }
}
