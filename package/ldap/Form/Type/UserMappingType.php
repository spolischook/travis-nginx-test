<?php

namespace OroCRMPro\Bundle\LDAPBundle\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use OroCRMPro\Bundle\LDAPBundle\ImportExport\Utils\LdapUtils;

class UserMappingType extends AbstractType
{
    const USER_CLASS = 'Oro\Bundle\UserBundle\Entity\User';

    /** @var Registry */
    protected $registry;

    /** @var ConfigProvider */
    private $importExportConfig;

    /** @var ConfigProvider */
    private $entityConfig;

    /** @var string[] */
    private $requiredFields = [
        LdapUtils::USERNAME_MAPPING_ATTRIBUTE,
        'email',
        'firstName',
        'lastName',
    ];

    /**
     * @param Registry       $registry
     * @param ConfigProvider $importExportConfig
     * @param ConfigProvider $entityConfig
     */
    public function __construct(
        Registry $registry,
        ConfigProvider $importExportConfig,
        ConfigProvider $entityConfig
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

        $fields = array_map(
            function ($fieldName) {
                $importExportConfig = $this->importExportConfig->getConfig(self::USER_CLASS, $fieldName);
                $entityConfig = $this->entityConfig->getConfig(self::USER_CLASS, $fieldName);

                $field = ['name' => $fieldName, 'options' => ['required' => false]];

                if ($importExportConfig->has('excluded') && $importExportConfig->get('excluded')) {
                    return null;
                }

                if ($fieldName == 'serialized_data') {
                    return null;
                }

                if (($importExportConfig->has('identity') && $importExportConfig->get('identity'))
                    || in_array($fieldName, $this->requiredFields)
                ) {
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
            },
            $fields
        );

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
        return 'orocrmpro_ldap_user_mapping';
    }
}
