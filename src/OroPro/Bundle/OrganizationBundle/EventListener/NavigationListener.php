<?php

namespace OroPro\Bundle\OrganizationBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\EntityBundle\EventListener\NavigationListener as BaseNavigationListener;

class NavigationListener extends BaseNavigationListener
{
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

                    if (!$applicable['all']) {
                        if (!in_array($this->securityFacade->getOrganizationId(), $applicable['selective'])) {
                            continue;
                        }
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
