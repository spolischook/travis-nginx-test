<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Magento;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\AddressBundle\Entity\AddressType;

use OroCRM\Bundle\MagentoBundle\Entity\Cart;
use OroCRM\Bundle\MagentoBundle\Entity\Order;
use OroCRM\Bundle\MagentoBundle\Entity\OrderAddress;
use OroCRM\Bundle\MagentoBundle\Entity\OrderItem;

use OroCRMPro\Bundle\DemoDataBundle\Exception\EntityNotFoundException;
use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadCustomerOrderData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @return array
     */
    public function getData()
    {
        return [
            'orders' => $this->loadData('magento/orders.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data        = $this->getData();
        $addressType = $this->getBillingAddressType();

        foreach ($data['orders'] as $orderData) {
            $cart     = $this->getCartReference($orderData['cart uid']);
            $customer = $cart->getCustomer();
            $order    = new Order();

            $order->setOrganization($cart->getOrganization());
            $order->setChannel($cart->getChannel());
            $order->setCustomer($cart->getCustomer());
            $order->setOwner($cart->getCustomer()->getOwner());
            $order->setStatus($orderData['status']);
            $order->setStore($cart->getStore());
            $order->setStoreName($cart->getStore()->getName());
            $order->setIsGuest(0);
            $order->setIncrementId((string)$orderData['uid']);
            $order->setCreatedAt($this->generateUpdatedDate($cart->getCreatedAt()));
            $order->setUpdatedAt($this->generateUpdatedDate($order->getCreatedAt()));
            $order->setCart($cart);
            $order->setCurrency($cart->getBaseCurrencyCode());
            $order->setDataChannel($cart->getDataChannel());
            $order->setShippingAmount(rand(5, 10));
            $order->setPaymentMethod($orderData['payment_method']);
            $order->setPaymentDetails($orderData['payment_method_details']);
            $order->setShippingMethod('flatrate_flatrate');
            $order->setTotalInvoicedAmount($cart->getGrandTotal());
            $order->setTotalAmount($cart->getGrandTotal());
            $order->setSubtotalAmount($cart->getSubTotal());
            $this->addOrderAddress($order, $cart, $addressType);

            if ($order->isCompleted()) {
                $order->setTotalPaidAmount($cart->getGrandTotal());
            }

            $cartItems  = $cart->getCartItems();
            $orderItems = [];
            foreach ($cartItems as $cartItem) {
                $orderItem = new OrderItem();
                $orderItem->setOriginId($cartItem->getOriginId());
                $orderItem->setOrder($order);
                $orderItem->setTaxAmount($cartItem->getTaxAmount());
                $orderItem->setTaxPercent($cartItem->getTaxPercent());
                $orderItem->setRowTotal($cartItem->getRowTotal());
                $orderItem->setProductType($cartItem->getProductType());
                $orderItem->setIsVirtual((bool)$cartItem->getIsVirtual());
                $orderItem->setQty($cartItem->getQty());
                $orderItem->setSku($cartItem->getSku());
                $orderItem->setPrice($cartItem->getPrice());
                $orderItem->setOriginalPrice($cartItem->getPrice());
                $orderItem->setName($cartItem->getName());
                $orderItem->setOwner($order->getOrganization());
                $orderItems[] = $orderItem;

                $manager->persist($orderItem);
            }
            $order->setItems($orderItems);

            $manager->persist($customer);
            $manager->persist($order);
        }
        $manager->flush();
    }

    /**
     * Add cart address
     *
     * @param Order       $order
     * @param Cart        $cart
     * @param AddressType $addressType
     */
    protected function addOrderAddress(Order $order, Cart $cart, AddressType $addressType)
    {
        if ($cart->getBillingAddress() && $cart->getBillingAddress()->getCountry()) {
            $address = new OrderAddress();

            $cartAddress = $cart->getBillingAddress();
            $address->setOwner($order);
            $address->setOrganization($cart->getOrganization());
            $address->setCity($cartAddress->getCity());
            $address->setStreet($cartAddress->getStreet());
            $address->setCountry($cartAddress->getCountry());
            $address->setRegion($cartAddress->getRegion());
            $address->setPostalCode($cartAddress->getPostalCode());
            $address->setPrimary(true);

            $address->addType($addressType);
            $order->addAddress($address);
        }
    }

    /**
     * @return AddressType
     * @throws EntityNotFoundException
     */
    protected function getBillingAddressType()
    {
        $repository = $this->em->getRepository('OroAddressBundle:AddressType');
        $type       = $repository->findOneBy(['name' => AddressType::TYPE_BILLING]);
        if (!$type) {
            throw new EntityNotFoundException('Address type ' . AddressType::TYPE_BILLING . ' not found!');
        }
        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 32;
    }
}
