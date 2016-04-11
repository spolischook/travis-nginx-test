<?php

namespace OroB2B\Bundle\AccountBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Test\FormInterface;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAction;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\AbstractServerRenderDataProvider;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserPasswordResetType;

class FrontendAccountUserResetPasswordFormProvider extends AbstractServerRenderDataProvider
{
    /**
     * @var FormAccessor
     */
    protected $data;

    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(ContextInterface $context)
    {
        if (!$this->data) {
            $this->data = new FormAccessor(
                $this->getForm(),
                FormAction::createByRoute('orob2b_account_frontend_account_user_password_reset')
            );
        }

        return $this->data;
    }

    /**
     * @param AccountUser $user
     * @return FormInterface
     */
    public function getForm(AccountUser $user = null)
    {
        if (!$this->form) {
            $this->form = $this->formFactory->create(AccountUserPasswordResetType::NAME, $user);
        }

        return $this->form;
    }
}
