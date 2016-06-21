<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationGmailType;

class ConfigurationGmailTypeExtension extends AbstractTypeExtension
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (!isset($view->vars['options'])) {
            $view->vars['options'] = ['viewOptions' => []];
        } elseif (!isset($view->vars['options']['viewOptions'])) {
            $view->vars['options']['viewOptions'] = [];
        }

        $organization = $this->securityFacade->getOrganization();
        $view->vars['options']['viewOptions']['isGlobalOrg'] = $organization && $organization->getIsGlobal();
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ConfigurationGmailType::NAME;
    }
}
