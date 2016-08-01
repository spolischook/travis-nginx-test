<?php

namespace OroB2B\Bundle\FrontendBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\FrontendBundle\DependencyInjection\OroB2BFrontendExtension;
use OroB2B\Bundle\FrontendBundle\DependencyInjection\CompilerPass\ExceptionControllerCompilerPass;

class OroB2BFrontendBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ExceptionControllerCompilerPass());
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroB2BFrontendExtension();
        }

        return $this->extension;
    }
}
