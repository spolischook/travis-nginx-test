<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Magento;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use OroCRM\Bundle\MagentoBundle\Entity\Cart;
use OroCRM\Bundle\MagentoBundle\Entity\CartStatus;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadCustomerCartData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\\LoadCustomerData',
        ];
    }

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
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        /** @var CartStatus $status */
        $status = $manager->getRepository('OroCRMMagentoBundle:CartStatus')->findOneBy(['name' => 'open']);

        foreach ($data['carts'] as $cartData) {
            $customer = $this->getCustomerReference($cartData['customer uid']);
            $cart = new Cart();

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

            $this->setCartReference($cartData['uid'], $cart);
            $manager->persist($cart);
        }
        $manager->flush();
    }
}
