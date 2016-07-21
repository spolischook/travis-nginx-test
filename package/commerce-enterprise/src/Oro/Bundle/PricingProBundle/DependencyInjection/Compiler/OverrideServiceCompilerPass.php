<?php

namespace Oro\Bundle\PricingProBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OverrideServiceCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $serviceId = 'orob2b_pricing.event_listener.price_list_form_view';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->setClass('Oro\Bundle\PricingProBundle\EventListener\PriceListFormViewListener');
        }

        $serviceId = 'orob2b_pricing.event_listener.account_form_view';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->setClass('Oro\Bundle\PricingProBundle\EventListener\AccountFormViewListener');

            $definition->addArgument(new Reference('orob2b_website.website.provider'));
        }

        $serviceId = 'orob2b_pricing.event_listener.account_group_form_view';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->setClass('Oro\Bundle\PricingProBundle\EventListener\AccountGroupFormViewListener');
            
            $definition->addArgument(new Reference('orob2b_website.website.provider'));
        }

        $serviceId = 'orob2b_pricing.importexport.reader.price_list_product_prices';
        if ($container->hasDefinition($serviceId)) {
            $definition = $container->getDefinition($serviceId);
            $definition->setClass('Oro\Bundle\PricingProBundle\ImportExport\Reader\ProPriceListProductPricesReader');
            
            $definition->addMethodCall('setSecurityFacade', [new Reference('oro_security.security_facade')]);
        }
    }
}
