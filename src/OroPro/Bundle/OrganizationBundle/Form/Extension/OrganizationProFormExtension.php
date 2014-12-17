<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\OrganizationBundle\Form\Extension\OrganizationFormExtension;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;
use OroPro\Bundle\OrganizationBundle\Exception\OrganizationAwareException;

class OrganizationProFormExtension extends OrganizationFormExtension
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var SystemAccessModeOrganizationProvider */
    protected $organizationProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param SystemAccessModeOrganizationProvider $organizationProvider
     */
    public function setOrganizationProvider(SystemAccessModeOrganizationProvider $organizationProvider)
    {
        $this->organizationProvider = $organizationProvider;
    }

    /**
     * @param SecurityFacade $securityFacade
     */
    public function setSecurityFacade(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
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
     * @throws OrganizationAwareException
     */
    public function preSetData(FormEvent $event)
    {
        if ($this->securityFacade->getOrganization() && $this->securityFacade->getOrganization()->getIsGlobal()) {
            if ($event->getForm()->getParent() === null && is_object($event->getData())) {
                $entity            = $event->getData();
                $entityClass = $this->doctrineHelper->getEntityClass($entity);
                $organizationField = $this->getMetadataProvider()
                    ->getMetadata($entityClass)
                    ->getOrganizationFieldName();
                if ($organizationField) {
                    $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);
                    if ($entityId === null && !$this->organizationProvider->getOrganizationId()) {
                        //we in create process without organization in organization Provider
                        throw new OrganizationAwareException($entityClass);
                    } else {
                        //we in edit process or in create process with organization id in parameter
                        if ($entityId) {
                            if (!$this->organizationProvider->getOrganizationId()) {
                                //store current entity organization id if it was not set in param converter
                                $this->organizationProvider->setOrganization(
                                    $this->getPropertyAccessor()->getValue($entity, $organizationField)
                                );
                            }
                        } else {
                            // on create entity we should set selected organization
                            $organization = $this->organizationProvider->getOrganization();
                            $this->getPropertyAccessor()->setValue($entity, $organizationField, $organization);
                        }

                        //  - add readonly organization field
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
    }

    /**
     * @return OwnershipMetadataProvider
     */
    protected function getMetadataProvider()
    {
        return $this->metadataProviderLink->getService();
    }
}
