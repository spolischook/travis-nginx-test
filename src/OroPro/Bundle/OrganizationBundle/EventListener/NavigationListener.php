<?php

namespace OroPro\Bundle\OrganizationBundle\EventListener;

use Symfony\Component\Translation\Translator;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class NavigationListener
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /** @var ConfigManager $configManager */
    protected $configManager;

    /** @var  Translator */
    protected $translator;

    /**
     * @param SecurityFacade $securityFacade
     * @param ConfigManager  $configManager
     * @param Translator     $translator
     */
    public function __construct(
        SecurityFacade $securityFacade,
        ConfigManager $configManager,
        Translator $translator
    ) {
        $this->securityFacade       = $securityFacade;
        $this->configManager        = $configManager;
        $this->translator           = $translator;
    }

    /**
     * @param ConfigureMenuEvent $event
     */
    public function onNavigationConfigure(ConfigureMenuEvent $event)
    {
        $menu     = $event->getMenu();
        $children = array();

        $entitiesMenuItem = $menu->getChild('system_tab')->getChild('entities_list');
        if ($entitiesMenuItem) {
            /** @var ConfigProvider $entityConfigProvider */
            $entityConfigProvider = $this->configManager->getProvider('entity');

            /** @var ConfigProvider $entityExtendProvider */
            $entityExtendProvider = $this->configManager->getProvider('extend');

            /** @var ConfigProvider $organizationConfigProvider */
            $organizationConfigProvider = $this->configManager->getProvider('organization');

            $extendConfigs = $entityExtendProvider->getConfigs();

            foreach ($extendConfigs as $extendConfig) {
                if ($extendConfig->is('is_extend')
                    && $extendConfig->get('owner') == ExtendScope::OWNER_CUSTOM
                    && $extendConfig->in(
                        'state',
                        [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]
                    )
                ) {
                    $className          = $extendConfig->getId()->getClassname();
                    $config             = $entityConfigProvider->getConfig($className);
                    $organizationConfig = $organizationConfigProvider->getConfig($className);

                    if (!class_exists($config->getId()->getClassName()) ||
                        !$this->securityFacade->hasLoggedUser() ||
                        !$this->securityFacade->isGranted('VIEW', 'entity:' . $config->getId()->getClassName()) ||
                        !$organizationConfig->has('applicable')
                    ) {
                        continue;
                    }

                    $applicable = $organizationConfig->get('applicable');
                    if (!in_array($this->securityFacade->getOrganizationId(), $applicable['selective'])) {
                        continue;
                    }

                    $children[$config->get('label')] = array(
                        'label'   => $this->translator->trans($config->get('label')),
                        'options' => array(
                            'route'           => 'oro_entity_index',
                            'routeParameters' => array(
                                'entityName' => str_replace('\\', '_', $config->getId()->getClassName())
                            ),
                            'extras'          => array(
                                'safe_label' => true,
                                'routes'     => array('oro_entity_*')
                            ),
                        )
                    );
                }
            }

            sort($children);
            foreach ($children as $child) {
                $entitiesMenuItem->addChild($child['label'], $child['options']);
            }
        }

    }
}
