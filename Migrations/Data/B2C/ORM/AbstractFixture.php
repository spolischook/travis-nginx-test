<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Common\DataFixtures\AbstractFixture as DoctrineAbstractFixture;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroCRMPro\Bundle\DemoDataBundle\Field\FieldHelper;


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
    }

    /**
     * @throws EntityNotFoundException
     * @return User
     */
    public function getMainUser()
    {
        if ($this->hasReference('OroCRMLiveDemoBundle:mainUser')) {
            $entity = $this->getReference('OroCRMLiveDemoBundle:mainUser');
            if ($entity instanceof User) {
                return $entity;
            }
        }

        $entity = $this->userRepository->find(1);
        if (!$entity) {
            throw new EntityNotFoundException('Main user does not exist.');
        }

        $this->addReference('OroCRMLiveDemoBundle:mainUser', $entity);

        return $entity;
    }

    /**
     * @return Organization
     * @throws EntityNotFoundException
     */
    protected function getMainOrganization()
    {

        if ($this->hasReference('OroCRMLiveDemoBundle:defaultOrganization')) {
            $entity = $this->getReference('OroCRMLiveDemoBundle:defaultOrganization');
            if ($entity instanceof Organization) {
                return $entity;
            }
        }

        $entity = $this->organizationRepository->getFirst();

        if (!$entity) {
            throw new EntityNotFoundException('Main organization is not defined.');
        }

        $this->setReference('OroCRMLiveDemoBundle:defaultOrganization', $entity);

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
     * TODO:Move to reset command
     * @param $repository
     * @param null $except
     */
    protected function removeOldData($repository, $except = null)
    {
    $entities = $this->em->getRepository($repository)->findAll();
    foreach ($entities as $entity)
    {
        if($entity == $except)
        {
            continue;
        }
        $this->em->remove($entity);
    }
}

    /**
     * @param $path
     * @return array
     */
    protected function loadDataFromCSV($path)
    {
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
}
