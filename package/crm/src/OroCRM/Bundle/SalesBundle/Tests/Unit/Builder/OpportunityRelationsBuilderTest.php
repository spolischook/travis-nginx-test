<?php

namespace OroCRM\Bundle\SalesBundle\Tests\Unit\Builder;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroCRM\Bundle\AccountBundle\Entity\Account;
use OroCRM\Bundle\ChannelBundle\Entity\Channel;
use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\SalesBundle\Builder\OpportunityRelationsBuilder;
use OroCRM\Bundle\SalesBundle\Entity\B2bCustomer;
use OroCRM\Bundle\SalesBundle\Entity\Opportunity;

class OpportunityRelationsBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldSetCustomerDataChannel()
    {
        $channel = new Channel();
        $customer = new B2bCustomer();
        $opportunity = new Opportunity();
        $opportunity->setCustomer($customer);
        $opportunity->setDataChannel($channel);

        $builder = new OpportunityRelationsBuilder($opportunity);
        $builder->buildCustomer();

        $this->assertSame($channel, $customer->getDataChannel());
    }

    public function testShouldCreateCustomerAccount()
    {
        $customer = new B2bCustomer();
        $customer->setName('John Doe');
        $opportunity = new Opportunity();
        $opportunity->setCustomer($customer);

        $builder = new OpportunityRelationsBuilder($opportunity);
        $builder->buildCustomer();

        $this->assertNotNull($customer->getAccount());
        $this->assertEquals('John Doe', $customer->getAccount()->getName());
    }

    public function testShouldSetCustomerOrganization()
    {
        $organization = new Organization();
        $customer = new B2bCustomer();
        $opportunity = new Opportunity();
        $opportunity->setCustomer($customer);
        $opportunity->setOrganization($organization);

        $builder = new OpportunityRelationsBuilder($opportunity);
        $builder->buildCustomer();

        $this->assertSame($organization, $customer->getOrganization());
    }

    /**
     * @dataProvider customerRelationIdentifiersProvider
     *
     * @param int|null $customerId
     * @param int|null $contactId
     */
    public function testShouldSetCustomerContactIfAtLeastOneOrBothRecordsAreNew($customerId, $contactId)
    {
        $opportunityContact = new Contact();
        $opportunityContact->setId($contactId);
        $customer = new B2bCustomer();
        $this->setObjectId($customer, $customerId);
        $opportunity = new Opportunity();
        $opportunity->setCustomer($customer);
        $opportunity->setContact($opportunityContact);

        $builder = new OpportunityRelationsBuilder($opportunity);
        $builder->buildCustomer();

        $this->assertSame($opportunityContact, $customer->getContact());
    }

    public function customerRelationIdentifiersProvider()
    {
        return [
            ['customerId' => 69, 'contactId' => null],
            ['customerId' => null, 'contactId' => 69],
            ['customerId' => null, 'contactId' => null],
        ];
    }

    public function testShouldNotSetCustomerContactIfAlreadyExists()
    {
        $customerContact = new Contact();
        $opportunityContact = new Contact();
        $customer = new B2bCustomer();
        $customer->setContact($customerContact);
        $opportunity = new Opportunity();
        $opportunity->setCustomer($customer);
        $opportunity->setContact($opportunityContact);

        $builder = new OpportunityRelationsBuilder($opportunity);
        $builder->buildCustomer();

        $this->assertSame($customerContact, $customer->getContact());
        $this->assertNotSame($opportunityContact, $customer->getContact());
    }

    public function testShouldNotSetCustomerContactIfBothRecordsAreOld()
    {
        $opportunityContact = new Contact();
        $opportunityContact->setId(1);
        $customer = new B2bCustomer();
        $this->setObjectId($customer, 1);
        $opportunity = new Opportunity();
        $opportunity->setCustomer($customer);
        $opportunity->setContact($opportunityContact);

        $builder = new OpportunityRelationsBuilder($opportunity);
        $builder->buildCustomer();

        $this->assertNull($customer->getContact());
    }

    /**
     * @dataProvider accountRelationIdentifiersProvider
     *
     * @param int|null $accountId
     * @param int|null $contactId
     */
    public function testShouldAddContactToAccountIfAtLeastOneOrBothRecordsAreNew($accountId, $contactId)
    {
        $contact = new Contact();
        $contact->setId($contactId);
        $account = new Account();
        $account->setId($accountId);

        $customer = new B2bCustomer();
        $customer->setAccount($account);

        $opportunity = new Opportunity();
        $opportunity->setCustomer($customer);
        $opportunity->setContact($contact);

        $builder = new OpportunityRelationsBuilder($opportunity);
        $builder->buildAccount();

        $this->assertTrue($account->getContacts()->contains($contact));
    }

    public function accountRelationIdentifiersProvider()
    {
        return [
            ['accountId' => 69, 'contactId' => null],
            ['accountId' => null, 'contactId' => 69],
            ['accountId' => null, 'contactId' => null],
        ];
    }

    /**
     * @param object $object
     * @param int $id
     */
    private function setObjectId($object, $id)
    {
        $reflection = new \ReflectionObject($object);
        $propertyReflection = $reflection->getProperty('id');
        $propertyReflection->setAccessible(true);
        $propertyReflection->setValue($object, $id);
    }
}
