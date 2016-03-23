<?php

namespace OroB2B\Bundle\ProductBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Component\Layout\DataProviderInterface;
use Oro\Component\Layout\ContextInterface;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAction;

use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;

class QuickAddFormProvider implements DataProviderInterface
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
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'orob2b_product_quick_add_form_provider';
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        if (!$this->data) {
            $this->data = new FormAccessor(
                $this->getForm(),
                FormAction::createByRoute('orob2b_product_frontend_quick_add')
            );
        }
        return $this->data;
    }

    /**
     * @param array $data
     * @param array $options
     * @return FormInterface
     */
    public function getForm($data = [], array $options = [])
    {
        if (!$this->form) {
            $this->form = $this->formFactory->create(QuickAddType::NAME, $data, $options);
        }
        return $this->form;
    }
}
