<?php

namespace OroCRMPro\Bundle\OutlookBundle\Manager;

use Symfony\Bridge\Twig\Extension\HttpFoundationExtension;

use Oro\Bundle\ConfigBundle\Config\ConfigApiManager as BaseConfigApiManager;
use Oro\Bundle\PlatformBundle\Composer\VersionHelper;
use OroCRM\Bundle\CRMBundle\OroCRMBundle;

class ConfigApiManager
{
    /** @var BaseConfigApiManager */
    protected $configManager;

    /** @var VersionHelper */
    protected $versionHelper;

    /** @var AddInManager */
    protected $addInManager;

    /** @var HttpFoundationExtension */
    protected $urlHelper;

    /**
     * @param BaseConfigApiManager    $configManager
     * @param VersionHelper           $versionHelper
     * @param AddInManager            $addInManager
     * @param HttpFoundationExtension $urlHelper
     */
    public function __construct(
        BaseConfigApiManager $configManager,
        VersionHelper $versionHelper,
        AddInManager $addInManager,
        HttpFoundationExtension $urlHelper
    ) {
        $this->configManager = $configManager;
        $this->versionHelper = $versionHelper;
        $this->addInManager = $addInManager;
        $this->urlHelper = $urlHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getData($path, $scope = 'user')
    {
        $result = $this->configManager->getData($path, $scope);
        if ('outlook' === $path) {
            // OroCRM version
            $result[] = [
                'key'   => 'oro_crm_pro_outlook.orocrm_version',
                'type'  => 'string',
                'value' => $this->versionHelper->getVersion(OroCRMBundle::PACKAGE_NAME)
            ];
            // The latest outlook add-in version
            $addInLatestVersion = $this->addInManager->getLatestVersion();
            $result[] = [
                'key'   => 'oro_crm_pro_outlook.addin_latest_version',
                'type'  => 'string',
                'value' => $addInLatestVersion
            ];
            if ($addInLatestVersion) {
                // The URL to the latest outlook add-in installer
                $file = $this->addInManager->getFile($addInLatestVersion);
                $result[] = [
                    'key'   => 'oro_crm_pro_outlook.addin_latest_version_url',
                    'type'  => 'string',
                    'value' => $this->urlHelper->generateAbsoluteUrl($file['url'])
                ];
                // The URL to the release notes for the latest outlook add-in version
                if (!empty($file['doc_url'])) {
                    $result[] = [
                        'key'   => 'oro_crm_pro_outlook.addin_latest_version_doc_url',
                        'type'  => 'string',
                        'value' => $this->urlHelper->generateAbsoluteUrl($file['doc_url'])
                    ];
                }
                // Minimal version of the Outlook Add-In that is supported by this server
                $minSupportedVersion = $this->addInManager->getMinSupportedVersion();
                if ($minSupportedVersion) {
                    $result[] = [
                        'key'   => 'oro_crm_pro_outlook.addin_min_supported_version',
                        'type'  => 'string',
                        'value' => $minSupportedVersion
                    ];
                }
            }
        }

        return $result;
    }
}
