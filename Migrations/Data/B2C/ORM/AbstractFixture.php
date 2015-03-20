<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Common\DataFixtures\AbstractFixture as DoctrineAbstractFixture;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;

use OroCRMPro\Bundle\DemoDataBundle\Field\FieldHelper;
use OroCRMPro\Bundle\DemoDataBundle\EventListener\ActivityListSubscriber;


abstract class AbstractFixture extends DoctrineAbstractFixture implements ContainerAwareInterface
{
    const DATA_FOLDER = 'data';

    /** @var  EntityManager */
    protected $em;

    /** @var ContainerInterface */
    protected $container;

    /** @var FieldHelper */
    protected $fieldHelper;

    /** @var  EntityRepository */
    protected $userRepository;

    /** @var  EntityRepository */
    protected $organizationRepository;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->em = $container->get('doctrine')->getManager();
        $this->container = $container;

        $this->fieldHelper = new FieldHelper();

        $this->userRepository = $this->em->getRepository('OroUserBundle:User');
        $this->organizationRepository = $this->em->getRepository('OroOrganizationBundle:Organization');

        $subscriber = new ActivityListSubscriber();
        $this->em->getEventManager()->addEventSubscriber($subscriber);
    }

    /**
     * @throws EntityNotFoundException
     * @return User
     */
    public function getMainUser()
    {
        if ($this->hasReference('MainUser')) {
            $entity = $this->getReference('MainUser');
            if ($entity instanceof User) {
                return $entity;
            }
        }

        $entity = $this->userRepository->find(1);
        if (!$entity) {
            throw new EntityNotFoundException('Main user does not exist.');
        }

        $this->addReference('MainUser', $entity);

        return $entity;
    }

    /**
     * @return Organization
     * @throws EntityNotFoundException
     */
    protected function getMainOrganization()
    {
        $entity = $this->organizationRepository->getFirst();

        if (!$entity) {
            throw new EntityNotFoundException('Main organization is not defined.');
        }
        return $entity;
    }

    /**
     * @param $name
     * @return mixed
     */
    protected function loadData($name)
    {
        static $data = [];
        if (!isset($data[$name])) {
            $path = __DIR__ . DIRECTORY_SEPARATOR . static::DATA_FOLDER . DIRECTORY_SEPARATOR . $name;
            $data[$name] = $this->loadDataFromCSV($path);
        }

        return $data[$name];
    }

    /**
     * @param $path
     * @return array
     */
    protected function loadDataFromCSV($path)
    {
        if(!file_exists($path))
        {
            throw new FileNotFoundException($path);
        }

        $data = [];
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle, 1000, ',');

        if (empty($headers)) {
            return [];
        }
        $headers = array_map('strtolower', $headers);
        if (!in_array('uid', $headers)) {
            throw new NoSuchPropertyException('Property: "uid" does not exists');
        }
        while ($info = fgetcsv($handle, 1000, ',')) {
            if(count($info) !== count($headers))
            {
                continue;
            }
            $tempData = array_combine($headers, array_values($info));
            if ($tempData) {
                $data[] = $tempData;
            }
        }
        fclose($handle);

        return $data;
    }

    /**
     * @param $object
     * @param $fieldName
     * @param $value
     * @throws \Exception
     */
    protected function setObjectValue($object, $fieldName, $value)
    {
        try {
            $this->fieldHelper->setObjectValue($object, $fieldName, $value);
        } catch (\Exception $e) {
            echo $e->getMessage(), "\n";
        }
    }

    /**
     * @param $object
     * @param array $values
     */
    protected function setObjectValues($object, $values = [])
    {
        foreach ($values as $fieldName => $value) {
            $this->setObjectValue($object, $fieldName, $value);
        }
    }

    /**
     * @param User $user
     */
    protected function setSecurityContext($user)
    {
        $securityContext = $this->container->get('security.context');
        $token = new UsernamePasswordOrganizationToken($user, $user->getUsername(), 'main', $this->getMainOrganization());
        $securityContext->setToken($token);
    }

    /**
     * @param $reference
     * @return object
     * @throws EntityNotFoundException
     */
    public function getReferenceByName($reference)
    {
        if ($this->hasReference($reference)) {
            return $this->getReference($reference);
        }
        throw new EntityNotFoundException('Reference ' . $reference . ' not found.');
    }

    /**
     * Generate Created date
     * @return \DateTime
     */
    protected function generateCreatedDate()
    {
        // Convert to timetamps
        $min = strtotime('now - 1 month');
        $max = strtotime('now - 1 day');
        $val = rand($min, $max);

        // Convert to timetamps
        $minTime = strtotime('12:00:00');
        $maxTime = strtotime('19:00:00');

        $valTime = rand($minTime, $maxTime);

        $date = date('Y-m-d', $val) . ' ' . date('H:i:s', $valTime);
        return new \DateTime($date, new \DateTimeZone('UTC'));
    }

    /**
     * Generate Updated date
     * @param \DateTime $created
     * @return \DateTime
     */
    protected function generateUpdatedDate(\DateTime $created)
    {
        // Convert to timetamps
        $min = strtotime($created->format('Y-M-d H:i:s'));
        $max = strtotime('now - 1 day');
        $val = rand($min, $max);

        // Convert to timetamps
        $minTime = strtotime($created->format('H:i:s'));
        $maxTime = strtotime('19:00:00');

        $valTime = rand($minTime, $maxTime);

        $date = date('Y-m-d', $val) . ' ' . date('H:i:s', $valTime);
        return new \DateTime($date, new \DateTimeZone('UTC'));
    }

    /**
     * Remove event $instance listener (for manual set created/updated dates)
     *
     * @param $instance
     * @param array $events
     */
    protected function removeEventListener($instance, $events = ['prePersist', 'postPersist'])
    {
        foreach ($this->em->getEventManager()->getListeners() as $event => $listeners) {
            foreach ($listeners as $hash => $listener) {
                if ($listener instanceof $instance) {
                    $this->em->getEventManager()->removeEventListener($events, $listener);
                    $this->em->getEventManager();
                    break 2;
                }
            }
        }
    }
}
