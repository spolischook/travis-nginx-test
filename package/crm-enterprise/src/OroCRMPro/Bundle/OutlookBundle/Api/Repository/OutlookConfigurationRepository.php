<?php

namespace OroCRMPro\Bundle\OutlookBundle\Api\Repository;

use Symfony\Bridge\Twig\Extension\HttpFoundationExtension;

use Oro\Bundle\ConfigBundle\Api\Model\ConfigurationOption;
use Oro\Bundle\PlatformBundle\Composer\VersionHelper;
use OroCRM\Bundle\CRMBundle\OroCRMBundle;
use OroCRMPro\Bundle\OutlookBundle\Manager\AddInManager;

class OutlookConfigurationRepository
{
    /** @var VersionHelper */
    protected $versionHelper;

    /** @var AddInManager */
    protected $addInManager;

    /** @var HttpFoundationExtension */
    protected $urlHelper;

    /**
     * @param VersionHelper           $versionHelper
     * @param AddInManager            $addInManager
     * @param HttpFoundationExtension $urlHelper
     */
    public function __construct(
        VersionHelper $versionHelper,
        AddInManager $addInManager,
        HttpFoundationExtension $urlHelper
    ) {
        $this->versionHelper = $versionHelper;
        $this->addInManager = $addInManager;
        $this->urlHelper = $urlHelper;
    }

    /**
     * Returns additional configuration options for "outlook" section.
     *
     * @param string $scope
     *
     * @return ConfigurationOption[]
     */
    public function getOutlookSectionOptions($scope)
    {
        $result = [];
        // OroCRM version
        $result[] = $this->createConfigurationOption(
            $scope,
            'oro_crm_pro_outlook.orocrm_version',
            'string',
            $this->versionHelper->getVersion(OroCRMBundle::PACKAGE_NAME)
        );
        // The latest outlook add-in version
        $addInLatestVersion = $this->addInManager->getLatestVersion();
        $result[] = $this->createConfigurationOption(
            $scope,
            'oro_crm_pro_outlook.addin_latest_version',
            'string',
            $addInLatestVersion
        );
        if ($addInLatestVersion) {
            // The URL to the latest outlook add-in installer
            $file = $this->addInManager->getFile($addInLatestVersion);
            $result[] = $this->createConfigurationOption(
                $scope,
                'oro_crm_pro_outlook.addin_latest_version_url',
                'string',
                $this->urlHelper->generateAbsoluteUrl($file['url'])
            );
            // The URL to the release notes for the latest outlook add-in version
            if (!empty($file['doc_url'])) {
                $result[] = $this->createConfigurationOption(
                    $scope,
                    'oro_crm_pro_outlook.addin_latest_version_doc_url',
                    'string',
                    $this->urlHelper->generateAbsoluteUrl($file['doc_url'])
                );
            }
            // Minimal version of the Outlook Add-In that is supported by this server
            $minSupportedVersion = $this->addInManager->getMinSupportedVersion();
            if ($minSupportedVersion) {
                $result[] = $this->createConfigurationOption(
                    $scope,
                    'oro_crm_pro_outlook.addin_min_supported_version',
                    'string',
                    $minSupportedVersion
                );
            }
        }

        return $result;
    }

    /**
     * @param string $scope
     * @param string $key
     * @param string $dataType
     * @param mixed  $value
     *
     * @return ConfigurationOption
     */
    protected function createConfigurationOption($scope, $key, $dataType, $value)
    {
        $result = new ConfigurationOption($scope, $key);
        $result->setDataType($dataType);
        $result->setValue($value);

        return $result;
    }
}
