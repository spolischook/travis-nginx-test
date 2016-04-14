<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\LayoutBundle\Annotation\Layout;

use OroB2B\Bundle\AccountBundle\Form\Handler\FrontendAccountUserHandler;

class AccountUserProfileController extends Controller
{
    /**
     * @Route("/", name="orob2b_account_frontend_account_user_profile")
     * @Layout
     * @AclAncestor("orob2b_account_frontend_account_user_view")
     *
     * @return array
     */
    public function profileAction()
    {
        return [
            'data' => [
                'entity' => $this->getUser()
            ]
        ];
    }

    /**
     * Edit account user form
     *
     * @Route("/update", name="orob2b_account_frontend_account_user_profile_update")
     * @Layout()
     * @AclAncestor("orob2b_account_frontend_account_user_update")
     *
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function updateAction(Request $request)
    {
        $accountUser = $this->getUser();
        $form = $this->get('orob2b_account.provider.frontend_account_user_profile_form')->getForm($accountUser);
        $handler = new FrontendAccountUserHandler(
            $form,
            $request,
            $this->get('orob2b_account_user.manager')
        );
        $resultHandler = $this->get('oro_form.model.update_handler')->handleUpdate(
            $accountUser,
            $form,
            ['route' => 'orob2b_account_frontend_account_user_profile_update'],
            ['route' => 'orob2b_account_frontend_account_user_profile'],
            $this->get('translator')->trans('orob2b.account.controller.accountuser.profile_updated.message'),
            $handler
        );

        if ($resultHandler instanceof Response) {
            return $resultHandler;
        }

        return [
            'data' => [
                'entity' => $accountUser
            ]
        ];
    }
}
