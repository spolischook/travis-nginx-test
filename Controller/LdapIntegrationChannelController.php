<?php

namespace Oro\Bundle\LDAPBundle\Controller;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LDAPBundle\Provider\Transport\LdapTransportInterface;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LdapIntegrationChannelController extends Controller
{

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @Route("/check", name="oro_ldap_transport_check")
     * @AclAncestor("oro_integration_update")
     */
    public function checkAction(Request $request)
    {
        /** @var LdapTransportInterface $transport */
        $transport = $this->get('oro_ldap.provider.transport.ldap');

        $entity = $this->getChannelEntity($request, $transport);

        $transport->init($entity->getTransport());

        return new JsonResponse([
            'status' => $transport->check() ? 'success' : 'invalid',
        ]);
    }

    /**
     * @param Request $request
     * @return Channel
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
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
