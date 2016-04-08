<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\MailChimp;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use OroCRM\Bundle\MailChimpBundle\Entity\SubscribersList;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadMailChimpSubscriberListData extends AbstractFixture implements OrderedFixtureInterface
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
            ]
        );
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'subscribers_list' => $this->loadData('mailchimp/subscribers_list.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['subscribers_list'] as $listData) {
            $list = new SubscribersList();
            $this->setObjectValues($list, $listData);
            $list->setEmailTypeOption(false);
            $list->setEmailTypeOption(true);
            $list->setUseAwesomeBar(true);
            $list->setMergeVarConfig(
                [
                    [
                        'name'       => 'Email Address',
                        'req'        => true,
                        'field_type' => 'email',
                        'public'     => true,
                        'show'       => true,
                        'order'      => '1',
                        'default'    => null,
                        'helptext'   => null,
                        'size'       => '25',
                        'tag'        => 'EMAIL',
                        'id'         => 0
                    ],
                    [
                        'name'       => 'First Name',
                        'req'        => false,
                        'field_type' => 'text',
                        'public'     => true,
                        'show'       => true,
                        'order'      => '2',
                        'default'    => '',
                        'helptext'   => '',
                        'size'       => '25',
                        'tag'        => 'FNAME',
                        'id'         => 1
                    ],
                    [
                        'name'       => 'Last Name',
                        'req'        => false,
                        'field_type' => 'text',
                        'public'     => true,
                        'show'       => true,
                        'order'      => '3',
                        'default'    => '',
                        'helptext'   => '',
                        'size'       => '25',
                        'tag'        => 'LNAME',
                        'id'         => 2
                    ]
                ]
            );

            $list->setOwner($this->getOrganizationReference($listData['organization uid']));
            $list->setChannel($this->getMailChimpIntegrationReference($listData['integration uid']));

            $this->setMailChimpSubscriberListReference($listData['uid'], $list);
            $manager->persist($list);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 36;
    }
}
