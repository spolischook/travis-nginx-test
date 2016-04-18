<?php

namespace OroCRMPro\Bundle\LDAPBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class LdapIntegrationChannelController extends Controller
{

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @Route("/check", name="orocrmpro_ldap_transport_check")
     * @AclAncestor("oro_integration_update")
     */
    public function checkAction(Request $request)
    {
        $entity = $this->getChannelEntity($request);
        $transport = $this->get('orocrmpro_ldap.provider.transport.ldap');
        $transport->init($entity->getTransport());

        return new JsonResponse(
            ['status' => $transport->check() ? 'success' : 'invalid']
        );
    }

    /**
     * @param Request $request
     *
     * @return Channel
     */
    protected function getChannelEntity(Request $request)
    {
        $data = null;
        $id = $request->get('id', false);
        if ($id) {
            $data = $this->get('doctrine')->getRepository('OroIntegrationBundle:Channel')->find($id);
        }

        $form = $this->createForm('oro_integration_channel_form', $data, ['csrf_protection' => false]);
        $form->submit($request);

        return $form->getData();
    }
}
