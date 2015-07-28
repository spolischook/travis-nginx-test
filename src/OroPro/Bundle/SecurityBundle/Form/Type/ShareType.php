<?php

namespace OroPro\Bundle\SecurityBundle\Form\Type;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\FormBundle\Form\DataTransformer\EntitiesToIdsTransformer;
use Oro\Bundle\SecurityBundle\Form\Type\ShareType as BaseType;

class ShareType extends BaseType
{
    /** @var EntityManager */
    protected $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add(
            'organizations',
            'oro_organization_select',
            [
                'label' => 'oro.organization.entity_plural_label',
                'configs' => [
                    'placeholder' => 'oro.organization.form.choose_organization',
                    'allowClear' => true,
                    'multiple' => true,
                ]
            ]
        );
        $builder->get('organizations')->addModelTransformer(
            new EntitiesToIdsTransformer($this->entityManager, 'Oro\Bundle\OrganizationBundle\Entity\Organization')
        );
    }
}
