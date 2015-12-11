<?php

namespace OroPro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Query\AST\PathExpression;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;
use Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use OroPro\Bundle\SecurityBundle\Acl\Domain\BusinessUnitSecurityIdentity;
use OroPro\Bundle\SecurityBundle\Acl\Domain\OrganizationSecurityIdentity;
use OroPro\Bundle\SecurityBundle\Entity\AclClass;
use OroPro\Bundle\SecurityBundle\Form\Model\Share;

class ShareConditionDataBuilder
{
    const ACL_ENTRIES_SCHEMA_NAME = 'OroPro\Bundle\SecurityBundle\Entity\AclEntry';
    const ACL_ENTRIES_ALIAS = 'entries';
    const ACL_ENTRIES_SHARE_RECORD = 'recordId';
    const ACL_ENTRIES_CLASS_ID = 'class';
    const ACL_ENTRIES_SECURITY_ID = 'securityIdentity';

    /* @var TokenStorageInterface */
    protected $securityTokenStorage;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var SecurityIdentityRetrievalStrategyInterface */
    protected $sidStrategy;

    /** @var array|null */
    protected $sids = null;

    /**
     * @param TokenStorageInterface $securityTokenStorage
     * @param ManagerRegistry $registry
     * @param ConfigProvider $configProvider
     * @param SecurityIdentityRetrievalStrategyInterface $sidStrategy
     */
    public function __construct(
        TokenStorageInterface $securityTokenStorage,
        ManagerRegistry $registry,
        ConfigProvider $configProvider,
        SecurityIdentityRetrievalStrategyInterface $sidStrategy
    ) {
        $this->securityTokenStorage = $securityTokenStorage;
        $this->registry = $registry;
        $this->configProvider = $configProvider;
        $this->sidStrategy = $sidStrategy;
    }

    /**
     * Get ACL sql conditions to check shared records
     *
     * @param string $entityName
     * @param string $entityAlias
     * @param mixed $permissions
     *
     * @return array
     */
    public function getAclShareData($entityName, $entityAlias, $permissions = BasicPermissionMap::PERMISSION_VIEW)
    {
        //Updated if add ability to share record on Edit and other permissions
        if ($permissions !== BasicPermissionMap::PERMISSION_VIEW) {
            return null;
        }

        $aclClass = $this->getObjectManager()->getRepository('OroProSecurityBundle:AclClass')
            ->findOneBy(['classType' => $entityName]);

        if (!$aclClass) {
            return null;
        }

        $shareConfig = null;

        if ($this->configProvider->hasConfig($entityName)) {
            $shareConfig = $this->configProvider->getConfig($entityName)->get('share_scopes');
        }

        if (!$shareConfig) {
            return null;
        }

        $aclSIds = $this->getSecurityIdentityIds((array) $shareConfig);

        if (empty($aclSIds)) {
            return null;
        }

        $shareCondition = [
            'existsSubselect' => [
                'select' => 1,
                'from'   => ['schemaName' => self::ACL_ENTRIES_SCHEMA_NAME, 'alias' => self::ACL_ENTRIES_ALIAS],
                'where'  => $this->getShareSubselectWhereConditions($entityAlias, $aclSIds, $aclClass)
            ],
            'not'             => false,
        ];

        //Add query components for OutputSqlWalker
        $queryComponents[self::ACL_ENTRIES_ALIAS] = [
            'metadata'     => $this->getObjectManager()->getClassMetadata(
                self::ACL_ENTRIES_SCHEMA_NAME
            ),
            'parent'       => null,
            'relation'     => null,
            'map'          => null,
            'nestingLevel' => null,
            'token'        => null
        ];

        return [$shareCondition, $queryComponents];
    }

    /**
     * Get all Security Identity Ids
     *
     * @param array $shareScope
     *
     * @return array|int
     */
    protected function getSecurityIdentityIds(array $shareScope)
    {
        if ($this->sids !== null) {
            $sidIds = $this->getSecurityIdentityIdsByScope($this->sids, $shareScope);
            return count($sidIds) === 1 ? $sidIds[0] : $sidIds;
        }

        if (!$this->securityTokenStorage->getToken()) {
            return null;
        }

        $sids = $this->sidStrategy->getSecurityIdentities($this->securityTokenStorage->getToken());
        $sidByDb = [];

        foreach ($sids as $sid) {
            $entitySid = $this->getSecurityIdentityId($sid);
            if ($entitySid) {
                $sidByDb[$entitySid->getId()] = $sid;
            }
        }

        $this->sids = $sidByDb;
        $sidIds = $this->getSecurityIdentityIdsByScope($this->sids, $shareScope);

        return count($sidIds) === 1 ? $sidIds[0] : $sidIds;
    }

