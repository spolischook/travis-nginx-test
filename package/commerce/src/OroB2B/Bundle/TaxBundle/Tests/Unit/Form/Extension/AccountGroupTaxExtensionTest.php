<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Form\Extension;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountGroupType;
use OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode;
use OroB2B\Bundle\TaxBundle\Form\Extension\AccountGroupTaxExtension;
use OroB2B\Bundle\TaxBundle\Form\Extension\AccountTaxExtension;

class AccountGroupTaxExtensionTest extends AbstractAccountTaxExtensionTest
{
    /**
     * @return AccountTaxExtension
     */
    protected function getExtension()
    {
        return new AccountGroupTaxExtension($this->doctrineHelper, 'OroB2BTaxBundle:AccountTaxCode');
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(AccountGroupType::NAME, $this->getExtension()->getExtendedType());
    }

    public function testOnPostSubmitNewAccountGroup()
    {
        $this->prepareDoctrineHelper(true, true);

        $account = $this->createTaxCodeTarget();
        $event = $this->createEvent($account);

        $taxCode = $this->createTaxCode(1);

        $this->assertTaxCodeAdd($event, $taxCode);
        $this->entityRepository->expects($this->once())
            ->method($this->getRepositoryFindMethod());

        $this->getExtension()->onPostSubmit($event);

        $this->assertEquals([$account], $taxCode->getAccountGroups()->toArray());
    }

    public function testOnPostSubmitExistingAccountGroup()
    {
        $this->prepareDoctrineHelper(true, true);

        $accountGroup = $this->createTaxCodeTarget(1);
        $event = $this->createEvent($accountGroup);

        $newTaxCode = $this->createTaxCode(1);
        $taxCodeWithAccountGroup = $this->createTaxCode(2);
        $taxCodeWithAccountGroup->addAccountGroup($accountGroup);

        $this->assertTaxCodeAdd($event, $newTaxCode);
        $this->entityRepository->expects($this->once())
            ->method($this->getRepositoryFindMethod())
            ->will($this->returnValue($taxCodeWithAccountGroup));

        $this->getExtension()->onPostSubmit($event);

        $this->assertEquals([$accountGroup], $newTaxCode->getAccountGroups()->toArray());
        $this->assertEquals([], $taxCodeWithAccountGroup->getAccountGroups()->toArray());
    }

    /**
     * @param int|null $id
     * @return AccountGroup
     */
    protected function createTaxCodeTarget($id = null)
    {
        return $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', ['id' => $id]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRepositoryFindMethod()
    {
        return 'findOneByAccountGroup';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTestableCollection(AccountTaxCode $accountTaxCode)
    {
        return $accountTaxCode->getAccountGroups();
    }
}
