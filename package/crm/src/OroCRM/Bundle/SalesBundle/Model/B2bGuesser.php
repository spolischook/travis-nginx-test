<?php

namespace OroCRM\Bundle\SalesBundle\Model;

use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\AccountBundle\Entity\Account;
use OroCRM\Bundle\SalesBundle\Entity\B2bCustomer;
use OroCRM\Bundle\SalesBundle\Entity\Lead;

class B2bGuesser
{
    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @param ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param Lead $lead
     *
     * @return B2bCustomer
     */
    public function getCustomer(Lead $lead)
    {
        $customer = $lead->getCustomer();

        $customer = (null === $customer) ? $this->findCustomerByCompanyName($lead->getCompanyName()) : $customer;

        if ($customer) {
           return $customer;
        }

        return $this->createCustomer($lead);
    }

    /**
     * @param Lead $lead
     * 
     * @return B2bCustomer
     */
    protected function createCustomer(Lead $lead)
    {
        $b2bCustomer = new B2bCustomer();

        $account = $this->findAccountByCompanyName($lead->getCompanyName());

        $b2bCustomer->setName($lead->getCompanyName());
        $b2bCustomer->setDataChannel($lead->getDataChannel());
        $b2bCustomer->setAccount($account);
        $b2bCustomer->addLead($lead);

        return $b2bCustomer;
    }

    /**
     * @param string $companyName
     *
     * @return array
     */
    protected function findCustomerByCompanyName($companyName)
    {
        $repository = $this->manager->getRepository('OroCRMSalesBundle:B2bCustomer');
        
        $qb = $repository->createQueryBuilder('c');

        $result = $qb->leftJoin('OroCRMAccountBundle:Account', 'a', 'WITH', 'a = c.account')
                     ->groupBy('c.id')
                     ->where($qb->expr()->orX(
                        $qb->expr()->eq('c.name', ':company_name'),
                        $qb->expr()->eq('a.name', ':company_name')
                     ))
                     ->setParameter('company_name', $companyName)
                     ->getQuery()
                     ->getArrayResult();

        $resultCount = count($result);

        return (!$resultCount || $resultCount > 1) ? null : reset($result);
    }

    /**
     * @param string $companyName
     *
     * @return Account|null
     */
    protected function findAccountByCompanyName($companyName)
    {
        $repository = $this->manager->getRepository('OroCRMAccountBundle:Account');

        $result = $repository->createQueryBuilder('a')
            ->where('a.name = :company_name')
            ->setParameter('company_name', $companyName)
            ->getQuery()
            ->getSingleResult();

        return $result;
    }
}
