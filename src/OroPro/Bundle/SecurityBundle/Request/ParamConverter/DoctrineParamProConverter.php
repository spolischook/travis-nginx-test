<?php

namespace OroPro\Bundle\SecurityBundle\Request\ParamConverter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Request\ParamConverter\DoctrineParamConverter;

use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;

class DoctrineParamProConverter extends DoctrineParamConverter
{
    /** @var SystemAccessModeOrganizationProvider */
    protected $organizationProvider;

    /** @var ServiceLink */
    protected $metadataProviderLink;

    /**
     * @param SystemAccessModeOrganizationProvider $organizationProvider
     */
    public function setOrganizationProvider(SystemAccessModeOrganizationProvider $organizationProvider)
    {
        $this->organizationProvider = $organizationProvider;
    }

    /**
     * @param ServiceLink $metadataProviderLink
     */
    public function setMetadataProviderLink(ServiceLink $metadataProviderLink)
    {
        $this->metadataProviderLink = $metadataProviderLink;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Request $request, ConfigurationInterface $configuration)
    {
        $isSet = parent::apply($request, $configuration);

        if ($this->securityFacade
            && $isSet
            && $this->securityFacade->getOrganization()
            && $this->securityFacade->getOrganization()->getIsGlobal()
        ) {
            $acl = $this->securityFacade->getRequestAcl($request, true);

            // set entity organization to the organization provider in case of edit page
            if ($acl && $acl->getPermission() === 'EDIT') {
                $object = $request->attributes->get($configuration->getName());
                $organizationField = $this->getMetadataProvider()
                    ->getMetadata($acl->getClass())
                    ->getOrganizationFieldName();
                if ($organizationField) {
                    $propertyAccessor  = PropertyAccess::createPropertyAccessor();
                    $this->organizationProvider->setOrganization(
                        $propertyAccessor->getValue($object, $organizationField)
                    );
                }
            }
        }

        return $isSet;
    }

    /**
     * @return OwnershipMetadataProvider
     */
    protected function getMetadataProvider()
    {
        return $this->metadataProviderLink->getService();
    }
}
