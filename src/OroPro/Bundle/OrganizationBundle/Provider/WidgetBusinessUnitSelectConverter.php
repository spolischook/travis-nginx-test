<?php

namespace OroPro\Bundle\OrganizationBundle\Provider;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DashboardBundle\Provider\Converters\WidgetBusinessUnitSelectConverter as BaseConverter;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;

class WidgetBusinessUnitSelectConverter extends BaseConverter
{
    /**
     * @param array $config
     * @return array
     */
    public function getBusinessUnitList($config)
    {
        $aclClass = isset($config['aclClass']) ? $config['aclClass'] : null;
        $aclPermission = isset($config['aclPermission']) ? $config['aclPermission'] : null;
        $queryBuilder = $this->businessUnitRepository->createQueryBuilder('businessUnit');

        if ($aclClass && $aclPermission) {

            $businessUnitIds = $this
                ->businessUnitAclProvider
                ->getBusinessUnitIds($aclClass, $aclPermission);

            if (!is_array($businessUnitIds) || count($businessUnitIds) === 0) {
                $businessUnitIds = [0];
            }

            $this->applyConditions(
                $queryBuilder,
                $businessUnitIds,
                $this->businessUnitAclProvider->getProcessedEntityAccessLevel()
            );
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $businessUnitIds
     * @param string $accessLevel
     */
    protected function applyConditions(QueryBuilder $queryBuilder, $businessUnitIds, $accessLevel)
    {
        $isGlobal = $this->securityFacade->getOrganization()->getIsGlobal();
        $expr = $queryBuilder->expr();

        /**
         * 1. Global org and not system level - doesn't show BU
         * 2. Is not global - default platform behaviour with id in
         * 3. Global org and system level - show all BU
         */
        if ($isGlobal && $accessLevel < AccessLevel::SYSTEM_LEVEL) {
            $queryBuilder->andWhere($expr->eq('businessUnit.id', 0));
        } elseif (!$isGlobal) {
            $queryBuilder->andWhere($expr->in('businessUnit.id', $businessUnitIds));
        }
    }
}
