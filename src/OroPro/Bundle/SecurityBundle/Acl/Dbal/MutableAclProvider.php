<?php

namespace OroPro\Bundle\SecurityBundle\Acl\Dbal;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

use Oro\Bundle\SecurityBundle\Acl\Dbal\MutableAclProvider as BaseMutableAclProvider;

use OroPro\Bundle\SecurityBundle\Acl\Domain\BusinessUnitSecurityIdentity;
use OroPro\Bundle\SecurityBundle\Acl\Domain\OrganizationSecurityIdentity;
use OroPro\Bundle\SecurityBundle\Event\UpdateAcl;

/**
 * This class extends the standard Oro MutableAclProvider.
 * Added feature to work with BusinessUnitSecurityIdentity and OrganizationSecurityIdentifier
 */
class MutableAclProvider extends BaseMutableAclProvider
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var MutableAclInterface */
    protected $updatedAcl;

    /** @var array|null */
    protected $sids = null;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Clear Sids cache, therefore method hydrateSecurityIdentities updates Sids cache
     *
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
     * Clear Sids cache, therefore method hydrateSecurityIdentities updates Sids cache
     *
     * {@inheritdoc}
     */
    protected function getDeleteSecurityIdentityIdSql(SecurityIdentityInterface $sid)
    {
        $delete = parent::getDeleteSecurityIdentityIdSql($sid);

        $this->sids = null;

        return $delete;
    }

    /**
     * Clear Sids cache, therefore method hydrateSecurityIdentities updates Sids cache
     *
     * {@inheritdoc}
     */
    protected function getInsertSecurityIdentitySql(SecurityIdentityInterface $sid)
    {
        list($identifier, $username) = $this->getSecurityIdentifier($sid);

        $this->sids = null;

        return sprintf(
            'INSERT INTO %s (identifier, username) VALUES (%s, %s)',
            $this->options['sid_table_name'],
            $this->connection->quote($identifier),
            $this->connection->getDatabasePlatform()->convertBooleans($username)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getSelectSecurityIdentityIdSql(SecurityIdentityInterface $sid)
    {
        list($identifier, $username) = $this->getSecurityIdentifier($sid);

        return sprintf(
            'SELECT id FROM %s WHERE identifier = %s AND username = %s',
            $this->options['sid_table_name'],
            $this->connection->quote($identifier),
            $this->connection->getDatabasePlatform()->convertBooleans($username)
        );
    }

    /**
     * Get Security Identifier and Username flag to create SQL queries
     *
     * @param SecurityIdentityInterface $sid
     *
     * @throws \InvalidArgumentException
     *
     * @return array
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
                '$sid must be an instance of UserSecurityIdentity or RoleSecurityIdentity' .
                ' or BusinessUnitSecurityIdentity or OrganizationSecurityIdentity.'
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * Inject shared record id to acl SQL queries (such as InsertAccessControlEntrySql) via property updatedAcl.
     */
    public function updateAcl(MutableAclInterface $acl)
    {
        $this->updatedAcl = $acl;
        $this->connection->beginTransaction();
        try {
            $event = new UpdateAcl($acl);
            if ($this->eventDispatcher) {
                $this->eventDispatcher->dispatch(UpdateAcl::NAME_BEFORE, $event);
            }
            parent::updateAcl($acl);
            if ($this->eventDispatcher) {
                $this->eventDispatcher->dispatch(UpdateAcl::NAME_AFTER, $event);
            }
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->updatedAcl = null;
            $this->connection->rollBack();

            throw $e;
        }

        $this->updatedAcl = null;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    protected function getInsertAccessControlEntrySql(
        $classId,
        $objectIdentityId,
        $field,
        $aceOrder,
        $securityIdentityId,
        $strategy,
        $mask,
        $granting,
        $auditSuccess,
        $auditFailure
    ) {
        $recordId = $this->updatedAcl && $this->updatedAcl->getObjectIdentity()
            ? $this->updatedAcl->getObjectIdentity()->getIdentifier()
            : null;

        $query = <<<QUERY
            INSERT INTO %s (
                class_id,
                object_identity_id,
                field_name,
                ace_order,
                security_identity_id,
                mask,
                granting,
                granting_strategy,
                audit_success,
                audit_failure,
                record_id
            )
            VALUES (%d, %s, %s, %d, %d, %d, %s, %s, %s, %s, %s)
QUERY;

        return sprintf(
            $query,
            $this->options['entry_table_name'],
            $classId,
            null === $objectIdentityId ? 'NULL' : (int) $objectIdentityId,
            null === $field ? 'NULL' : $this->connection->quote($field),
            $aceOrder,
            $securityIdentityId,
            $mask,
            $this->connection->getDatabasePlatform()->convertBooleans($granting),
            $this->connection->quote($strategy),
            $this->connection->getDatabasePlatform()->convertBooleans($auditSuccess),
            $this->connection->getDatabasePlatform()->convertBooleans($auditFailure),
            null === $recordId ? 'NULL' : (int) $recordId
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findAcls(array $oids, array $sids = array())
    {
        $sids = $this->hydrateSecurityIdentities($sids);

        return parent::findAcls($oids, $sids);
    }

    /**
     * Make SIDs before find ACLs
     *
     * @param array $sids
     *
     * @return array
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function hydrateSecurityIdentities(array $sids = array())
    {
        if ($this->sids !== null) {
            return array_merge($this->sids, $sids);
        }

        $sql = $this->getSelectAllSidsSql();
        $stmt = $this->connection->executeQuery($sql);
        $stmtResult = $stmt->fetchAll(\PDO::FETCH_NUM);

        foreach ($stmtResult as $data) {
            list($username, $securityIdentifier) = $data;
            $key = ($username ? '1' : '0').$securityIdentifier;

            if (!isset($sids[$key])) {
                $sids[$key] = $this->getSecurityIdentityFromString($securityIdentifier, $username);
            }
        }

        $this->sids = $sids;

        return $sids;
    }

    /**
     * Constructs the query used for looking up all security identities.
     *
     * @return string
     */
    protected function getSelectAllSidsSql()
    {
        $sql = <<<SELECTCLAUSE
            SELECT
                s.username,
                s.identifier as security_identifier
            FROM
                {$this->options['sid_table_name']} s
SELECTCLAUSE;

        return $sql;
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
