<?php

namespace OroB2B\Bundle\PaymentBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

use OroB2B\Bundle\ValidationBundle\Validator\Constraints\Integer;

class CreditCardType extends AbstractType
{
    const NAME = 'orob2b_payment_credit_card';

    /** {@inheritdoc} */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'ACCT',
                'text',
                [
                    'required' => true,
                    'label' => 'orob2b.payment.credit_card.card_number.label',
                    'mapped' => false,
                    'attr' => [
                        'data-validation' => [
                            'creditCardNumberLuhnCheck' => [
                                'message' => 'orob2b.payment.account.error',
                                'payload' => null,
                            ],
                        ],
                        'data-card-number' => true,
                        'autocomplete' => 'off',
                        'data-gateway' => true,
                    ],
                    'constraints' => [
                        new Integer(),
                        new NotBlank(),
                        new Length(['min' => '12', 'max' => '19'])
                    ],
                ]
            )
            ->add(
                'expirationDate',
                'orob2b_payment_credit_card_expiration_date',
                [
                    'required' => true,
                    'label' => 'orob2b.payment.credit_card.expiration_date.label',
                    'mapped' => false,
                    'placeholder' => [
                        'year' => 'Year',
                        'month' => 'Month',
                    ],
                    'attr' => [
                        'data-expiration-date' => true
                    ]
                ]
            )
            ->add(
                'EXPDATE',
                'hidden',
                [
                    'attr' => [
                        'data-gateway' => true,
                    ],
                ]
            )
            ->add(
                'CVV2',
                'password',
                [
                    'required' => true,
                    'label' => 'orob2b.payment.credit_card.cvv2.label',
                    'mapped' => false,
                    'block_name' => 'payment_credit_card_cvv',
                    'constraints' => [
                        new Integer(['message' => 'orob2b.payment.number.error']),
                        new NotBlank(),
                        new Length(['min' => 3, 'max' => 4]),
                    ],
                    'attr' => [
                        'data-card-cvv' => true,
                        'data-gateway' => true,
                    ]
                ]
            );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['label' => 'orob2b.payment.methods.credit_card.label', 'csrf_protection' => false]);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        foreach ($view->children as $child) {
            $child->vars['full_name'] = $child->vars['name'];
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
