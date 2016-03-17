<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Magento;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

use OroCRM\Bundle\MagentoBundle\Entity\Cart;
use OroCRM\Bundle\MagentoBundle\Entity\CartAddress;
use OroCRM\Bundle\MagentoBundle\Entity\CartStatus;
use OroCRM\Bundle\MagentoBundle\Entity\Customer;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadCustomerCartData extends AbstractFixture implements OrderedFixtureInterface
{

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'carts' => $this->loadData('magento/carts.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['carts'] as $cartData) {
            /** @var CartStatus $status */
            $status = $manager->getRepository('OroCRMMagentoBundle:CartStatus')
                ->findOneBy(['name' => $cartData['status']]);

            $customer = $this->getCustomerReference($cartData['customer uid']);
            $cart     = new Cart();

            $cart->setOrganization($customer->getOrganization());
            $cart->setChannel($customer->getChannel());
            $cart->setCustomer($customer);
            $cart->setOwner($customer->getOwner());
            $cart->setStatus($status);
            $cart->setStore($customer->getStore());
            $cart->setBaseCurrencyCode($cartData['currency']);
            $cart->setStoreCurrencyCode($cartData['currency']);
            $cart->setQuoteCurrencyCode($cartData['currency']);
            $cart->setStoreToBaseRate($cartData['rate']);
            $cart->setStoreToQuoteRate($cartData['rate']);
            $cart->setItemsQty(0);
            $cart->setItemsCount(0);
            $cart->setIsGuest(0);
            $cart->setOriginId($cartData['uid']);
            $cart->setEmail($customer->getEmail());
            $cart->setCreatedAt($this->generateUpdatedDate($customer->getCreatedAt()));
            $cart->setUpdatedAt($this->generateUpdatedDate($cart->getCreatedAt()));
            $cart->setDataChannel($customer->getDataChannel());

            if ($cartData['status'] == 'purchased') {
                $this->addCartAddress($cart, $customer);
            }

            $this->setCartReference($cartData['uid'], $cart);
            $manager->persist($cart);
        }
        $manager->flush();
    }

    /**
     * Add cart address
     *
     * @param Cart     $cart
     * @param Customer $customer
     */
    protected function addCartAddress(Cart $cart, Customer $customer)
    {
        if ($customer->getAddresses()->count()) {
            /** @var AbstractAddress $customerAddress */
            $customerAddress = $customer->getAddresses()->first();
            $address         = new CartAddress();
            $address->setCity($customerAddress->getCity());
            $address->setStreet($customerAddress->getStreet());
            $address->setCountry($customerAddress->getCountry());
            $address->setRegion($customerAddress->getRegion());
            $address->setPostalCode($customerAddress->getPostalCode());
            $cart->setBillingAddress($address);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 30;
    }
}
