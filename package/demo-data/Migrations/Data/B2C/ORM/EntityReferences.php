<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture as DoctrineAbstractFixture;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TrackingBundle\Entity\TrackingEvent;
use Oro\Bundle\TrackingBundle\Entity\TrackingEventDictionary;
use Oro\Bundle\TrackingBundle\Entity\TrackingVisit;
use Oro\Bundle\TrackingBundle\Entity\TrackingWebsite;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;

use OroCRM\Bundle\AccountBundle\Entity\Account;
use OroCRM\Bundle\CampaignBundle\Entity\Campaign;
use OroCRM\Bundle\CampaignBundle\Entity\EmailCampaign;
use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;
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
use OroCRM\Bundle\SalesBundle\Entity\Lead;
use OroCRM\Bundle\SalesBundle\Entity\Opportunity;

use OroCRMPro\Bundle\DemoDataBundle\Exception\EntityNotFoundException;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class EntityReferences extends DoctrineAbstractFixture
{

    /**
     * @param $reference
     *
     * @return object
     * @throws EntityNotFoundException
     */
    protected function getReferenceByName($reference)
    {
        if ($this->hasReference($reference)) {
            return $this->getReference($reference);
        }
        throw new EntityNotFoundException('Reference ' . $reference . ' not found.');
    }

    /**
     * @param $uid
     *
     * @return Organization
     */
    protected function getOrganizationReference($uid)
    {
        $reference = 'Organization:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param              $uid
     * @param Organization $organization
     */
    protected function setOrganizationReference($uid, Organization $organization)
    {
        $reference = 'Organization:' . $uid;
        $this->addReference($reference, $organization);
    }

    /**
     * @param $uid
     *
     * @return User
     */
    protected function getUserReference($uid)
    {
        $reference = 'User:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     *
     * @return bool
     */
    protected function hasUserReference($uid)
    {
        $reference = 'User:' . $uid;

        return $this->hasReference($reference);
    }

    /**
     * @param      $uid
     * @param User $entity
     */
    protected function setUserReference($uid, User $entity)
    {
        $reference = 'User:' . $uid;
        $this->addReference($reference, $entity);
    }

    /**
     * @param $uid
     *
     * @return Tag
     */
    protected function getTagReference($uid)
    {
        $reference = 'Tag:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param     $uid
     * @param Tag $tag
     */
    protected function setTagReference($uid, Tag $tag)
    {
        $reference = 'Tag:' . $uid;
        $this->setReference($reference, $tag);
    }

    /**
     * @param $uid
     *
     * @return Account
     */
    protected function getAccountReference($uid)
    {
        $reference = 'Account:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param         $uid
     * @param Account $account
     */
    protected function setAccountReference($uid, Account $account)
    {
        $reference = 'Account:' . $uid;
        $this->setReference($reference, $account);
    }

    /**
     * @param $uid
     *
     * @return BusinessUnit
     */
    protected function getBusinessUnitReference($uid)
    {
        $reference = 'BusinessUnit:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param              $uid
     * @param BusinessUnit $businessUnit
     */
    protected function setBusinessUnitReference($uid, BusinessUnit $businessUnit)
    {
        $reference = 'BusinessUnit:' . $uid;
        $this->setReference($reference, $businessUnit);
    }

    /**
     * @param $uid
     *
     * @return Contact
     */
    protected function getContactReference($uid)
    {
        $reference = 'Contact:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param         $uid
     * @param Contact $contact
     *
     * @return object
     */
    protected function setContactReference($uid, Contact $contact)
    {
        $reference = 'Contact:' . $uid;
        $this->setReference($reference, $contact);
    }

    /**
     * @param $uid
     *
     * @return Customer|B2bCustomer
     */
    protected function getCustomerReference($uid)
    {
        $reference = 'Customer:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param                      $uid
     * @param Customer|B2bCustomer $customer
     */
    protected function setCustomerReference($uid, $customer)
    {
        $reference = 'Customer:' . $uid;
        $this->setReference($reference, $customer);
    }

    /**
     * @param $uid
     *
     * @return Cart
     */
    protected function getCartReference($uid)
    {
        $reference = 'Cart:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param      $uid
     * @param Cart $cart
     */
    protected function setCartReference($uid, Cart $cart)
    {
        $reference = 'Cart:' . $uid;
        $this->setReference($reference, $cart);
    }

    /**
     * @param $uid
     *
     * @return Store
     */
    protected function getStoreReference($uid)
    {
        $reference = 'Store:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param       $uid
     * @param Store $store
     */
    protected function setStoreReference($uid, Store $store)
    {
        $reference = 'Store:' . $uid;
        $this->setReference($reference, $store);
    }

    /**
     * @param $uid
     *
     * @return CustomerGroup
     */
    protected function getCustomerGroupReference($uid)
    {
        $reference = 'CustomerGroup:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param               $uid
     * @param CustomerGroup $customerGroup
     */
    protected function setCustomerGroupReference($uid, CustomerGroup $customerGroup)
    {
        $reference = 'CustomerGroup:' . $uid;
        $this->setReference($reference, $customerGroup);
    }

    /**
     * @param $uid
     *
     * @return Integration
     */
    protected function getIntegrationReference($uid)
    {
        $reference = 'Integration:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param             $uid
     * @param Integration $integration
     */
    protected function setIntegrationReference($uid, Integration $integration)
    {
        $reference = 'Integration:' . $uid;
        $this->setReference($reference, $integration);
    }

    /**
     * @param $uid
     *
     * @return Integration
     */
    protected function getMailChimpIntegrationReference($uid)
    {
        $reference = 'MailChimpIntegration:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param             $uid
     * @param Integration $integration
     */
    protected function setMailChimpIntegrationReference($uid, Integration $integration)
    {
        $reference = 'MailChimpIntegration:' . $uid;
        $this->setReference($reference, $integration);
    }

    /**
     * @param $uid
     *
     * @return SubscribersList
     */
    protected function getMailChimpSubscriberListReference($uid)
    {
        $reference = 'MailChimpSubscribersList:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param                 $uid
     * @param SubscribersList $list
     */
    protected function setMailChimpSubscriberListReference($uid, SubscribersList $list)
    {
        $reference = 'MailChimpSubscribersList:' . $uid;
        $this->setReference($reference, $list);
    }

    /**
     * @param                 $uid
     * @param Integration     $integration
     */
    protected function setZendeskIntegrationReference($uid, Integration $integration)
    {
        $reference = 'ZendeskIntegration:' . $uid;
        $this->setReference($reference, $integration);
    }

    /**
     * @param $uid
     *
     * @return Integration
     */
    protected function getZendeskIntegrationReference($uid)
    {
        $reference = 'ZendeskIntegration:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     *
     * @return Channel
     */
    protected function getChannelReference($uid)
    {
        $reference = 'Channel:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param         $uid
     * @param Channel $dataChannel
     */
    protected function setChannelReference($uid, Channel $dataChannel)
    {
        $reference = 'Channel:' . $uid;
        $this->setReference($reference, $dataChannel);
    }

    /**
     * @param $uid
     *
     * @return Website
     */
    protected function getWebsiteReference($uid)
    {
        $reference = 'Website:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param         $uid
     * @param Website $website
     */
    protected function setWebsiteReference($uid, Website $website)
    {
        $reference = 'Website:' . $uid;
        $this->setReference($reference, $website);
    }

    /**
     * @param $uid
     *
     * @return TrackingWebsite
     */
    protected function getTrackingWebsiteReference($uid)
    {
        $reference = 'TrackingWebsite:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param                 $uid
     * @param TrackingWebsite $website
     */
    protected function setTrackingWebsiteReference($uid, TrackingWebsite $website)
    {
        $reference = 'TrackingWebsite:' . $uid;
        $this->setReference($reference, $website);
    }

    /**
     * @param $uid
     *
     * @return TrackingEventDictionary
     */
    protected function getTrackingEventDictionaryReference($uid)
    {
        $reference = 'TrackingEventDictionary:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param                         $uid
     * @param TrackingEventDictionary $dictionary
     */
    protected function setTrackingEventDictionaryReference($uid, TrackingEventDictionary $dictionary)
    {
        $reference = 'TrackingEventDictionary:' . $uid;
        $this->setReference($reference, $dictionary);
    }

    /**
     * @param $uid
     *
     * @return TrackingVisit
     */
    protected function getTrackingVisitReference($uid)
    {
        $reference = 'TrackingVisit:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param               $uid
     * @param TrackingVisit $visit
     */
    protected function setTrackingVisitReference($uid, $visit)
    {
        $reference = 'TrackingVisit:' . $uid;
        $this->setReference($reference, $visit);
    }

    /**
     * @param $uid
     *
     * @return TrackingEvent
     */
    protected function getTrackingEventReference($uid)
    {
        $reference = 'TrackingEvent:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param               $uid
     * @param TrackingEvent $event
     */
    protected function setTrackingEventReference($uid, TrackingEvent $event)
    {
        $reference = 'TrackingEvent:' . $uid;
        $this->setReference($reference, $event);
    }

    /**
     * @param $uid
     *
     * @return Dashboard
     */
    protected function getDashboardReference($uid)
    {
        $reference = 'Dashboard:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param           $uid
     * @param Dashboard $dashboard
     */
    protected function setDashboardReference($uid, Dashboard $dashboard)
    {
        $reference = 'Dashboard:' . $uid;
        $this->setReference($reference, $dashboard);
    }

    /**
     * @param $uid
     *
     * @return Group
     */
    protected function getGroupReference($uid)
    {
        $reference = 'Group:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param       $uid
     * @param Group $group
     */
    protected function setGroupReference($uid, Group $group)
    {
        $reference = 'Group:' . $uid;
        $this->setReference($reference, $group);
    }

    /**
     * @param $uid
     *
     * @return MarketingList
     */
    protected function getMarketingListReference($uid)
    {
        $reference = 'MarketingList:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param               $uid
     */
    protected function setMarketingListReference($uid, MarketingList $list)
    {
        $reference = 'MarketingList:' . $uid;
        $this->setReference($reference, $list);
    }

    /**
     * @param $uid
     *
     * @return EmailCampaign
     */
    protected function getEmailCampaignReference($uid)
    {
        $reference = 'EmailCampaign:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param               $uid
     * @param EmailCampaign $emailCampaign
     */
    protected function setCampaignEmailReference($uid, EmailCampaign $emailCampaign)
    {
        $reference = 'EmailCampaign:' . $uid;
        $this->setReference($reference, $emailCampaign);
    }

    /**
     * @param $uid
     *
     * @return Report
     */
    protected function getReportReference($uid)
    {
        $reference = 'Report:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param         $uid
     * @param Report  $report
     */
    protected function setReportReference($uid, Report $report)
    {
        $reference = 'Report:' . $uid;
        $this->setReference($reference, $report);
    }

    /**
     * @param $uid
     *
     * @return Segment
     */
    protected function getSegmentReference($uid)
    {
        $reference = 'Segment:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param         $uid
     * @param Segment $segment
     */
    protected function setSegmentReference($uid, Segment $segment)
    {
        $reference = 'Segment:' . $uid;
        $this->setReference($reference, $segment);
    }

    /**
     * @param $uid
     *
     * @return CaseEntity
     */
    protected function getCaseReference($uid)
    {
        $reference = 'Case:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param            $uid
     * @param CaseEntity $case
     */
    protected function setCaseReference($uid, CaseEntity $case)
    {
        $reference = 'Case:' . $uid;
        $this->setReference($reference, $case);
    }

    /**
     * @param         $uid
     * @param Address $address
     */
    protected function setAddressReference($uid, Address $address)
    {
        $reference = 'Address:' . $uid;
        $this->setReference($reference, $address);
    }

    /**
     * @param $uid
     *
     * @return Address
     */
    protected function getAddressReference($uid)
    {
        $reference = 'Address:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param                   $uid
     * @param AbstractEnumValue $leadSourceValue
     */
    protected function setLeadSourceReference($uid, AbstractEnumValue $leadSourceValue)
    {
        $reference = 'LeadSource:' . $uid;
        $this->setReference($reference, $leadSourceValue);
    }

    /**
     * @param $uid
     *
     * @return AbstractEnumValue
     */
    protected function getLeadSourceReference($uid)
    {
        $reference = 'LeadSource:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param      $uid
     * @param Lead $lead
     */
    protected function setLeadReference($uid, Lead $lead)
    {
        $reference = 'Lead:' . $uid;
        $this->setReference($reference, $lead);
    }

    /**
     * @param $uid
     *
     * @return Lead
     */
    protected function getLeadReference($uid)
    {
        $reference = 'Lead:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param             $uid
     * @param Opportunity $opportunity
     */
    protected function setOpportunityReference($uid, Opportunity $opportunity)
    {
        $reference = 'Opportunity:' . $uid;
        $this->setReference($reference, $opportunity);
    }

    /**
     * @param $uid
     *
     * @return Opportunity
     */
    protected function getOpportunityReference($uid)
    {
        $reference = 'Opportunity:' . $uid;

        return $this->getReferenceByName($reference);
    }

    /**
     * @param          $uid
     * @param Campaign $campaign
     */
    protected function setCampaignReference($uid, Campaign $campaign)
    {
        $reference = 'Campaign:' . $uid;
        $this->setReference($reference, $campaign);
    }

    /**
     * @param $uid
     *
     * @return Campaign
     */
    protected function getCampaignReference($uid)
    {
        $reference = 'Campaign:' . $uid;

        return $this->getReferenceByName($reference);
    }
}
