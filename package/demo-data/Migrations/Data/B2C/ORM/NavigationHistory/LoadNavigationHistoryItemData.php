<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\NavigationHistory;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadNavigationHistoryItemData extends AbstractFixture implements OrderedFixtureInterface
{
    const ACCOUNT_VIEW_ROUTE = 'orocrm_account_view';
    const CONTACT_VIEW_ROUTE = 'orocrm_contact_view';

    /**
     * {@inheritdoc}
     */
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'user uid',
                'account uid',
                'contact uid',
                'organization uid',
            ]
        );
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'accounts' => $this->loadData('navigation_history/accounts.csv'),
            'contacts' => $this->loadData('navigation_history/contacts.csv'),
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadAccountNavigationHistory();
        $this->loadContactNavigationHistory();
        $manager->flush();
    }


    /**
     * Load Account Navigation History Item
     */
    protected function loadAccountNavigationHistory()
    {
        $data = $this->getData();
        foreach ($data['accounts'] as $accountData) {
            $account = $this->getAccountReference($accountData['account uid']);
            $history = $this->createNavigationHistory($account->getId(), self::ACCOUNT_VIEW_ROUTE, $accountData);
            $this->em->persist($history);
        }
    }

    /**
     * Load Contact Navigation History Item
     */
    protected function loadContactNavigationHistory()
    {
        $data = $this->getData();
        foreach ($data['contacts'] as $contactData) {
            $contact = $this->getContactReference($contactData['contact uid']);
            $history = $this->createNavigationHistory($contact->getId(), self::CONTACT_VIEW_ROUTE, $contactData);
            $this->em->persist($history);
        }
    }

    /**
     * @param int    $entityId
     * @param string $route
     * @param array  $data
     * @return NavigationHistoryItem
     */
    protected function createNavigationHistory($entityId, $route, $data = [])
    {
        $history = new NavigationHistoryItem();
        $history->setRoute($route);
        $history->setUrl('/');
        $history->setTitle("{}");
        $history->setVisitCount(1);

        $history->setUser($this->getUserReference($data['user uid']));
        $history->setEntityId($entityId);
        $history->setOrganization($this->getOrganizationReference($data['organization uid']));

        return $history;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 21;
    }
}
