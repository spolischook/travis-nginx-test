<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;

class OrganizationChoiceType extends AbstractType
{
    const NAME = 'oro_organization_choice_select2';

    /** @var OroEntityManager */
    protected $em;

    /** @var  ConfigManager */
    protected $configManager;

    /**
     * @param Registry      $doctrine
     * @param ConfigManager $configManager
     */
    public function __construct(
        Registry $doctrine,
        ConfigManager $configManager
    ) {
        $this->em            = $doctrine->getManager();
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $that    = $this;
        $choices = function (Options $options) use ($that) {
            return $that->getChoices();
        };

        $defaultConfigs = array(
            'placeholder' => 'oro.organization.form.choose_organization',
        );

        // this normalizer allows to add/override config options outside.
        $configsNormalizer = function (Options $options, $configs) use (&$defaultConfigs, $that) {
            return array_merge($defaultConfigs, $configs);
        };

        $resolver->setDefaults(
            [
                'choices'     => $choices,
                'empty_value' => '',
                'multiple'    => true,
                'configs'     => $defaultConfigs
            ]
        );
        $resolver->setNormalizers(
            [
                'configs' => $configsNormalizer
            ]
        );
    }

    /**
     * Returns a list of choices
     *
     * @return array of organizations: key = organization name, value = ChoiceListItem
     */
    public function getChoices()
    {
        $choices = [];

        /** @var OrganizationRepository $organizationRepository */
        $organizationRepository = $this->em->getRepository('OroOrganizationBundle:Organization');

        /** @var Organization[] $organizations */
        $organizations = $organizationRepository->findAll();
        foreach ($organizations as $organization) {
            $choices[$organization->getId()] = $organization->getName();
        }

        return $choices;
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if ($form->getParent()->getConfig()->hasOption('config_id')) {
            $configId = $form->getParent()->getConfig()->getOption('config_id');
            if ($configId instanceof FieldConfigId) {
                $entityClassName    = $configId->getClassName();
                $entityExtendConfig = $this->configManager->getProvider('extend')->getConfig($entityClassName);
                if ($entityExtendConfig->is('owner', 'Custom')) {
                    $entityOrgConfig = $this->configManager->getProvider('organization')->getConfig($entityClassName);
                    $applicable      = $entityOrgConfig->get('applicable', false, false);
                    if ($applicable && !$applicable['all']) {
                        $selected = $applicable['selective'];
                        $view->vars['choices'] = array_filter(
                            $view->vars['choices'],
                            function (ChoiceView $choiceViewItem) use ($selected) {
                                return in_array($choiceViewItem->data, $selected);
                            }
                        );
                    }
                }
            }
        }
    }


    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'genemu_jqueryselect2_choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
