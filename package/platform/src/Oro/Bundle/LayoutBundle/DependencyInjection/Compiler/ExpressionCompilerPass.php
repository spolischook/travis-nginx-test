<?php

namespace Oro\Bundle\LayoutBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ExpressionCompilerPass implements CompilerPassInterface
{
    const EXPRESSION_ENCODER_TAG = 'oro_layout.expression.encoder';
    const EXPRESSION_ENCODING_SERVICE = 'oro_layout.expression.encoder_registry';

    const EXPRESSION_LANGUAGE_PROVIDER_TAG = 'oro_layout.expression_language_provider';
    const EXPRESSION_LANGUAGE_SERVICE = 'oro_layout.expression_language';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $this->loadExpressionEncoders($container);
        $this->loadExpressionLanguageFunctions($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function loadExpressionEncoders(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::EXPRESSION_ENCODING_SERVICE)) {
            return;
        }

        $serviceIds       = [];
        $taggedServiceIds = $container->findTaggedServiceIds(self::EXPRESSION_ENCODER_TAG);
        foreach ($taggedServiceIds as $id => $attributes) {
            foreach ($attributes as $attr) {
                $serviceIds[$attr['format']] = $id;
            }
        }

        $encodingServiceDef = $container->getDefinition(self::EXPRESSION_ENCODING_SERVICE);
        $encodingServiceDef->replaceArgument(1, $serviceIds);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function loadExpressionLanguageFunctions(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::EXPRESSION_LANGUAGE_SERVICE)) {
            return;
        }

        $providers = [];
        $taggedServiceIds = $container->findTaggedServiceIds(self::EXPRESSION_LANGUAGE_PROVIDER_TAG);
        foreach ($taggedServiceIds as $id => $attributes) {
            $providers[] = new Reference($id);
        }

        $expressionServiceDef = $container->getDefinition(self::EXPRESSION_LANGUAGE_SERVICE);
        $expressionServiceDef->replaceArgument(1, $providers);
    }
}
