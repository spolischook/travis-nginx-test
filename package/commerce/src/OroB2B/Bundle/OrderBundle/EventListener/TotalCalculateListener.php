<?php

namespace OroB2B\Bundle\OrderBundle\EventListener;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\Form;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Form\Type\FrontendOrderType;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderType;
use OroB2B\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use OroB2B\Bundle\FrontendBundle\Helper\ActionApplicationsHelper;

class TotalCalculateListener
{
    protected $forms = [
        ActionApplicationsHelper::FRONTEND => FrontendOrderType::NAME,
        ActionApplicationsHelper::BACKEND => OrderType::NAME
    ];

    /** @var FormFactory */
    protected $formFactory;

    /** @var ActionApplicationsHelper */
    protected $applicationsHelper;

    /**
     * @param FormFactory $formFactory
     * @param ApplicationsHelper $applicationsHelper
     */
    public function __construct(FormFactory $formFactory, ApplicationsHelper $applicationsHelper)
    {
        $this->formFactory = $formFactory;
        $this->applicationsHelper = $applicationsHelper;
    }

    /**
     * @param TotalCalculateBeforeEvent $event
     */
    public function onBeforeTotalCalculate(TotalCalculateBeforeEvent $event)
    {
        /** @var Order $entity */
        $entity = $event->getEntity();
        $request = $event->getRequest();

        if ($entity instanceof Order) {
            $currentApplication = $this->applicationsHelper->getCurrentApplication();
            $entity->resetLineItems();

            if ($currentApplication === ActionApplicationsHelper::BACKEND) {
                $entity->resetDiscounts();
            }

            if ($form = $this->createForm($entity, $currentApplication)) {
                $form->submit($request, false);
            }
        }
    }

    /**
     * @param object $entity
     * @param string $currentApplication - Application Name
     *
     * @return null|Form|FormInterface
     */
    protected function createForm($entity, $currentApplication)
    {
        $form = null;

        if ($this->isDefinedForm($currentApplication)) {
            $form = $this->formFactory->create($this->forms[$currentApplication], $entity);
        }

        return $form;
    }

    /**
     * @param string $currentApplication - Application Name
     *
     * @return bool
     */
    protected function isDefinedForm($currentApplication)
    {
        return array_key_exists($currentApplication, $this->forms) && $this->forms[$currentApplication];
    }
}
