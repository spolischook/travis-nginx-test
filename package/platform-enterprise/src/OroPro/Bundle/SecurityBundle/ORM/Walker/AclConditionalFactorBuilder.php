<?php

namespace OroPro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\AST\ArithmeticExpression;
use Doctrine\ORM\Query\AST\ComparisonExpression;
use Doctrine\ORM\Query\AST\ConditionalExpression;
use Doctrine\ORM\Query\AST\ConditionalPrimary;
use Doctrine\ORM\Query\AST\ConditionalTerm;
use Doctrine\ORM\Query\AST\ExistsExpression;
use Doctrine\ORM\Query\AST\IdentificationVariableDeclaration;
use Doctrine\ORM\Query\AST\Literal;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\RangeVariableDeclaration;
use Doctrine\ORM\Query\AST\SimpleSelectClause;
use Doctrine\ORM\Query\AST\SimpleSelectExpression;
use Doctrine\ORM\Query\AST\Subselect;
use Doctrine\ORM\Query\AST\SubselectFromClause;
use Doctrine\ORM\Query\AST\WhereClause;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclConditionalFactorBuilder as OroAclConditionalFactorBuilder;
use Oro\Bundle\SecurityBundle\ORM\Walker\Condition\AclCondition;

/**
 * Add Share condition to dql query.
 */
class AclConditionalFactorBuilder extends OroAclConditionalFactorBuilder
{
    const ORO_ACL_SHARE_CONDITION = 'oro_acl.share.condition';

    /**
     * {@inheritdoc}
     */
    public function addWhereAclConditionalFactors(
        array $aclConditionalFactors,
        array $conditions,
        AbstractQuery $query,
        array $options = null
    ) {
        $aclConditionalFactors = parent::addWhereAclConditionalFactors(
            $aclConditionalFactors,
            $conditions,
            $query,
            $options
        );

        $whereShareCondition = null;

        if ($query->hasHint(self::ORO_ACL_SHARE_CONDITION)) {
            $whereShareCondition = $query->getHint(self::ORO_ACL_SHARE_CONDITION);
        }

        if (!empty($aclConditionalFactors) && $whereShareCondition) {
            $aclConditionalFactors = $this->addShareFactor($aclConditionalFactors, $whereShareCondition);
        }

        return $aclConditionalFactors;
    }

    /**
     * @param array $aclConditionalFactors
     * @param array $whereShareCondition
     *
     * @return array
     */
    protected function addShareFactor(array $aclConditionalFactors, array $whereShareCondition)
    {
        $prShareCondition = new ConditionalPrimary();
        $prShareCondition->simpleConditionalExpression = $this->buildShareCondition($whereShareCondition);
        $orgCondition = new ConditionalPrimary();
        $ownershipCondition = new ConditionalTerm($aclConditionalFactors);
        $orgCondition->conditionalExpression = new ConditionalExpression([$ownershipCondition, $prShareCondition]);
        return [$orgCondition];
    }

    /**
     * @param $data
     *
     * @return ExistsExpression
     */
    protected function buildShareCondition($data)
    {
        $literal = new Literal(Literal::NUMERIC, $data['existsSubselect']['select']);
        $simpleSelectEx = new SimpleSelectExpression($literal);
        $simpleSelect = new SimpleSelectClause($simpleSelectEx, false);

        $rangeVarDeclaration = new RangeVariableDeclaration(
            $data['existsSubselect']['from']['schemaName'],
            $data['existsSubselect']['from']['alias'],
            true
        );
        $idVarDeclaration = new IdentificationVariableDeclaration($rangeVarDeclaration, null, []);

        $subSelectFrom = new SubselectFromClause([$idVarDeclaration]);
        $subSelect = new Subselect($simpleSelect, $subSelectFrom);

        $shareCondition = new ExistsExpression($subSelect);
        $shareCondition->not = $data['not'];

        $subSelect->whereClause = new WhereClause(
            new ConditionalTerm($this->buildSubselectWhereConditions($data['existsSubselect']['where']))
        );

        return $shareCondition;
    }

    /**
     * @param $data
     *
     * @return array
     */
    protected function buildSubselectWhereConditions($data)
    {
        //Make expr rootEntity.id = acl_entries.record_id
        $condition = $data[0];
        $leftExpression = new ArithmeticExpression();
        $aclCondition = new AclCondition(
            $condition['left']['entityAlias'],
            $condition['left']['field'],
            null,
            $condition['left']['typeOperand']
        );
        $leftExpression->simpleArithmeticExpression = $this->getPathExpression($aclCondition);
        $rightExpression = new ArithmeticExpression();
        $aclCondition = new AclCondition(
            $condition['right']['entityAlias'],
            $condition['right']['field'],
            null,
            $condition['right']['typeOperand']
        );
        $rightExpression->simpleArithmeticExpression = $this->getPathExpression($aclCondition);
        $resultCondition      = new ConditionalPrimary();
        $resultCondition->simpleConditionalExpression =
            new ComparisonExpression($leftExpression, $condition['operation'], $rightExpression);
        $conditionalFactors[] = $resultCondition;

        //Make expr acl_entries.security_identity_id IN (?) or acl_entries.security_identity_id = ?
        $condition = $data[1];
        $aclCondition = new AclCondition(
            $condition['left']['entityAlias'],
            $condition['left']['field'],
            $condition['right']['value'],
            $condition['left']['typeOperand']
        );

        if ($condition['operation'] === 'IN') {
            $condition = new ConditionalPrimary();
            $condition->simpleConditionalExpression = $this->getInExpression($aclCondition);
            $conditionalFactors[] = $condition;

        } else {
            $pathExpression = $this->getPathExpression($aclCondition);
            $conditionalFactors[] = $this->getLiteralComparisonExpression(
                $pathExpression,
                $condition['right']['value'],
                $condition['operation']
            );
        }

        //Make expr acl_entries.class_id = ?
        $condition = $data[2];
        $aclCondition = new AclCondition(
            $condition['left']['entityAlias'],
            $condition['left']['field'],
            $condition['right']['value'],
            $condition['left']['typeOperand']
        );
        $pathExpression = $this->getPathExpression($aclCondition);
        $conditionalFactors[] = $this->getLiteralComparisonExpression(
            $pathExpression,
            $condition['right']['value'],
            $condition['operation']
        );

        return $conditionalFactors;
    }

    /**
     * @param PathExpression $pathExpression
     * @param mixed          $value
     * @param string         $operation
     * @param int            $type
     *
     * @return ConditionalPrimary
     */
    protected function getLiteralComparisonExpression(
        PathExpression $pathExpression,
        $value,
        $operation,
        $type = Literal::NUMERIC
    ) {
        $resultCondition = new ConditionalPrimary();
        $leftExpression = new ArithmeticExpression();
        $leftExpression->simpleArithmeticExpression = $pathExpression;
        $rightExpression  = new ArithmeticExpression();
        $rightExpression->simpleArithmeticExpression = new Literal($type, $value);
        $resultCondition->simpleConditionalExpression =
            new ComparisonExpression($leftExpression, $operation, $rightExpression);

        return $resultCondition;
    }
}
