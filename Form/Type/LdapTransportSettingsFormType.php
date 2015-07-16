<?php
namespace OroCRMPro\Bundle\LDAPBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;

class LdapTransportSettingsFormType extends AbstractType
{
    const NAME = 'orocrmpro_ldap_ldap_transport_setting_form_type';
    const LABEL_PREFIX = 'orocrmpro.ldap.integration.channel.fields.';

    /** @var TypesRegistry */
    private $registry;

    /**
     * @param TypesRegistry $registry
     */
    public function __construct(TypesRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'host',
            'text',
            [
                'label'       => self::LABEL_PREFIX . 'host.label',
                'tooltip'     => self::LABEL_PREFIX . 'host.tooltip',
                'required'    => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ]
        );
        $builder->add(
            'port',
            'integer',
            [
                'label'       => self::LABEL_PREFIX . 'port.label',
                'tooltip'     => self::LABEL_PREFIX . 'port.tooltip',
                'required'    => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ]
        );
        $builder->add(
            'encryption',
            'orocrmpro_ldap_encryption',
            [
                'label'       => self::LABEL_PREFIX . 'encryption.label',
                'tooltip'     => self::LABEL_PREFIX . 'encryption.tooltip',
                'required'    => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ]
        );
        $builder->add(
            'baseDn',
            'text',
            [
                'label'       => self::LABEL_PREFIX . 'baseDn.label',
                'tooltip'     => self::LABEL_PREFIX . 'baseDn.tooltip',
                'required'    => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ]
        );
        $builder->add(
            'username',
            'text',
            [
                'label'    => self::LABEL_PREFIX . 'username.label',
                'tooltip'  => self::LABEL_PREFIX . 'username.tooltip',
                'required' => false,
            ]
        );
        $builder->add(
            'password',
            'password',
            [
                'label'        => self::LABEL_PREFIX . 'password.label',
                'tooltip'      => self::LABEL_PREFIX . 'password.tooltip',
                'required'     => false,
                'always_empty' => false,
            ]
        );
        $builder->add(
            'accountDomainName',
            'text',
            [
                'label'    => self::LABEL_PREFIX . 'accountDomainName.label',
                'tooltip'  => self::LABEL_PREFIX . 'accountDomainName.tooltip',
                'required' => false,
            ]
        );
        $builder->add(
            'accountDomainNameShort',
            'text',
            [
                'label'    => self::LABEL_PREFIX . 'accountDomainNameShort.label',
                'tooltip'  => self::LABEL_PREFIX . 'accountDomainNameShort.tooltip',
                'required' => false,
            ]
        );
        $builder->add(
            'connectionCheck',
            'orocrmpro_ldap_transport_check_button_type',
            [
                'label' => self::LABEL_PREFIX . 'connectionCheck.label',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'OroCRMPro\Bundle\LDAPBundle\Entity\LdapTransport',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
