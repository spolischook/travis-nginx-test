<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityNotFoundException;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\CalendarProperty;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;

class LoadUsersCalendarData extends AbstractFixture implements DependentFixtureInterface
{
    const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    /** @var CalendarRepository */
    protected $calendars;

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
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->calendars = $this->em->getRepository('OroCalendarBundle:Calendar');
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();
        $format = static::DATE_TIME_FORMAT;
        foreach ($data['events'] as $event) {
            $calendarEvent = new CalendarEvent();
            $calendar = $this->getCalendar($event['user uid'], $event['organization uid']);
            $calendarEvent
                ->setTitle($event['title'])
                ->setDescription($event['description'])
                ->setStart(\DateTime::createFromFormat($format, $event['start']))
                ->setEnd(\DateTime::createFromFormat($format, $event['end']))
                ->setAllDay($event['all_day']);
            $calendar->addEvent($calendarEvent);
            $this->setSecurityContext($calendar->getOwner());
            $this->em->persist($calendarEvent);
            $this->setCalendarEventReference($event['uid'], $calendarEvent);
        }

        foreach ($data['connections'] as $connection) {
            $calendar = $this->getCalendar(
                $connection['calendar user uid'],
                $connection['calendar organization uid']
            );
            $targetCalendar = $this->getCalendar(
                $connection['target calendar user uid'],
                $connection['target calendar organization uid']
            );

            if ($calendar->getOwner()->getId() !== $targetCalendar->getOwner()->getId()) {
                $calendarProperty = new CalendarProperty();
                $calendarProperty
                    ->setTargetCalendar($targetCalendar)
                    ->setCalendarAlias($connection['alias'])
                    ->setCalendar($calendar->getId())
                    ->setPosition($connection['position'])
                    ->setVisible($connection['visible'])
                    ->setBackgroundColor($connection['background_color']);
                $this->em->persist($calendarProperty);
                $this->setCalendarPropertyReference($connection['uid'], $calendarProperty);
            }
        }

        $this->em->flush();
    }

    /**
     * @return array
     */
    protected function getData()
    {
        return [
            'events' => $this->loadData('calendar/events.csv'),
            'connections' => $this->loadData('calendar/connections.csv'),
        ];
    }

    /**
     * @param $userUid
     * @param $organizationUid
     * @return null|Calendar
     */
    protected function getCalendar($userUid, $organizationUid)
    {
        $user = $this->getUserReference($userUid);
        $organization = $this->getOrganizationReference($organizationUid);
        $calendar = $this->calendars->findDefaultCalendar($user->getId(), $organization->getId());
        if ($calendar === null) {
            throw new \InvalidArgumentException(sprintf(
                'Calendar not found with parameters user_owner_id = %s and organization_id = %s',
                $user->getId(),
                $organizationUid
            ));
        }
        return $calendar;
    }
}
