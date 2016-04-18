<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

class LoadRequestStatusData extends AbstractFixture
{
    const NAME_NOT_DELETED = 'not_deleted';
    const NAME_DELETED = 'is_deleted';
    const NAME_IN_PROGRESS = 'in_progress';
    const NAME_CLOSED = 'closed';

    const PREFIX = 'request.status.';

    /**
     * @var array
     */
    protected $requestStatuses = [
        [
            'order' => 10,
            'name' => self::NAME_NOT_DELETED,
            'label' => 'Open',
            'locale' => 'en_US',
            'deleted' => false,
        ],
        [
            'order' => 20,
            'name' => self::NAME_IN_PROGRESS,
            'label' => 'In Progress',
            'locale' => 'en_US',
            'deleted' => false,
        ],
        [
            'order' => 30,
            'name' => self::NAME_CLOSED,
            'label' => 'Closed',
            'locale' => 'en_US',
            'deleted' => false,
        ],
        [
            'order' => 40,
            'name' => self::NAME_DELETED,
            'label' => 'Deleted',
            'locale' => 'en_US',
            'deleted' => true,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $om)
    {
        foreach ($this->requestStatuses as $requestStatusData) {
            $requestStatus = new RequestStatus();

            $requestStatus
                ->setSortOrder($requestStatusData['order'])
                ->setName($requestStatusData['name'])
                ->setLabel($requestStatusData['label'])
                ->setLocale($requestStatusData['locale'])
                ->setDeleted($requestStatusData['deleted']);

            $this->setReference('request.status.' . $requestStatus->getName(), $requestStatus);
            $om->persist($requestStatus);
        }

        $om->flush();
    }
}
