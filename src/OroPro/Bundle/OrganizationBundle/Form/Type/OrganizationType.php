<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;

/**
 * Class OrganizationType
 * @package OroPro\Bundle\OrganizationBundle\Form\Type
 *
 * Used in EntityManagement to configure entity/field availability per organization
 */
class OrganizationType extends AbstractType
{
    const NAME = 'oro_type_choice_organization_type';

    /** @var EntityManager */
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
                'required'   => false,
                'attr'       => [
                    'class' => 'all-selector pull-left',
                ]
            ]
        );
        $builder->add(
            'selective',
            'choice',
            [
                'multiple' => true,
                'expanded' => true,
                'choices'  => $this->getOptions(),
                'required' => false,
                'attr'     => [
                    'class' => 'selective-selector'
                ]
            ]
        );
    }

    /**
     * Prepare choice options for a select
     * @return array
     */
    public function getOptions()
    {
        $options = [];

        /** @var OrganizationRepository $organizationRepository */
        $organizationRepository = $this->em->getRepository('OroOrganizationBundle:Organization');

        /** @var Organization[] $organizations */
        $organizations = $organizationRepository->findAll();
        foreach ($organizations as $organization) {
            $options[$organization->getId()] = $organization->getName();
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
