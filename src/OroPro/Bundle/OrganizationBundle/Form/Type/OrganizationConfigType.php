<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;

/**
 * Used in EntityManagement to configure entity/field availability per organization
 */
class OrganizationConfigType extends AbstractType
{
    const NAME = 'oro_type_choice_organization_type';

    /** @var OroEntityManager */
    protected $em;

    /**
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'label' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'all',
            'checkbox',
            [
                'required' => false,
                'attr'     => [
                    'class' => 'all-selector',
                ]
            ]
        );
        $builder->add(
            'selective',
            'oro_organization_choice_select2',
            [
                'configs' => [
                    'containerCssClass' => 'organization-selective-selector'
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
