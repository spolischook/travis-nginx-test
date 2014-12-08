<?php

namespace OroPro\Bundle\OrganizationBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent;

class FormListener
{
    const ORGANIZATION_FIELD_TEMPLATE = 'OroProOrganizationBundle::organization_field.html.twig';

    /** @var ConfigManager */
    protected $configManager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager, SecurityFacade $securityFacade)
    {
        $this->configManager = $configManager;
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
            $data = $event->getFormData();
            $form = $event->getForm();
            $label = false;
            $entityProvider = $this->configManager->getProvider('entity');

            /*if (is_object($form->vars['value'])) {
                $className = ClassUtils::getClass($form->vars['value']);
                if (class_exists($className)
                    && $entityProvider->hasConfig($className, 'owner')
                ) {
                    $config = $entityProvider->getConfig($className, 'owner');
                    $label  = $config->get('label');
                }
            }*/

            $organizationField = $environment->render(
                self::ORGANIZATION_FIELD_TEMPLATE,
                [
                    'form'  => $form,
                    'label' => $label
                ]
            );

            /**
             * Setting organization field as last field in first data block
             */
            if (!empty($data['dataBlocks'])) {
                if (isset($data['dataBlocks'][0]['subblocks'])) {
                    $data['dataBlocks'][0]['subblocks'][0]['data'][] = $organizationField;
                }
            }

            $event->setFormData($data);
        }
    }
}
