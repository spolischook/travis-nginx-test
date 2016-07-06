<?php

namespace OroCRMPro\Bundle\OutlookBundle\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigApiManager as BaseConfigApiManager;
use OroCRMPro\Bundle\OutlookBundle\Api\Repository\OutlookConfigurationRepository;

class ConfigApiManager
{
    /** @var BaseConfigApiManager */
    protected $configManager;

    /** @var OutlookConfigurationRepository */
    protected $configRepository;

    /**
     * @param BaseConfigApiManager           $configManager
     * @param OutlookConfigurationRepository $configRepository
     */
    public function __construct(
        BaseConfigApiManager $configManager,
        OutlookConfigurationRepository $configRepository
    ) {
        $this->configManager = $configManager;
        $this->configRepository = $configRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getData($path, $scope = 'user')
    {
        $result = $this->configManager->getData($path, $scope);
        if ('outlook' === $path) {
            $options = $this->configRepository->getOutlookSectionOptions($scope);
            foreach ($options as $option) {
                $result[] = [
                    'key'   => $option->getKey(),
                    'type'  => $option->getDataType(),
                    'value' => $option->getValue()
                ];
            }
        }

        return $result;
    }
}
