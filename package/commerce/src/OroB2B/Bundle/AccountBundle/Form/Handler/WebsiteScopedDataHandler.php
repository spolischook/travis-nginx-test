<?php

namespace OroB2B\Bundle\AccountBundle\Form\Handler;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class WebsiteScopedDataHandler
{
    /**
     * @var FormInterface
     */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Process form
     *
     * @param Product $entity
     * @return bool  True on successful processing, false otherwise
     */
    public function process(Product $entity)
    {
        $this->form->setData($entity);

        if ($this->request->isMethod('POST')) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $this->eventDispatcher->dispatch(
                    'orob2b_product.product.edit',
                    new AfterFormProcessEvent($this->form, $entity)
                );

                return true;
            }
        }

        return false;
    }
}
