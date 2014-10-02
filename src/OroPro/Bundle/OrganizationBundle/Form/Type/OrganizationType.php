<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;

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

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'all',
            'checkbox',
            [
                'label'      => 'All',
                'empty_data' => true,
                'required'   => false,
            ]
        );
        $builder->add(
            'selective',
            'choice',
            [
                'label'    => 'Selective',
                'multiple' => true,
                'expanded' => true,
                'choices'  => $this->getOptions(),
                'required' => false,
            ]
        );

    }

    /**
     * Prepare choice options for a select
     * @return array
     */
    protected function getOptions()
    {
        /** @var OrganizationRepository $organizationRepository */
        $organizationRepository = $this->em->getRepository('OroOrganizationBundle:Organization');

        return $organizationRepository->findAll();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
