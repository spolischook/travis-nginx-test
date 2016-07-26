<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension\Generator;

use CG\Generator\PhpMethod;
use CG\Generator\PhpProperty;
use CG\Generator\PhpParameter;

use Oro\Component\ConfigExpression\ExpressionInterface;
use Oro\Component\Layout\Loader\Generator\VisitContext;
use Oro\Component\Layout\Loader\Generator\LayoutUpdateGeneratorInterface;
use Oro\Component\Layout\Loader\Visitor\VisitorInterface;

class ConfigExpressionConditionVisitor implements VisitorInterface
{
    /** @var ExpressionInterface */
    protected $expression;

    /**
     * @param ExpressionInterface $expression
     */
    public function __construct(ExpressionInterface $expression)
    {
        $this->expression = $expression;
    }

    /**
     * {@inheritdoc}
     */
    public function startVisit(VisitContext $visitContext)
    {
        $writer = $visitContext->createWriter();
        $class  = $visitContext->getClass();

        $class->addInterfaceName('Oro\Component\ConfigExpression\ExpressionFactoryAwareInterface');
        $class->addInterfaceName('Oro\Component\Layout\IsApplicableLayoutUpdateInterface');

        $setFactoryMethod = PhpMethod::create('setExpressionFactory');
        $setFactoryMethod->addParameter(
            PhpParameter::create('expressionFactory')
                ->setType('Oro\Component\ConfigExpression\ExpressionFactoryInterface')
        );
        $setFactoryMethod->setBody($writer->write('$this->expressionFactory = $expressionFactory;')->getContent());
        $class->setMethod($setFactoryMethod);

        $factoryProperty = PhpProperty::create('expressionFactory');
        $factoryProperty->setVisibility(PhpProperty::VISIBILITY_PRIVATE);
        $class->setProperty($factoryProperty);

        $factoryProperty = PhpProperty::create('applicable');
        $factoryProperty->setVisibility(PhpProperty::VISIBILITY_PRIVATE);
        $factoryProperty->setDefaultValue(false);
        $class->setProperty($factoryProperty);

        $setFactoryMethod = PhpMethod::create('isApplicable');
        $setFactoryMethod->setBody($writer->reset()->write('return $this->applicable;')->getContent());
        $class->setMethod($setFactoryMethod);

        $visitContext->getUpdateMethodWriter()
            ->writeln('if (null === $this->expressionFactory) {')
            ->writeln('    throw new \\RuntimeException(\'Missing expression factory for layout update\');')
            ->writeln('}')
            ->writeln('')
            ->writeln(sprintf('$expr = %s;', $this->expression->compile('$this->expressionFactory')))
            ->writeln(
                sprintf(
                    '$context = [\'context\' => $%s->getContext()];',
                    LayoutUpdateGeneratorInterface::PARAM_LAYOUT_ITEM
                )
            )
            ->writeln('if ($expr->evaluate($context)) {')
            ->indent()
            ->writeln('$this->isApplicable = true;');
    }

    /**
     * {@inheritdoc}
     */
    public function endVisit(VisitContext $visitContext)
    {
        $visitContext->getUpdateMethodWriter()
            ->outdent()
            ->writeln('}');
    }
}
