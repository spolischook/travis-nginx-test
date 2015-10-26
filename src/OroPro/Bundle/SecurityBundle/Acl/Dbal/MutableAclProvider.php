<?php

namespace OroPro\Bundle\SecurityBundle\Acl\Dbal;

use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

use Oro\Bundle\SecurityBundle\Acl\Dbal\MutableAclProvider as BaseMutableAclProvider;
use Oro\Bundle\SecurityBundle\Acl\Domain\BusinessUnitSecurityIdentity;

use OroPro\Bundle\SecurityBundle\Acl\Domain\OrganizationSecurityIdentity;

/**
 * This class extends the standard Oro MutableAclProvider.
 * Added OrganizationSecurityIdentifier
 */
class MutableAclProvider extends BaseMutableAclProvider
{
    /**
     * {@inheritdoc}
     */
    protected function getUpdateSecurityIdentitySql(SecurityIdentityInterface $sid, $oldName)
    {
        if ($sid instanceof UserSecurityIdentity) {
            if ($sid->getUsername() == $oldName) {
                throw new \InvalidArgumentException('There are no changes.');
            }
            $oldIdentifier = $sid->getClass() . '-' . $oldName;
            $newIdentifier = $sid->getClass() . '-' . $sid->getUsername();
            $username = true;
        } elseif ($sid instanceof RoleSecurityIdentity) {
            if ($sid->getRole() == $oldName) {
                throw new \InvalidArgumentException('There are no changes.');
            }
            $oldIdentifier = $oldName;
            $newIdentifier = $sid->getRole();
            $username = false;
        } elseif ($sid instanceof BusinessUnitSecurityIdentity || $sid instanceof OrganizationSecurityIdentity) {
            if ($sid->getId() === $oldName) {
                throw new \InvalidArgumentException('There are no changes.');
            }
            $oldIdentifier = $sid->getClass() . '-' . $oldName;
            $newIdentifier = $sid->getClass() . '-' . $sid->getId();
            $username = false;
        } else {
            throw new \InvalidArgumentException(
                '$sid must either be an instance of UserSecurityIdentity or RoleSecurityIdentity ' .
                'or OrganizationSecurityIdentity or BusinessUnitSecurityIdentity.'
            );
        }

        $this->sids = null;

        return sprintf(
            'UPDATE %s SET identifier = %s WHERE identifier = %s AND username = %s',
            $this->options['sid_table_name'],
            $this->connection->quote($newIdentifier),
            $this->connection->quote($oldIdentifier),
            $this->connection->getDatabasePlatform()->convertBooleans($username)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getSecurityIdentifier(SecurityIdentityInterface $sid)
    {
        if ($sid instanceof UserSecurityIdentity) {
            return [$sid->getClass().'-'.$sid->getUsername(), true];
        } elseif ($sid instanceof RoleSecurityIdentity) {
            return [$sid->getRole(), false];
        } elseif ($sid instanceof BusinessUnitSecurityIdentity || $sid instanceof OrganizationSecurityIdentity) {
            return [$sid->getClass() . '-' . $sid->getId(), false];
        } else {
            throw new \InvalidArgumentException(
                '$sid must either be an instance of UserSecurityIdentity or RoleSecurityIdentity' .
                ' or BusinessUnitSecurityIdentity or OrganizationSecurityIdentity.'
            );
        }
    }

    /**
     * @param string $securityIdentifier
     * @param string $username
     *
     * @return BusinessUnitSecurityIdentity|RoleSecurityIdentity|OrganizationSecurityIdentity
     */
    protected function getSecurityIdentityFromString($securityIdentifier, $username)
    {
        if ($username) {
            return new UserSecurityIdentity(
                substr($securityIdentifier, 1 + $pos = strpos($securityIdentifier, '-')),
                substr($securityIdentifier, 0, $pos)
            );
        } else {
            $pos = strpos($securityIdentifier, '-');
            $className = substr($securityIdentifier, 0, $pos);

            if ($pos !== false && class_exists($className)) {
                $identifier = substr($securityIdentifier, 1 + $pos);
                $sidReflection = new \ReflectionClass($className);
                $interfaceNames = $sidReflection->getInterfaceNames();
                if (in_array(
                    'Oro\Bundle\OrganizationBundle\Entity\BusinessUnitInterface',
                    (array) $interfaceNames,
                    true
                )) {
                    return new BusinessUnitSecurityIdentity($identifier, $className);
                } elseif (in_array(
                    'Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface',
                    (array) $interfaceNames,
                    true
                )) {
                    return new OrganizationSecurityIdentity($identifier, $className);
                }
            }

            return new RoleSecurityIdentity($securityIdentifier);
        }
    }
}
