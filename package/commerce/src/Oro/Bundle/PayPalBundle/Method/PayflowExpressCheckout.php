<?php

namespace Oro\Bundle\PayPalBundle\Method;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PayPalBundle\Method\Config\PayflowExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\ExpressCheckout\Option as ECOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\ResponseInterface;

use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class PayflowExpressCheckout implements PaymentMethodInterface
{
    const TYPE = 'payflow_express_checkout';

    const COMPLETE = 'complete';

    const PILOT_REDIRECT_URL = 'https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&useraction=commit&token=%s';
    const PRODUCTION_REDIRECT_URL = 'https://www.paypal.com/webscr?cmd=_express-checkout&useraction=commit&token=%s';

    /** @var Gateway */
    protected $gateway;

    /** @var RouterInterface */
    protected $router;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /** @var PayflowExpressCheckoutConfigInterface */
    protected $config;

    /**
     * @param Gateway $gateway
     * @param PayflowExpressCheckoutConfigInterface $config
     * @param RouterInterface $router
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        Gateway $gateway,
        PayflowExpressCheckoutConfigInterface $config,
        RouterInterface $router,
        DoctrineHelper $doctrineHelper
    ) {
        $this->gateway = $gateway;
        $this->config = $config;
        $this->router = $router;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($action, PaymentTransaction $paymentTransaction)
    {
        if (!$this->supports($action)) {
            throw new \InvalidArgumentException(sprintf('Unsupported action "%s"', $action));
        }

        $this->gateway->setTestMode($this->config->isTestMode());

        return $this->{$action}($paymentTransaction) ?: [];
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return static::TYPE;
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
    public function isApplicable(array $context = [])
    {
        return true;
    }

    /**
     * @param string $actionName
     * @return bool
     */
    public function supports($actionName)
    {
        return in_array($actionName, [self::PURCHASE, self::AUTHORIZE, self::CHARGE, self::CAPTURE, self::COMPLETE]);
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function purchase(PaymentTransaction $paymentTransaction)
    {
        $paymentTransaction->setRequest(array_merge(
            $this->getCredentials(),
            $this->getSetExpressCheckoutOptions($paymentTransaction),
            $this->getShippingAddressOptions($paymentTransaction)
        ));

        $paymentTransaction->setAction($this->config->getPurchaseAction());

        $this->execute($paymentTransaction->getAction(), $paymentTransaction);

        if (!$paymentTransaction->isActive()) {
            return [];
        }

        return [
            'purchaseRedirectUrl' => $this->getRedirectUrl($paymentTransaction->getReference()),
        ];
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     */
    protected function complete(PaymentTransaction $paymentTransaction)
    {
        $paymentTransaction->setRequest(array_merge(
            $this->getCredentials(),
            $this->getDoExpressCheckoutOptions($paymentTransaction)
        ));

        $response = $this->actionRequest($paymentTransaction, ECOption\Action::DO_EC);

        $data = $response->getData();
        $paymentTransaction
            ->setSuccessful($response->isSuccessful())
            ->setActive(false)
            ->setResponse($data)
            ->setReference($response->getReference());

        // Payment with non-complete pending reason should be marked as pending
        if ($paymentTransaction->isSuccessful()
            && isset($data['PENDINGREASON'])
            && !in_array($data['PENDINGREASON'], ['none', 'completed', 'authorization'])
        ) {
            $paymentTransaction
                ->setActive(true)
                ->setSuccessful(false);
        }

        if ($paymentTransaction->getAction() === self::AUTHORIZE && $paymentTransaction->isSuccessful()) {
            $paymentTransaction->setActive(true);
        }
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     */
    protected function authorize(PaymentTransaction $paymentTransaction)
    {
        $paymentTransaction->setRequest(array_merge(
            $paymentTransaction->getRequest(),
            [Option\Transaction::TRXTYPE => Option\Transaction::AUTHORIZATION]
        ));
        $this->setExpressCheckoutRequest($paymentTransaction);
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     */
    protected function charge(PaymentTransaction $paymentTransaction)
    {
        $paymentTransaction->setRequest(array_merge(
            $paymentTransaction->getRequest(),
            [Option\Transaction::TRXTYPE => Option\Transaction::SALE]
        ));
        $this->setExpressCheckoutRequest($paymentTransaction);
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function capture(PaymentTransaction $paymentTransaction)
    {
        $sourcePaymentTransaction = $paymentTransaction->getSourcePaymentTransaction();
        if (!$sourcePaymentTransaction) {
            $paymentTransaction
                ->setSuccessful(false)
                ->setActive(false);

            return ['successful' => false];
        }

        $options = array_merge(
            $this->getCredentials(),
            $this->getDelayedCaptureOptions($paymentTransaction)
        );

        $response = $this->gateway->request(Option\Transaction::DELAYED_CAPTURE, $options);

        $paymentTransaction
            ->setRequest($options)
            ->setSuccessful($response->isSuccessful())
            ->setActive(false)
            ->setReference($response->getReference())
            ->setResponse($response->getData());

        $sourcePaymentTransaction->setActive(!$paymentTransaction->isSuccessful());

        return [
            'message' => $response->getMessage(),
            'successful' => $response->isSuccessful(),
        ];
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return null
     */
    protected function setExpressCheckoutRequest(PaymentTransaction $paymentTransaction)
    {
        $response = $this->actionRequest($paymentTransaction, ECOption\Action::SET_EC);

        $paymentTransaction
            ->setSuccessful(false)
            ->setActive($response->isSuccessful())
            ->setResponse($response->getData());

        $data = $response->getData();

        if (!isset($data[ECOption\Token::TOKEN])) {
            $paymentTransaction->setAction(false);

            return;
        }

        $paymentTransaction->setReference($data[ECOption\Token::TOKEN]);
    }

    /**
     * @param string $token
     * @return string
     */
    protected function getRedirectUrl($token)
    {
        $redirectUrl = $this->config->isTestMode() ? self::PILOT_REDIRECT_URL : self::PRODUCTION_REDIRECT_URL;

        return sprintf($redirectUrl, $token);
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function getSetExpressCheckoutOptions(PaymentTransaction $paymentTransaction)
    {
        return array_merge(
            $this->getLineItemOptions($paymentTransaction),
            [
                ECOption\PaymentType::PAYMENTTYPE => ECOption\PaymentType::INSTANTONLY,
                ECOption\ShippingAddressOverride::ADDROVERRIDE => ECOption\ShippingAddressOverride::TRUE,
                Option\Amount::AMT => $paymentTransaction->getAmount(),
                Option\Currency::CURRENCY => $paymentTransaction->getCurrency(),
                Option\ReturnUrl::RETURNURL => $this->router->generate(
                    'orob2b_payment_callback_return',
                    [
                        'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                Option\CancelUrl::CANCELURL => $this->router->generate(
                    'orob2b_payment_callback_error',
                    [
                        'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
            ]
        );
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function getDoExpressCheckoutOptions(PaymentTransaction $paymentTransaction)
    {
        $options = [
            Option\Amount::AMT => $paymentTransaction->getAmount(),
            Option\Transaction::TRXTYPE => $this->getTransactionType($paymentTransaction),
            ECOption\Token::TOKEN => $paymentTransaction->getReference(),
        ];

        $response = $paymentTransaction->getResponse();

        if (isset($response['PayerID'])) {
            $options[ECOption\Payer::PAYERID] = $response['PayerID'];
        }

        return $options;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function getDelayedCaptureOptions(PaymentTransaction $paymentTransaction)
    {
        $sourceTransaction = $paymentTransaction->getSourcePaymentTransaction();

        return [
            Option\Amount::AMT => $paymentTransaction->getAmount(),
            Option\OriginalTransaction::ORIGID => $sourceTransaction->getReference(),
        ];
    }

    /**
     * @param $paymentTransaction
     * @return array
     */
    protected function getShippingAddressOptions(PaymentTransaction $paymentTransaction)
    {
        $entity = $this->doctrineHelper->getEntityReference(
            $paymentTransaction->getEntityClass(),
            $paymentTransaction->getEntityIdentifier()
        );

        if (!$entity) {
            return [];
        }

        $propertyAccessor = $this->getPropertyAccessor();

        try {
            /** @var OrderAddress $shippingAddress */
            $shippingAddress = $propertyAccessor->getValue($entity, 'shippingAddress');
        } catch (NoSuchPropertyException $e) {
            return [];
        }

        return [
            Option\ShippingAddress::SHIPTOFIRSTNAME => (string)$shippingAddress->getFirstName(),
            Option\ShippingAddress::SHIPTOLASTNAME => (string)$shippingAddress->getLastName(),
            Option\ShippingAddress::SHIPTOSTREET => (string)$shippingAddress->getStreet(),
            Option\ShippingAddress::SHIPTOSTREET2 => (string)$shippingAddress->getStreet2(),
            Option\ShippingAddress::SHIPTOCITY => (string)$shippingAddress->getCity(),
            Option\ShippingAddress::SHIPTOSTATE => (string)$shippingAddress->getRegionCode(),
            Option\ShippingAddress::SHIPTOZIP => (string)$shippingAddress->getPostalCode(),
            Option\ShippingAddress::SHIPTOCOUNTRY => (string)$shippingAddress->getCountryIso2(),
        ];
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    protected function getLineItemOptions(PaymentTransaction $paymentTransaction)
    {
        $entity = $this->doctrineHelper->getEntityReference(
            $paymentTransaction->getEntityClass(),
            $paymentTransaction->getEntityIdentifier()
        );

        if (!$entity) {
            return [];
        }

        if (!$entity instanceof LineItemsAwareInterface) {
            return [];
        }

        $lineItems = $entity->getLineItems();

        $options = [];
        foreach ($lineItems as $lineItem) {
            if (!$lineItem instanceof OrderLineItem) {
                continue;
            }

            $product = $lineItem->getProduct();

            if (!$product) {
                continue;
            }

            $options[] = [
                Option\LineItems::NAME => $this->truncateString((string)$product->getDefaultName(), 36),
                Option\LineItems::DESC => $this->truncateString((string)$product->getDefaultShortDescription(), 35),
                Option\LineItems::COST => $lineItem->getValue(),
                Option\LineItems::QTY => (int)$lineItem->getQuantity(),
            ];
        }

        return Option\LineItems::prepareOptions($options);
    }

    /**
     * @param string $string
     * @param int $length
     * @return string
     */
    protected function truncateString($string, $length)
    {
        return substr($string, 0, $length);
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @param string $requestAction
     * @return ResponseInterface
     */
    protected function actionRequest(PaymentTransaction $paymentTransaction, $requestAction)
    {
        $options = $paymentTransaction->getRequest();
        $transactionType = $this->getTransactionType($paymentTransaction);

        $options[ECOption\Action::ACTION] = $requestAction;
        unset($options[Option\Transaction::TRXTYPE]);

        return $this->gateway->request($transactionType, $options);
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return null|string
     */
    protected function getTransactionType(PaymentTransaction $paymentTransaction)
    {
        $request = $paymentTransaction->getRequest();

        if (isset($request[Option\Transaction::TRXTYPE])) {
            return $request[Option\Transaction::TRXTYPE];
        }

        return null;
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * @return array
     */
    protected function getCredentials()
    {
        return array_merge(
            $this->config->getCredentials(),
            [
                Option\Tender::TENDER => Option\Tender::PAYPAL,
            ]
        );
    }
}
