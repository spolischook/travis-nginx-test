<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Form\Extension\OrganizationFormExtension;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

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
     * Add readonly organization field if user works in system access organization
     *
     * @param FormEvent $event
     * @throws OrganizationAwareException
     */
    public function preSetData(FormEvent $event)
    {
        if ($this->securityFacade->getOrganization() && $this->securityFacade->getOrganization()->getIsGlobal()) {
            if ($event->getForm()->getParent() === null && is_object($event->getData())) {
                $entity = $event->getData();
                list ($organizationField, $entityId) = $this->getEntityInfo($entity);
                if ($organizationField) {
                    if ($entityId === null && !$this->organizationProvider->getOrganizationId()) {
                        //we in create process without organization in organization Provider
                        throw new OrganizationAwareException();
                    } else {
                        //we in edit process or in create process with organization id in parameter
                        if ($entityId) {
                            if (!$this->organizationProvider->getOrganizationId()) {
                                //store current entity organization id if it was not set in param converter
                                $this->organizationProvider->setOrganization(
                                    $this->getOrganizationValue($entity, $organizationField)
                                );
                            }
                        } else {
                            // on create entity we should set selected organization
                            $organization = $this->organizationProvider->getOrganization();
                            $this->setOrganizationData($entity, $organizationField, $organization);
                        }

                        //  - add readonly organization field
                        $form = $event->getForm();
                        $form->add(
                            $organizationField,
                            'oropro_organization_label',
                            [
                                'class'                => 'OroOrganizationBundle:Organization',
                                'required'             => false,
                                'property'             => 'name',
                                'mapped'               => true,
                                'label'                => 'Organization',
                                'translatable_options' => false,
                                'read_only'            => true,
                                'disabled'             => true,
                                'data'                 => $this->organizationProvider->getOrganization()
                            ]
                        );
                    }
                }
            }
        }
    }


    /**
     * Get entity organization field value
     *
     * @param object $entity
     * @param string $organizationField
     * @return mixed
     */
    protected function getOrganizationValue($entity, $organizationField)
    {
        if ($entity instanceof WorkflowData) {
            $workflowData = $entity->getValues();
            foreach ($workflowData as $entityData) {
                if (is_object($entityData)) {
                    $entity            = $entityData;
                    $organizationField = $this->getMetadataProvider()
                        ->getMetadata($this->doctrineHelper->getEntityClass($entity))
                        ->getOrganizationFieldName();
                    if ($organizationField) {
                        return $this->getPropertyAccessor()->getValue($entity, $organizationField);
                    }
                }

            }
        } else {
            return $this->getPropertyAccessor()->getValue($entity, $organizationField);
        }

        return null;
    }

    /**
     * Set organization to entity. In case of Workflow gata, we should not set organization
     *
     * @param object       $entity
     * @param string       $organizationField
     * @param Organization $organization
     */
    protected function setOrganizationData($entity, $organizationField, Organization $organization)
    {
        if ($entity instanceof WorkflowData) {
            return;
        }

        $this->getPropertyAccessor()->setValue($entity, $organizationField, $organization);
    }

    /**
     * Get entity organization field and entity id
     *
     * @param $entity
     * @return array
     */
    protected function getEntityInfo($entity)
    {
        $organizationField = null;
        $entityId          = null;

        if ($entity instanceof WorkflowData) {
            $workflowData   = $entity->getValues();
            foreach ($workflowData as $key => $entityData) {
                if (is_object($entityData)) {
                    $entity            = $entityData;
                    $organizationField = $this->getMetadataProvider()
                        ->getMetadata($this->doctrineHelper->getEntityClass($entity))
                        ->getOrganizationFieldName();
                    if ($organizationField) {
                        $organizationField = $key . '_' . $organizationField;
                        break;
                    }
                }
            }

        } else {
            $entityClass       = $this->doctrineHelper->getEntityClass($entity);
            $organizationField = $this->getMetadataProvider()
                ->getMetadata($entityClass)
                ->getOrganizationFieldName();
        }

        if ($organizationField) {
            $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);
        }

        return [$organizationField, $entityId];
    }

    /**
     * @return OwnershipMetadataProvider
     */
    protected function getMetadataProvider()
    {
        return $this->metadataProviderLink->getService();
    }
}