    /**
     * Get only Security Identity ids that can be shared by entity share scope
     *
     * @param array $sids
     * @param array $shareScope
     *
     * @return array
     */
    protected function getSecurityIdentityIdsByScope(array $sids, array $shareScope)
    {
        $sidIds = [];

        foreach ($sids as $key => $sid) {
            $sharedToScope = false;

            if ($sid instanceof UserSecurityIdentity) {
                $sharedToScope = Share::SHARE_SCOPE_USER;
            } elseif ($sid instanceof BusinessUnitSecurityIdentity) {
                $sharedToScope = Share::SHARE_SCOPE_BUSINESS_UNIT;
            } elseif ($sid instanceof OrganizationSecurityIdentity) {
                $sharedToScope = Share::SHARE_SCOPE_ORGANIZATION;
            }

            if (in_array($sharedToScope, $shareScope)) {
                $sidIds[] = $key;
            }
        }

        return $sidIds;
    }

    /**
     * @param SecurityIdentityInterface $sid
     *
     * @return mixed
     */
    protected function getSecurityIdentityId(SecurityIdentityInterface $sid)
    {
        if ($sid instanceof UserSecurityIdentity) {
            $identifier = $sid->getClass() . '-' . $sid->getUsername();
            $username = true;
        } elseif ($sid instanceof RoleSecurityIdentity) {
            //skip Role SID because we didn't share records for Role
            return null;
        } elseif ($sid instanceof BusinessUnitSecurityIdentity || $sid instanceof OrganizationSecurityIdentity) {
            $identifier = $sid->getClass() . '-' . $sid->getId();
            $username = false;
        } else {
            throw new \InvalidArgumentException(
                '$sid must either be an instance of UserSecurityIdentity or RoleSecurityIdentity ' .
                'or BusinessUnitSecurityIdentity.'
            );
        }

        return $this->getObjectManager()->getRepository('OroProSecurityBundle:AclSecurityIdentity')
            ->findOneBy([
                'identifier' => $identifier,
                'username' => $username,
            ]);
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        return $this->registry->getManager();
    }

    /**
     * @param string    $entityAlias
     * @param int|array $aclSIds
     * @param AclClass  $aclClass
     *
     * @return array
     */
    protected function getShareSubselectWhereConditions($entityAlias, $aclSIds, AclClass $aclClass)
    {
        return [
            [
                'left' => [
                    'expectedType' => AclConditionalFactorBuilder::EXPECTED_TYPE,
                    'entityAlias' => $entityAlias,
                    'field' => 'id',
                    'typeOperand' => PathExpression::TYPE_STATE_FIELD
                ],
                'right' => [
                    'expectedType' => AclConditionalFactorBuilder::EXPECTED_TYPE,
                    'entityAlias' => self::ACL_ENTRIES_ALIAS,
                    'field' => self::ACL_ENTRIES_SHARE_RECORD,
                    'typeOperand' => PathExpression::TYPE_STATE_FIELD
                ],
                'operation' => '='
            ],
            [
                'left' => [
                    'expectedType' => AclConditionalFactorBuilder::EXPECTED_TYPE,
                    'entityAlias' => self::ACL_ENTRIES_ALIAS,
                    'field' => self::ACL_ENTRIES_SECURITY_ID,
                    'typeOperand' => PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION
                ],
                'right' => [
                    'value' => $aclSIds
                ],
                'operation' => is_array($aclSIds) ? 'IN' : '='
            ],
            [
                'left' => [
                    'expectedType' => AclConditionalFactorBuilder::EXPECTED_TYPE,
                    'entityAlias' => self::ACL_ENTRIES_ALIAS,
                    'field' => self::ACL_ENTRIES_CLASS_ID,
                    'typeOperand' => PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION
                ],
                'right' => [
                    'value' => $aclClass->getId()
                ],
                'operation' => '='
            ]
        ];
    }
}
