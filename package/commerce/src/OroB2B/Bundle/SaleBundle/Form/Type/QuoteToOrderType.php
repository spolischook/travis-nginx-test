<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Form\EventListener\QuoteToOrderResizeFormSubscriber;

/**
 * Class extended from collection to override default form listener
 */
class QuoteToOrderType extends CollectionType
{
    const NAME = 'orob2b_sale_quote_to_order';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // custom subscriber to pass data to child form types
        $resizeSubscriber = new QuoteToOrderResizeFormSubscriber(
            $options['type'],
            $options['options'],
            $options['allow_add'],
            $options['allow_delete'],
            $options['delete_empty']
        );
        $builder->addEventSubscriber($resizeSubscriber);

        // must be run before ResizeFormListener
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData'], 10);
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        if (!$data instanceof Quote) {
            throw new UnexpectedTypeException($data, 'Quote');
        }
        $event->setData($data->getQuoteProducts()->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setRequired(['data']);
        $resolver->setDefaults(['data_class' => null, 'type' => QuoteProductToOrderType::NAME]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
