<?php

namespace OroB2B\Bundle\MoneyOrderBundle\Method;

use OroB2B\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class MoneyOrder implements PaymentMethodInterface
{
    const TYPE = 'money_order';

    /**
     * @var MoneyOrderConfigInterface
     */
    protected $config;

    /**
     * @param MoneyOrderConfigInterface $config
     */
    public function __construct(MoneyOrderConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($action, PaymentTransaction $paymentTransaction)
    {
        $paymentTransaction->setSuccessful(true);

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->config->isEnabled();
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE;
    }

    /**
     * @param array $context
     * @return bool
     */
    public function isApplicable(array $context = [])
    {
        return $this->config->isCountryApplicable($context) && $this->config->isCurrencyApplicable($context);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionName)
    {
        return $actionName === self::PURCHASE;
    }
}
