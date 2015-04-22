<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture as DoctrineAbstractFixture;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TrackingBundle\Entity\TrackingEvent;
use Oro\Bundle\TrackingBundle\Entity\TrackingEventDictionary;
use Oro\Bundle\TrackingBundle\Entity\TrackingVisit;
use Oro\Bundle\TrackingBundle\Entity\TrackingWebsite;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;

use OroCRM\Bundle\AccountBundle\Entity\Account;
use OroCRM\Bundle\CampaignBundle\Entity\EmailCampaign;
use OroCRM\Bundle\ChannelBundle\Entity\Channel;
use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\MagentoBundle\Entity\Cart;
use OroCRM\Bundle\MagentoBundle\Entity\Customer;
use OroCRM\Bundle\MagentoBundle\Entity\CustomerGroup;
use OroCRM\Bundle\MagentoBundle\Entity\Store;
use OroCRM\Bundle\MagentoBundle\Entity\Website;
use OroCRM\Bundle\MailChimpBundle\Entity\SubscribersList;
use OroCRM\Bundle\MarketingListBundle\Entity\MarketingList;
use OroCRM\Bundle\SalesBundle\Entity\B2bCustomer;
use OroCRMPro\Bundle\DemoDataBundle\Exception\EntityNotFoundException;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class EntityReferences extends DoctrineAbstractFixture
{

    /**
     * @param $reference
     * @return object
     * @throws EntityNotFoundException
     */
    protected function getReferenceByName($reference)
    {
        if ($this->hasReference($reference)) {
            return $this->getReference($reference);
        }
        echo $reference, "\n";
        throw new EntityNotFoundException('Reference ' . $reference . ' not found.');
    }

    /**
     * @param $uid
     * @return Organization
     * @throws EntityNotFoundException
     */
    protected function getOrganizationReference($uid)
    {
        $reference = 'Organization:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param Organization $organization
     */
    protected function setOrganizationReference($uid, Organization $organization)
    {
        $reference = 'Organization:' . $uid;
        $this->addReference($reference, $organization);
    }

    /**
     * @param $uid
     * @return User
     * @throws EntityNotFoundException
     */
    protected function getUserReference($uid)
    {
        $reference = 'User:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @return bool
     */
    protected function hasUserReference($uid)
    {
        $reference = 'User:' . $uid;
        return $this->hasReference($reference);
    }

    /**
     * @param $uid
     * @param User $entity
     */
    protected function setUserReference($uid, User $entity)
    {
        $reference = 'User:' . $uid;
        $this->addReference($reference, $entity);
    }

    /**
     * @param $uid
     * @return Tag
     * @throws EntityNotFoundException
     */
    protected function getTagReference($uid)
    {
        $reference = 'Tag:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param Tag $tag
     */
    protected function setTagReference($uid, Tag $tag)
    {
        $reference = 'Tag:' . $uid;
        $this->setReference($reference, $tag);
    }

    /**
     * @param $uid
     * @return Account
     * @throws EntityNotFoundException
     */
    protected function getAccountReference($uid)
    {
        $reference = 'Account:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param Account $account
     */
    protected function setAccountReference($uid, Account $account)
    {
        $reference = 'Account:' . $uid;
        $this->setReference($reference, $account);
    }

    /**
     * @param $uid
     * @return BusinessUnit
     * @throws EntityNotFoundException
     */
    protected function getBusinessUnitReference($uid)
    {
        $reference = 'BusinessUnit:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param BusinessUnit $businessUnit
     */
    protected function setBusinessUnitReference($uid, BusinessUnit $businessUnit)
    {
        $reference = 'BusinessUnit:' . $uid;
        $this->setReference($reference, $businessUnit);
    }

    /**
     * @param $uid
     * @return Contact
     * @throws EntityNotFoundException
     */
    protected function getContactReference($uid)
    {
        $reference = 'Contact:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param Contact $contact
     * @return object
     * @throws EntityNotFoundException
     */
    protected function setContactReference($uid, Contact $contact)
    {
        $reference = 'Contact:' . $uid;
        $this->setReference($reference, $contact);
    }

    /**
     * @param $uid
     * @return Customer
     * @throws EntityNotFoundException
     */
    protected function getCustomerReference($uid)
    {
        $reference = 'Customer:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param Customer $customer
     */
    protected function setCustomerReference($uid, Customer $customer)
    {
        $reference = 'Customer:' . $uid;
        $this->setReference($reference, $customer);
    }

    /**
     * @param $uid
     * @return Cart
     * @throws EntityNotFoundException
     */
    protected function getCartReference($uid)
    {
        $reference = 'Cart:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param Cart $cart
     */
    protected function setCartReference($uid, Cart $cart)
    {
        $reference = 'Cart:' . $uid;
        $this->setReference($reference, $cart);
    }

    /**
     * @param $uid
     * @return Store
     * @throws EntityNotFoundException
     */
    protected function getStoreReference($uid)
    {
        $reference = 'Store:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param Store $store
     */
    protected function setStoreReference($uid, Store $store)
    {
        $reference = 'Store:' . $uid;
        $this->setReference($reference, $store);
    }

    /**
     * @param $uid
     * @return CustomerGroup
     * @throws EntityNotFoundException
     */
    protected function getCustomerGroupReference($uid)
    {
        $reference = 'CustomerGroup:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param CustomerGroup $customerGroup
     */
    protected function setCustomerGroupReference($uid, CustomerGroup $customerGroup)
    {
        $reference = 'CustomerGroup:' . $uid;
        $this->setReference($reference, $customerGroup);
    }

    /**
     * @param $uid
     * @return Integration
     * @throws EntityNotFoundException
     */
    protected function getIntegrationReference($uid)
    {
        $reference = 'Integration:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param Integration $integration
     */
    protected function setIntegrationReference($uid, Integration $integration)
    {
        $reference = 'Integration:' . $uid;
        $this->setReference($reference, $integration);
    }

    /**
     * @param $uid
     * @return Integration
     * @throws EntityNotFoundException
     */
    protected function getMailChimpIntegrationReference($uid)
    {
        $reference = 'MailChimpIntegration:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param Integration $integration
     */
    protected function setMailChimpIntegrationReference($uid, Integration $integration)
    {
        $reference = 'MailChimpIntegration:' . $uid;
        $this->setReference($reference, $integration);
    }

    /**
     * @param $uid
     * @return SubscribersList
     * @throws EntityNotFoundException
     */
    protected function getMailChimpSubscriberListReference($uid)
    {
        $reference = 'MailChimpSubscribersList:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param SubscribersList $list
     */
    protected function setMailChimpSubscriberListReference($uid, SubscribersList $list)
    {
        $reference = 'MailChimpSubscribersList:' . $uid;
        $this->setReference($reference, $list);
    }

    /**
     * @param $uid
     * @return Channel
     * @throws EntityNotFoundException
     */
    protected function getIntegrationDataChannelReference($uid)
    {
        $reference = 'IntegrationDataChannel:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param Channel $dataChannel
     */
    protected function setIntegrationDataChannelReference($uid, Channel $dataChannel)
    {
        $reference = 'IntegrationDataChannel:' . $uid;
        $this->setReference($reference, $dataChannel);
    }

    /**
     * @param $uid
     * @return Website
     * @throws EntityNotFoundException
     */
    protected function getWebsiteReference($uid)
    {
        $reference = 'Website:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param Website $website
     */
    protected function setWebsiteReference($uid, Website $website)
    {
        $reference = 'Website:' . $uid;
        $this->setReference($reference, $website);
    }

    /**
     * @param $uid
     * @return TrackingWebsite
     * @throws EntityNotFoundException
     */
    protected function getTrackingWebsiteReference($uid)
    {
        $reference = 'TrackingWebsite:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param TrackingWebsite $website
     */
    protected function setTrackingWebsiteReference($uid, TrackingWebsite $website)
    {
        $reference = 'TrackingWebsite:' . $uid;
        $this->setReference($reference, $website);
    }

    /**
     * @param $uid
     * @return TrackingEventDictionary
     * @throws EntityNotFoundException
     */
    protected function getTrackingEventDictionaryReference($uid)
    {
        $reference = 'TrackingEventDictionary:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param TrackingEventDictionary $dictionary
     */
    protected function setTrackingEventDictionaryReference($uid, TrackingEventDictionary $dictionary)
    {
        $reference = 'TrackingEventDictionary:' . $uid;
        $this->setReference($reference, $dictionary);
    }

    /**
     * @param $uid
     * @return TrackingVisit
     * @throws EntityNotFoundException
     */
    protected function getTrackingVisitReference($uid)
    {
        $reference = 'TrackingVisit:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param TrackingVisit $visit
     */
    protected function setTrackingVisitReference($uid, $visit)
    {
        $reference = 'TrackingVisit:' . $uid;
        $this->setReference($reference, $visit);
    }

    /**
     * @param $uid
     * @return TrackingEvent
     * @throws EntityNotFoundException
     */
    protected function getTrackingEventReference($uid)
    {
        $reference = 'TrackingEvent:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param TrackingEvent $event
     */
    protected function setTrackingEventReference($uid, TrackingEvent $event)
    {
        $reference = 'TrackingEvent:' . $uid;
        $this->setReference($reference, $event);
    }

    /**
     * @param $uid
     * @return Dashboard
     * @throws EntityNotFoundException
     */
    protected function getDashboardReference($uid)
    {
        $reference = 'Dashboard:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param Dashboard $dashboard
     */
    protected function setDashboardReference($uid, Dashboard $dashboard)
    {
        $reference = 'Dashboard:' . $uid;
        $this->setReference($reference, $dashboard);
    }

    /**
     * @param $uid
     * @return Group
     * @throws EntityNotFoundException
     */
    protected function getGroupReference($uid)
    {
        $reference = 'Group:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param Group $group
     */
    protected function setGroupReference($uid, Group $group)
    {
        $reference = 'Group:' . $uid;
        $this->setReference($reference, $group);
    }

    /**
     * @param $uid
     * @return MarketingList
     * @throws EntityNotFoundException
     */
    protected function getMarketingListReference($uid)
    {
        $reference = 'MarketingList:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param MarketingList $list
     */
    protected function setMarketingListReference($uid, MarketingList $list)
    {
        $reference = 'MarketingList:' . $uid;
        $this->setReference($reference, $list);
    }

    /**
     * @param $uid
     * @return EmailCampaign
     * @throws EntityNotFoundException
     */
    protected function getEmailCampaignReference($uid)
    {
        $reference = 'EmailCampaign:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param EmailCampaign $emailCampaign
     */
    protected function setCampaignEmailReference($uid, EmailCampaign $emailCampaign)
    {
        $reference = 'EmailCampaign:' . $uid;
        $this->setReference($reference, $emailCampaign);
    }

    /**
     * @param $uid
     * @return Segment
     * @throws EntityNotFoundException
     */
    protected function getSegmentReference($uid)
    {
        $reference = 'Segment:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param Segment $segment
     */
    protected function setSegmentReference($uid, Segment $segment)
    {
        $reference = 'Segment:' . $uid;
        $this->setReference($reference, $segment);
    }

    /**
     * @param $uid
     * @param Address $address
     */
    protected function setAddressReference($uid, Address $address)
    {
        $reference = 'Address:' . $uid;
        $this->setReference($reference, $address);
    }

    /**
     * @param $uid
     * @return Address
     * @throws EntityNotFoundException
     */
    protected function getAddressReference($uid)
    {
        $reference = 'Address:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param AbstractEnumValue $leadSourceValue
     */
    protected function setLeadSourceReference($uid, AbstractEnumValue $leadSourceValue)
    {
        $reference = 'LeadSource:' . $uid;
        $this->setReference($reference, $leadSourceValue);
    }

    /**
     * @param $uid
     * @return AbstractEnumValue
     * @throws EntityNotFoundException
     */
    protected function getLeadSourceReference($uid)
    {
        $reference = 'LeadSource:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @param B2BCustomer $customer
     */
    protected function setB2bCustomerReference($uid, B2bCustomer $customer)
    {
        $reference = 'B2bCustomer:' . $uid;
        $this->setReference($reference, $customer);
    }

    /**
     * @param $uid
     * @return B2bCustomer
     * @throws EntityNotFoundException
     */
    protected function getB2bCustomerReference($uid)
    {
        $reference = 'B2bCustomer:' . $uid;
        return $this->getReferenceByName($reference);
    }
}
