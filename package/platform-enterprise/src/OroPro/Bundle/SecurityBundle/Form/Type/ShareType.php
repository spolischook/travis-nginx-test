<?php

namespace OroPro\Bundle\SecurityBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;

class ShareType extends AbstractType
{
    /** @var EntityClassNameHelper */
    protected $entityClassNameHelper;

    /**
     * @param EntityClassNameHelper $entityClassNameHelper
     */
    public function __construct(EntityClassNameHelper $entityClassNameHelper)
    {
        $this->entityClassNameHelper = $entityClassNameHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('entityClass', 'hidden', ['required' => false])
            ->add('entityId', 'hidden', ['required' => false])
            ->add(
                'entities',
                'oropro_share_select',
                [
                    'label' => 'oro.security.action.share_with',
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'         => 'OroPro\Bundle\SecurityBundle\Form\Model\Share',
                'intention'          => 'entities',
                'csrf_protection'    => false,
                'cascade_validation' => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oropro_share';
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['entityId'] = $form->get('entityId')->getData();
        $view->vars['entityClass'] = $form->get('entityClass')->getData();

        $routeParameters = isset($view->children['entities']->vars['configs']['route_parameters'])
            ? $view->children['entities']->vars['configs']['route_parameters']
            : [];
        $routeParameters['entityClass'] = $this->entityClassNameHelper->getUrlSafeClassName(
            $form->get('entityClass')->getData()
        );

        $view->children['entities']->vars['configs']['route_parameters'] = $routeParameters;
    }
}
