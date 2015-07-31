<?php

namespace OroCRMPro\Bundle\LDAPBundle\Twig;

use Oro\Bundle\EntityExtendBundle\Twig\DynamicFieldsExtension;
use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * Class DynamicFieldsExtension
 *
 * Decorator of dynamic fields twig extension.
 *
 * @package OroCRMPro\Bundle\LDAPBundle\Twig
 */
class LdapDynamicFieldsExtension extends \Twig_Extension
{
    /** @var DynamicFieldsExtension */
    private $baseExtension;

    /** @var SecurityFacade */
    private $securityFacade;

    /**
     * @param DynamicFieldsExtension $baseExtension
     * @param SecurityFacade             $securityFacade
     */
    public function __construct(DynamicFieldsExtension $baseExtension, SecurityFacade $securityFacade)
    {
        $this->baseExtension = $baseExtension;
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->baseExtension->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_get_dynamic_fields', [$this, 'getFields']),
        ];
    }

    /**
     * @param object      $entity
     * @param null|string $entityClass
     *
     * @return array
     */
    public function getFields($entity, $entityClass = null)
    {
        $fields = $this->baseExtension->getFields($entity, $entityClass);

        if (isset($fields['ldap_distinguished_names']) && !$this->securityFacade->isGranted('ROLE_ADMINISTRATOR')) {
            unset($fields['ldap_distinguished_names']);
        }

        return $fields;
    }
}
