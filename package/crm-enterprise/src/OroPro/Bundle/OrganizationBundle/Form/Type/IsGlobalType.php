<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use OroPro\Bundle\OrganizationBundle\Helper\OrganizationProHelper;

/**
 * This form type allows to set organization as Global and checks that the system contains only only one such
 * organization.
 *
 * Please do not use this form extension anywhere because of custom validation logic
 *
 * Class IsGlobalType
 * @package OroPro\Bundle\OrganizationBundle\Form\Type
 */
class IsGlobalType extends AbstractType
{
    const NAME = 'oropro_organization_is_global';

    const INVALID_GLOBAL_MESSAGE = 'System cannot have more than one global organization';

    /** @var OrganizationProHelper */
    protected $organizationHelper;

    /**
     * @param OrganizationProHelper $organizationHelper
     */
    public function __construct(OrganizationProHelper $organizationHelper)
    {
        $this->organizationHelper = $organizationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $organizationHelper = $this->organizationHelper;

        // if entity was marked as global, we should check if existing global organization have the same id
        $callback = function ($value, ExecutionContextInterface $context) use ($organizationHelper) {
            /** @var OrganizationProHelper $organizationHelper */
            $globalOrgId = $organizationHelper->getGlobalOrganizationId();
            if (!empty($value)
                && $value == 1
                && !is_null($globalOrgId)
                && $globalOrgId !== $context->getRoot()->getData()->getId()
            ) {
                $context->addViolation(self::INVALID_GLOBAL_MESSAGE);
            }
        };

        $resolver->setDefaults(
            [
                'tooltip'     => 'oropro.organization.form.is_global',
                'empty_value' => false,
                'choices'     => ['No', 'Yes'],
                'constraints' => new Assert\Callback([$callback])
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        // if we already have global organization - we should disable select
        $disabled = $this->organizationHelper->isGlobalOrganizationExists();

        // if user edit current global organization - he should be able to set this organization as non global
        if ($form->getViewData() == 1) {
            $disabled = false;
        }

        $view->vars['disabled'] = $disabled;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
