<?php

namespace OroPro\Bundle\EwsBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

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
            $ewsConnector = $this->get('oro_pro_ews.connector');
            //set config values, so these values will be used by EWS service
            $encryptor = $this->get('oro_security.encoder.mcrypt');
            $config = $this->get('oro_config.user');
            $config->set('oro_pro_ews.server', $server);
            $config->set('oro_pro_ews.version', $version);
            $config->set('oro_pro_ews.login', $login);
            $config->set('oro_pro_ews.password', $encryptor->encryptData($password));
            $config->set('oro_pro_ews.domain_list', $domainList);

            //use simple request to check connection
            $response = $ewsConnector->getPasswordExpirationDate($login);
            if (!empty($response) && isset($response->PasswordExpirationDate)) {
                $result = ['msg' => 'Connection successful!'];
            }
        } catch (\Exception $exception) {
            $result = ['error' => 'Connection failed!'];
        }

        return new JsonResponse($result);
    }
}
