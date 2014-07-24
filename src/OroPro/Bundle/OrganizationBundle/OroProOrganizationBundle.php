<?php

namespace OroPro\Bundle\OrganizationBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

//use OroPro\Bundle\OrganizationBundle\DependencyInjection\Compiler\OwnerDeletionManagerPass;

class OroProOrganizationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        //$container->addCompilerPass(new OwnerDeletionManagerPass());
    }
}
