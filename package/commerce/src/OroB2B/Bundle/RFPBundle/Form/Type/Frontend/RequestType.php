<?php

namespace OroB2B\Bundle\RFPBundle\Form\Type\Frontend;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;

use OroB2B\Bundle\AccountBundle\Form\Type\Frontend\AccountUserMultiSelectType;

use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

class RequestType extends AbstractType
{
    const NAME = 'orob2b_rfp_frontend_request';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var string
     */
    protected $requestStatusClass;

    /**
     * @param ConfigManager $configManager
     * @param ManagerRegistry $registry
     */
    public function __construct(ConfigManager $configManager, ManagerRegistry $registry)
    {
        $this->configManager = $configManager;
        $this->registry = $registry;
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @param string $requestStatusClass
     */
    public function setRequestStatusClass($requestStatusClass)
    {
        $this->requestStatusClass = $requestStatusClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', 'text', [
                'label' => 'orob2b.rfp.request.first_name.label'
            ])
            ->add('lastName', 'text', [
                'label' => 'orob2b.rfp.request.last_name.label'
            ])
            ->add('email', 'text', [
                'label' => 'orob2b.rfp.request.email.label'
            ])
            ->add('phone', 'text', [
                'required' => false,
                'label' => 'orob2b.rfp.request.phone.label'
            ])
            ->add('company', 'text', [
                'label' => 'orob2b.rfp.request.company.label'
            ])
            ->add('role', 'text', [
                'required' => false,
                'label' => 'orob2b.rfp.request.role.label'
            ])
            ->add('note', 'textarea', [
                'required' => false,
                'label' => 'orob2b.rfp.request.note.label'
            ])
            ->add('poNumber', 'text', [
                'required' => false,
                'label' => 'orob2b.rfp.request.po_number.label'
            ])
            ->add('shipUntil', OroDateType::NAME, [
                'required' => false,
                'label' => 'orob2b.rfp.request.ship_until.label'
            ])
            ->add('requestProducts', RequestProductCollectionType::NAME, [
                'options' => [
                    'compact_units' => true,
                ],
            ])
            ->add('assignedAccountUsers', AccountUserMultiSelectType::NAME, [
                'label' => 'orob2b.frontend.rfp.request.assigned_account_users.label',
            ])
        ;

        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        /** @var Request $request */
        $request = $event->getData();
        $defaultStatus = $this->getDefaultRequestStatus();
        if ($defaultStatus && !$request->getStatus()) {
            $request->setStatus($defaultStatus);
        }
    }

    /**
     * @return RequestStatus
     */
    protected function getDefaultRequestStatus()
    {
        return $this->registry
            ->getManagerForClass($this->requestStatusClass)
            ->getRepository($this->requestStatusClass)
            ->findOneBy([
                'name' => $this->configManager->get('oro_b2b_rfp.default_request_status')
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
