<?php
namespace Oro\Bundle\LDAPBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\LDAPBundle\Form\EventListener\LdapConnectorFormSubscriber;

class LdapTransportSettingsFormType extends AbstractType
{
    const NAME = 'oro_ldap_ldap_transport_setting_form_type';

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
            'server_hostname',
            'text',
            [
                'label' => 'oro.ldap.transport.ldap.fields.server_hostname.label',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ]
        );
        $builder->add(
            'server_port',
            'integer',
            [
                'label' => 'oro.ldap.transport.ldap.fields.server_port.label',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ]
        );
        $builder->add(
            'server_encryption',
            'oro_ldap_encryption',
            [
                'label' => 'oro.ldap.transport.ldap.fields.server_encryption.label',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ]
        );
        $builder->add(
            'server_base_dn',
            'text',
            [
                'label' => 'oro.ldap.transport.ldap.fields.server_base_dn.label',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ]
        );
        $builder->add(
            'admin_dn',
            'text',
            [
                'label' => 'oro.ldap.transport.ldap.fields.admin_dn.label',
                'required' => false,
            ]
        );
        $builder->add(
            'admin_password',
            'password',
            [
                'label' => 'oro.ldap.transport.ldap.fields.admin_password.label',
                'required' => false,
                'always_empty' => false,
            ]
        );
        $builder->add(
            'check',
            'oro_ldap_transport_check_button_type',
            [
                'label' => 'Check Connection',
            ]
        );
        // TODO: $builder->addEventSubscriber(new LdapConnectorFormSubscriber($this->registry));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => "Oro\Bundle\LDAPBundle\Entity\LdapTransport"
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
