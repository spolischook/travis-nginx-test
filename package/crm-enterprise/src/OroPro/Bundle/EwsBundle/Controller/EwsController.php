<?php

namespace OroPro\Bundle\EwsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use OroPro\Bundle\EwsBundle\Ews\EwsType\DistinguishedFolderIdNameType;

/**
 * Class EwsController
 * @package OroPro\Bundle\EwsBundle\Controller
 */
class EwsController extends Controller
{
    /**
     * @Route("/ping", name="oro_pro_ews_ping")
     */
    public function pingAction()
    {
        $server = $this->getRequest()->get('server');
        $version = $this->getRequest()->get('version');
        $login = $this->getRequest()->get('login');
        $password = $this->getRequest()->get('password');
        $domainList = $this->getRequest()->get('domain_list');

        try {
            $ewsConnector = $this->get('oro_pro_ews.transport_check.connector');
            $defaultConfigurator = $this->get('oro_pro_ews.service_configurator');
            $password = ($password === '' || is_null($password)) ? $defaultConfigurator->getPassword() : $password;
            //set config values, so these values will be used by EWS service
            $configurator = $this->get('oro_pro_ews.transport_check.service_configurator');
            $configurator->setServer($server);
            $configurator->setVersion($version);
            $configurator->setLogin($login);
            $configurator->setPassword($password);
            $configurator->setDomains($domainList);

            //use simple request to check connection
            $response = $ewsConnector->findFolders(DistinguishedFolderIdNameType::ROOT);
            if (!empty($response) && isset($response[0]) && !empty($response[0]->RootFolder)) {
                $result = ['msg' => $this->get('translator')->trans('oro_pro_ews.controller.message.ok')];
            } else {
                $result = ['error' => $this->get('translator')->trans('oro_pro_ews.controller.message.error')];
            }
        } catch (\Exception $exception) {
            $result = ['error' => $this->get('translator')->trans('oro_pro_ews.controller.message.error')];
        }

        return new JsonResponse($result);
    }
}
