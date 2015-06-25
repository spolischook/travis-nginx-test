<?php

namespace Oro\Bundle\LDAPBundle\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class UserMappingType extends AbstractType
{
    const USER_CLASS = 'Oro\Bundle\UserBundle\Entity\User';

    /** @var Registry */
    protected $registry;
    /** @var ConfigProviderInterface */
    private $importExportConfig;
    /** @var ConfigProviderInterface */
    private $entityConfig;

    /**
     * @param Registry                $registry
     * @param ConfigProviderInterface $importExportConfig
     * @param ConfigProviderInterface $entityConfig
     */
    public function __construct(
        Registry $registry,
        ConfigProviderInterface $importExportConfig,
        ConfigProviderInterface $entityConfig
    ) {
        $this->registry = $registry;
        $this->importExportConfig = $importExportConfig;
        $this->entityConfig = $entityConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $userManager = $this->getUserManager();
        $metadata = $userManager->getClassMetadata(static::USER_CLASS);
        $fields = $metadata->getFieldNames();

        $fields = array_map(function ($fieldName) {
            $importExportConfig = $this->importExportConfig->getConfig(self::USER_CLASS, $fieldName);
            $entityConfig = $this->entityConfig->getConfig(self::USER_CLASS, $fieldName);

            $field = ['name' => $fieldName, 'options' => ['required' => false]];

            if ($importExportConfig->has('excluded') && $importExportConfig->get('excluded')) {
                return null;
            }

            if ($fieldName == 'serialized_data') {
                return null;
            }

            if ($importExportConfig->has('identity') && $importExportConfig->get('identity')) {
                $field['options']['required'] = true;
                $field['options']['constraints'] = [new Assert\NotBlank()];
            }

            if ($entityConfig->has('label')) {
                $field['options']['label'] = $entityConfig->get('label');
            }

            if ($entityConfig->has('tooltip')) {
                $field['options']['tooltip'] = $entityConfig->get('tooltip');
            }

            return $field;
        }, $fields);

        $this->addFields($builder, array_filter($fields));
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $fields
     */
    protected function addFields(FormBuilderInterface $builder, array $fields)
    {
        foreach ($fields as $field) {
            $builder->add($field['name'], 'text', $field['options']);
        }
    }

    /**
     * @return EntityManager
     */
    protected function getUserManager()
    {
        return $this->registry->getManagerForClass(static::USER_CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_ldap_user_mapping';
    }
}
