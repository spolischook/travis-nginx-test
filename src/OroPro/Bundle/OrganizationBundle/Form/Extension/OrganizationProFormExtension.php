<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Extension;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Bundle\OrganizationBundle\Form\Extension\OrganizationFormExtension;

class OrganizationProFormExtension extends OrganizationFormExtension
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function setSecurityFacade(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData'], 128);
        parent::buildForm($builder, $options);
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        if ($this->securityFacade->getOrganization()->getIsGlobal()) {
            if ($event->getForm()->getParent() === null && is_object($event->getData())) {
                if ($event->getData()->getId() === null) {
                    //throw new OrganizationAwareException();
                } else {
                    //Update process detected -> add organization selector (disabled)
                    $entity            = $event->getData();
                    $organizationField = $this->getMetadataProvider()->getMetadata(ClassUtils::getClass($entity))
                        ->getOrganizationFieldName();

                    $form = $event->getForm();
                    $form->add(
                        $organizationField,
                        'entity',
                        [
                            'class'                => 'OroOrganizationBundle:Organization',
                            'property'             => 'name',
                            'mapped'               => true,
                            'label'                => 'Organization',
                            'translatable_options' => false,
                            'read_only'            => true,
                            'disabled'             => true
                        ]
                    );
                }
            }
        }
    }

    /**
     * @return OwnershipMetadataProvider
     */
    protected function getMetadataProvider()
    {
        return $this->metadataProviderLink->getService();
    }
}
