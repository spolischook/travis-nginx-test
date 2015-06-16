<?php

namespace Oro\Bundle\LDAPBundle\Controller;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;

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
        /** @var TransportInterface $transport */
        $transport = $this->get('oro_ldap.provider.ldap.transport');

        $entity = $this->getChannelEntity($request, $transport);

        $response = [];

        try {
            $manager = $this->get('oro_ldap.provider.channel_manager');
            $users = $manager->channel($entity)->findUsers();

            $response = [
                'status' => 'success',
                'users' => count($users),
            ];
        } catch (\Exception $e) {
            $response = [
                'status' => 'invalid',
            ];
        }

        return new JsonResponse($response);
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
            $data = $this->get('doctrine.orm.entity_manager')->find('OroIntegrationBundle:Channel', $id);
        }

        $form = $this->get('form.factory')->create('oro_integration_channel_form', $data, ['csrf_protection' => false]);
        $form->submit($request);

        return $form->getData();
    }
} 
