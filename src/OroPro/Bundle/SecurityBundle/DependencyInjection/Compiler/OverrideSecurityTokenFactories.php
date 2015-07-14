<?php

namespace OroPro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OverrideSecurityTokenFactories implements CompilerPassInterface
{
    /**
     * @var array
     */
    // @codingStandardsIgnoreStart
    private static $proTokenFactories = [
        'oro_sso.token.factory.oauth' => 'OroPro\Bundle\SecurityBundle\Tokens\ProOAuthTokenFactory',
        'oro_security.token.factory.organization_rememberme' => 'OroPro\Bundle\SecurityBundle\Tokens\ProOrganizationRememberMeTokenFactory',
        'oro_security.token.factory.username_password_organization'  => 'OroPro\Bundle\SecurityBundle\Tokens\ProUsernamePasswordOrganizationTokenFactory',
        'oro_user.token.factory.wsse' => 'OroPro\Bundle\SecurityBundle\Tokens\ProWsseTokenFactory'
    ];
    // @codingStandardsIgnoreEnd

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach (self::$proTokenFactories as $tokenFactoryServiceId => $proTokenFactoryClass) {
            if ($container->hasDefinition($tokenFactoryServiceId)) {
                $definition = $container->getDefinition($tokenFactoryServiceId);
                $definition->setClass($proTokenFactoryClass);
            }
        }
    }
}
