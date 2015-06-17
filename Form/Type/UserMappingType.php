<?php

namespace Oro\Bundle\LDAPBundle\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class UserMappingType extends AbstractType
{
    const USER_CLASS = 'Oro\Bundle\UserBundle\Entity\User';

    /** @var Registry */
    protected $registry;

    /** @var array */
    protected $requiredFields = [
        'username',
        'email',
    ];

    /** @var array */
    protected $allowedFields = [
        'username',
        'email',
        'phone',
        'name_prefix',
        'first_name',
        'middle_name',
        'last_name',
        'name_suffix',
        'birthday',
    ];

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $userManager = $this->getUserManager();
        $metadata = $userManager->getClassMetadata(static::USER_CLASS);
        $fields = array_intersect($metadata->getColumnNames(), $this->allowedFields);
        $notRequiredFields = array_diff($fields, $this->requiredFields);

        $requiredOptions = [
            'required'    => true,
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ];

        $this->addFields($builder, $this->requiredFields, $requiredOptions);
        $this->addFields($builder, $notRequiredFields);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $fields
     * @param array $options
     */
    protected function addFields(FormBuilderInterface $builder, array $fields, array $options = [])
    {
        foreach ($fields as $field) {
            $fieldOptions = array_merge(
                [
                    'label'    => "oro.user.$field.label",
                    'tooltip'  => "user_mapping.tooltip",
                    'required' => false,
                ],
                $options
            );
            $builder->add($field, 'text', $fieldOptions);
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
