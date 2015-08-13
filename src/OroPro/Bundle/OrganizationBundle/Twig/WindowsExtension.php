<?php

namespace OroPro\Bundle\OrganizationBundle\Twig;

use Symfony\Bridge\Twig\Extension\HttpKernelExtension;
use Symfony\Component\Routing\Router;

use Oro\Bundle\WindowsBundle\Entity\WindowsState;
use Oro\Bundle\WindowsBundle\Twig\WindowsExtension as BaseWindowsExtension;

use OroPro\Bundle\OrganizationBundle\Exception\OrganizationAwareException;

/**
 * Override for render two step dialog form
 *
 * Class WindowsExtension
 * @package OroPro\Bundle\OrganizationBundle\Twig
 */
class WindowsExtension extends BaseWindowsExtension
{
    /** @var Router */
    protected $router;

    /**
     * @param Router $router
     */
    public function setRouter(Router $router)
    {
        $this->router = $router;
    }

    /**
     * { @inheritdoc }
     */
    public function renderFragment(\Twig_Environment $environment, WindowsState $windowState)
    {
        $result = '';
        try {
            $result = parent::renderFragment($environment, $windowState);
        } catch (OrganizationAwareException $e) {
            $windowState->setRenderedSuccessfully(false);
            $data = $windowState->getData();
            if (isset($data['cleanUrl'])) {
                if (isset($data['type'])) {
                    $wid = isset($data['wid']) ? $data['wid'] : $this->getUniqueIdentifier();
                    $parameters['form_url'] = $this->getUrlWithContainer($data['cleanUrl'], $data['type'], $wid);
                } else {
                    $parameters['form_url'] = $data['cleanUrl'];
                }
                $parameters['_widgetContainer'] = 'dialog';
                $uri = $this->router->generate(
                    'oropro_organization_selector_form',
                    $parameters
                );
                /** @var HttpKernelExtension $httpKernelExtension */
                $httpKernelExtension = $environment->getExtension('http_kernel');
                $result = $httpKernelExtension->renderFragment($uri);
                $windowState->setRenderedSuccessfully(true);
            }
        }

        return $result;
    }
}
