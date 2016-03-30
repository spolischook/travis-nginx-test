<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\MailChimp;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use OroCRM\Bundle\MailChimpBundle\Entity\Member;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadMailChimpMemberData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @return array
     */
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'organization uid',
                'integration uid',
                'mailchimp subscriber list uid',
            ]
        );
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'members' => $this->loadData('mailchimp/members.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();
        foreach ($data['members'] as $memberData) {
            $member = new Member();
            $member->setOwner($this->getOrganizationReference($memberData['organization uid']));
            $member->setSubscribersList(
                $this->getMailChimpSubscriberListReference($memberData['mailchimp subscriber list uid'])
            );
            $member->setChannel($this->getMailChimpIntegrationReference($memberData['integration uid']));
            $this->setObjectValues($member, $memberData);
            $manager->persist($member);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 38;
    }
}
