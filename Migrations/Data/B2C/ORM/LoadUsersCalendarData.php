<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;

class LoadUsersCalendarData extends AbstractFixture implements DependentFixtureInterface
{
    /** @var CalendarRepository */
    protected $calendarRepository;

    /** @var  EntityRepository */
    protected $roleRepository;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->calendarRepository = $this->em->getRepository('OroCalendarBundle:Calendar');
        $this->roleRepository = $this->em->getRepository('OroUserBundle:Role');
    }

    /**
     * {@inheritdoc}
     */
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'day',
                'start time',
                'end time'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\\LoadDefaultUserData',
        ];
    }

    /**
     * @return array
     */
    protected function getData()
    {
        return [
            'events' => $this->loadData('calendar/events.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();
        $calendars = $this->calendarRepository->findAll();

        /** @var Role $userRole */
        $userRole = $this->roleRepository->findOneBy(['role' => LoadRolesData::ROLE_USER]);

        /** @var Calendar $calendar */
        foreach($calendars as $calendar) {
            /**
             * Skip user with ROLE_USER
             */
            if($calendar->getOwner()->hasRole($userRole))
            {
                continue;
            }

            $now = new \DateTime('now');
            $created = $calendar->getOwner()->getCreatedAt();
            for(;$created->format('Y-m-d') <= $now->format('Y-m-d'); $created->add(new \DateInterval('P1D')))
            {
                foreach ($data['events'] as $eventData) {
                    if($eventData['day'] == $created->format('l')) {
                        $event = new CalendarEvent();
                        $this->setObjectValues($event, $eventData);
                        $event
                            ->setStart(new \DateTime($created->format('Y-m-d') . ' ' . $eventData['start time']))
                            ->setEnd(new \DateTime($created->format('Y-m-d') . ' ' . $eventData['end time']));

                        $calendar->addEvent($event);
                        $this->setSecurityContext($calendar->getOwner());
                        $manager->persist($calendar);
                    }
                }
            }
        }
        $manager->flush();
    }

}
