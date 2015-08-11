<?php

namespace OroCRMPro\Bundle\LDAPBundle\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

use OroCRMPro\Bundle\LDAPBundle\Form\DataTransformer\RolesToIdsTransformer;

class RoleMappingType extends AbstractType
{
    const ROLE_CLASS = 'Oro\Bundle\UserBundle\Entity\Role';

    /** @var Registry */
    protected $registry;

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
        $registry = $this->registry;

        $builder->add(
            'ldapName',
            'text',
            [
                'label' => 'orocrmpro.ldap.integration.transport.mapping.roleLdapName.label',
            ]
        );
        $builder->add(
            $builder->create(
                'crmRoles',
                'genemu_jqueryselect2_entity',
                [
                    'class'    => 'OroUserBundle:Role',
                    'property' => 'label',
                    'multiple' => true,
                    'label'    => 'orocrmpro.ldap.integration.transport.mapping.roleCrmName.label',
                    'query_builder' => function () use ($registry) {
                        $qb = $registry->getRepository('OroUserBundle:Role')
                            ->createQueryBuilder('r');
                        $qb->where('r.role != :role')
                            ->setParameter('role', User::ROLE_ANONYMOUS);

                        return $qb;
                    }
                ]
            )
                ->addModelTransformer(new RolesToIdsTransformer($this->getRoleManager(), static::ROLE_CLASS))
        );
    }

    /**
     * @return EntityManager
     */
    protected function getRoleManager()
    {
        return $this->registry->getManagerForClass(static::ROLE_CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'orocrmpro_ldap_role_mapping';
    }
}
