<?php

namespace OroPro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\Subselect;
use Doctrine\ORM\Query\AST\RangeVariableDeclaration;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper as OroAclHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclWalker;

/**
 * Class build share condition and put it into query
 */
class AclHelper extends OroAclHelper
{
    const ORO_ACL_OUTPUT_SQL_WALKER = 'OroPro\Bundle\SecurityBundle\ORM\Walker\SqlWalker';

    /** @var array */
    protected $queryComponents = [];

    /** @var ShareConditionDataBuilder */
    protected $shareDataBuilder;

    public function setShareDataBuilder(ShareConditionDataBuilder $shareDataBuilder)
    {
        $this->shareDataBuilder = $shareDataBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function apply($query, $permission = 'VIEW', $checkRelations = true)
    {
        /** @var Query $query */
        $query = parent::apply($query, $permission, $checkRelations);

        if (!$query->hasHint(AclWalker::ORO_ACL_CONDITION)) {
            return $query;
        }

        $this->queryComponents = [];

        $ast = $query->getAST();
        if ($ast instanceof SelectStatement) {
            $shareCondition = $this->processShareSelect($ast, $permission);

            $this->addShareConditionToQuery($query, $shareCondition);

            if (!empty($this->queryComponents)) {
                $query->setHint(SqlWalker::ORO_ACL_QUERY_COMPONENTS, $this->queryComponents);
                $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::ORO_ACL_OUTPUT_SQL_WALKER);
            }
        }

        return $query;
    }

    /**
     * Check request
     *
     * @param Subselect|SelectStatement $select
     * @param string                    $permission
     *
     * @return array
     */
    protected function processShareSelect($select, $permission)
    {
        if ($select instanceof SelectStatement) {
            $isSubRequest = false;
        } else {
            $isSubRequest = true;
        }

        $shareCondition = null;
        $fromClause = $isSubRequest ? $select->subselectFromClause : $select->fromClause;

        foreach ($fromClause->identificationVariableDeclarations as $fromKey => $identificationVariableDeclaration) {
            $shareCondition = $this->processRangeVariableDeclarationShare(
                $identificationVariableDeclaration->rangeVariableDeclaration,
                $permission
            );
        }

        return $shareCondition;
    }

    /**
     * Process where and join statements
     *
     * @param RangeVariableDeclaration $rangeVariableDeclaration
     * @param string                   $permission
     *
     * @return Node|null
     */
    protected function processRangeVariableDeclarationShare(
        RangeVariableDeclaration $rangeVariableDeclaration,
        $permission
    ) {
        $entityName = $rangeVariableDeclaration->abstractSchemaName;
        $entityAlias = $rangeVariableDeclaration->aliasIdentificationVariable;

        $resultData = $this->shareDataBuilder->getAclShareData($entityName, $entityAlias, $permission);

        if (!empty($resultData)) {
            list($shareCondition, $queryComponents) = $resultData;
            $this->addQueryComponents($queryComponents);
            return $shareCondition;
        }

        return null;
    }

    /**
     * Add query components which will add to query hints
     *
     * @param array $queryComponents
     * @throws QueryException
     */
    protected function addQueryComponents(array $queryComponents)
    {
        $requiredKeys = array('metadata', 'parent', 'relation', 'map', 'nestingLevel', 'token');

        foreach ($queryComponents as $dqlAlias => $queryComponent) {
            if (array_diff($requiredKeys, array_keys($queryComponent))) {
                throw QueryException::invalidQueryComponent($dqlAlias);
            }

            $this->queryComponents[$dqlAlias] = $queryComponent;
        }
    }

    /**
     * Add to query share condition
     *
     * @param Query     $query
     * @param array $shareCondition
     */
    protected function addShareConditionToQuery(Query $query, $shareCondition)
    {
        if ($shareCondition) {
            $hints = $query->getHints();
            if (!empty($hints[Query::HINT_CUSTOM_TREE_WALKERS])) {
                $customHints = !in_array(self::ORO_ACL_WALKER, $hints[Query::HINT_CUSTOM_TREE_WALKERS])
                    ? array_merge($hints[Query::HINT_CUSTOM_TREE_WALKERS], [self::ORO_ACL_WALKER])
                    : $hints[Query::HINT_CUSTOM_TREE_WALKERS];
            } else {
                $customHints = [self::ORO_ACL_WALKER];
            }
            $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, $customHints);
            $query->setHint(AclConditionalFactorBuilder::ORO_ACL_SHARE_CONDITION, $shareCondition);
        }
    }
}
