<?php

namespace OroPro\Bundle\SecurityBundle\Request\ParamConverter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Request\ParamConverter\DoctrineParamConverter;

use OroPro\Bundle\OrganizationBundle\Provider\OrganizationIdProvider;

class DoctrineParamProConverter extends DoctrineParamConverter
{
    /** @var OrganizationIdProvider */
    protected $organizationIdProvider;

    /**
     * @var ServiceLink
     */
    protected $metadataProviderLink;

    /**
     * @param OrganizationIdProvider $organizationIdProvider
     */
    public function setOrganizationIdProvider(OrganizationIdProvider $organizationIdProvider)
    {
        $this->organizationIdProvider = $organizationIdProvider;
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

        if ($this->securityFacade && $isSet) {
            $acl = $this->securityFacade->getRequestAcl($request, true);

            // set entity organization to the organization provider in case of edit page
            if ($acl->getPermission() === 'EDIT') {
                $object = $request->attributes->get($configuration->getName());
                $organizationField = $this->getMetadataProvider()
                    ->getMetadata($acl->getClass())
                    ->getOrganizationFieldName();
                if ($organizationField) {
                    $propertyAccessor  = PropertyAccess::createPropertyAccessor();
                    $this->organizationIdProvider->setOrganization(
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
