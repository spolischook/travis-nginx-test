<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class LoadConfigData extends AbstractFixture
{
    /**
     * @return array
     */
    public function getData()
    {
        return [
            'configs' => $this->loadData('configs.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_config.global');

        foreach ($data['configs'] as $config) {
            $configManager->set($config['name'], $config['value']);
        }
        $configManager->flush();
    }
}
