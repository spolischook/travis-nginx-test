<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use OroCRM\Bundle\TaskBundle\Entity\Task;
use OroCRM\Bundle\TaskBundle\Entity\TaskPriority;
use OroCRMPro\Bundle\DemoDataBundle\Model\WeekendCheckerTrait;

class LoadUsersTasksData extends AbstractFixture implements OrderedFixtureInterface
{
    use WeekendCheckerTrait;

    const DEFAULT_TASK_PRIORITY_NAME = 'low';

    /** @var  EntityRepository */
    protected $prioritiesRepository;

    /** @var array */
    protected $priorities = [];

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);

        $this->prioritiesRepository = $this->em->getRepository('OroCRMTaskBundle:TaskPriority');
        $this->priorities           = array_reduce(
            $this->prioritiesRepository->findAll(),
            function ($carry, $item) {
                /** @var TaskPriority $item */
                $carry[$item->getName()] = $item;
                return $carry;
            },
            []
        );
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
                'due time',
                'priority',
                'user uid'
            ]
        );
    }

    /**
     * @return array
     */
    protected function getData()
    {
        return [
            'tasks' => $this->loadData('tasks/users.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data  = $this->getData();
        $tasks = array_reduce(
            $data['tasks'],
            function ($carry, $item) {
                $carry[$item['day']][] = $item;
                return $carry;
            },
            []
        );
        $manager->getClassMetadata('OroCRM\Bundle\TaskBundle\Entity\Task')->setLifecycleCallbacks([]);
        $now  = new \DateTime();
        $date = new \DateTime();
        for ($i = 0; array_key_exists($i, $data['tasks']); $date->add(new \DateInterval('P1D'))) {
            if (!$this->isWeekEnd($date)) {
                $day      = $i + 1;
                $dayTasks = array_key_exists($day, $tasks) ? $tasks[$day] : [];
                foreach ($dayTasks as $taskData) {
                    $owner = $this->getUserReference($taskData['user uid']);
                    /** @var Organization $organization */
                    $organization  = $owner->getOrganization();
                    $dueDate       = new \DateTime($date->format('Y-m-d') . ' ' . $taskData['due time']);
                    $taskCreatedAt = (new \DateTime())->setTimestamp(
                        rand($owner->getCreatedAt()->getTimestamp(), $now->getTimestamp())
                    );

                    $task = new Task();
                    $this->setObjectValues($task, $taskData);
                    $task->setDueDate($dueDate);
                    $task->setOwner($owner);
                    $task->setOrganization($organization);
                    $task->setCreatedAt($taskCreatedAt);
                    $task->setUpdatedAt(
                        (new \DateTime())->setTimestamp(rand($taskCreatedAt->getTimestamp(), $now->getTimestamp()))
                    );
                    $taskPriority = $this->getPriority($taskData['priority']);
                    $task->setTaskPriority($taskPriority);
                    $manager->persist($task);
                }
                $i++;
            }
        }
        $manager->flush();
    }

    /**
     * @param $dataPriority
     * @return null|TaskPriority
     */
    protected function getPriority($dataPriority)
    {
        $taskPriority = $this->prioritiesRepository->find($dataPriority);
        if ($taskPriority === null) {
            return $this->prioritiesRepository->find(self::DEFAULT_TASK_PRIORITY_NAME);
        }
        return $taskPriority;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 7;
    }
}
