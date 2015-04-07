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
            $calendar = $this->getCalendar($event);
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
            $user = $this->getUserReference($connection['calendar user uid']);
            $targetUser = $this->getUserReference($connection['target calendar user uid']);
            $userCalendar = $this->calendars->findDefaultCalendar($user->getId(), $user->getOrganization()->getId());
            $targetUserCalendar = $this->calendars->findDefaultCalendar(
                $targetUser->getId(),
                $targetUser->getOrganization()->getId()
            );
            if ($user->getId() !== $targetUser->getId()) {
                $calendarProperty = new CalendarProperty();
                $calendarProperty
                    ->setTargetCalendar($targetUserCalendar)
                    ->setCalendarAlias($connection['alias'])
                    ->setCalendar($userCalendar->getId());
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
     * @param array $event
     * @return null|Calendar
     * @throws EntityNotFoundException
     */
    protected function getCalendar(array $event)
    {
        $user = $this->getUserReference($event['user uid']);
        $calendar = $this->calendars->findDefaultCalendar($user->getId(), $event['organization uid']);
        if ($calendar === null) {
            throw new \InvalidArgumentException(sprintf(
                'Calendar not found with parameters user_owner_id = %s and organization_id = %s',
                $user->getId(),
                $event['organization uid']
            ));
        }
        return $calendar;
    }
}
