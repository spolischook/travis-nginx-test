<?php

namespace OroPro\Bundle\OrganizationBundle\Form\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent;

class OrganizationFieldListener
{
    const ORGANIZATION_FIELD_TEMPLATE = 'OroProOrganizationBundle::organizationSelector.html.twig';

    /** @var ConfigManager */
    protected $configManager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param ConfigManager  $configManager
     * @param SecurityFacade $securityFacade
     */
    public function __construct(ConfigManager $configManager, SecurityFacade $securityFacade)
    {
        $this->configManager  = $configManager;
        $this->securityFacade = $securityFacade;
    }

    /**
     * Add owner field to forms
     *
     * @param BeforeFormRenderEvent $event
     */
    public function addOrganizationField(BeforeFormRenderEvent $event)
    {
        if ($this->securityFacade->getOrganization()->getIsGlobal()) {
            $environment = $event->getTwigEnvironment();
            $data        = $event->getFormData();
            $form        = $event->getForm();

            $organizationField = $environment->render(
                self::ORGANIZATION_FIELD_TEMPLATE,
                [
                    'form'  => $form,
                    'label' => false
                ]
            );

            /**
             * Setting organization field as first field in first data block
             */
            if (!empty($data['dataBlocks'])) {
                if (isset($data['dataBlocks'][0]['subblocks'])) {
                    array_unshift($data['dataBlocks'][0]['subblocks'][0]['data'], $organizationField);
                }
            }

            $event->setFormData($data);
        }
    }
}
