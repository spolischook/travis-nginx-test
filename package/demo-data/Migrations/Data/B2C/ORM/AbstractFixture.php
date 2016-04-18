<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\UserBundle\Entity\User;

use OroCRMPro\Bundle\DemoDataBundle\Model\FileLoaderTrait;
use OroCRMPro\Bundle\DemoDataBundle\Model\GenerateDateTrait;
use OroCRMPro\Bundle\DemoDataBundle\Exception\EntityNotFoundException;

abstract class AbstractFixture extends EntityReferences implements ContainerAwareInterface
{
    use FileLoaderTrait, GenerateDateTrait;

    const MAIN_USER_ID = 1;

    const DATA_FOLDER = 'data';

    /** @var  EntityManager */
    protected $em;

    /** @var PropertyAccessor */
    protected $accessor;

    /** @var ContainerInterface */
    protected $container;

    /** @var  EntityRepository */
    protected $userRepository;

    /** @var  OrganizationRepository */
    protected $organizationRepository;

    /**
     * @return string
     */
    protected function getDataDirectory()
    {
        $classData = new \ReflectionClass($this);
        $dir       = __DIR__ . DIRECTORY_SEPARATOR;

        preg_match('#Migrations[/\\\]Data[/\\\](\w*[/\\\]ORM)#', $classData->getFilename(), $matches);
        if (!empty($matches[1])) {
            $dir .= '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $matches[1] . DIRECTORY_SEPARATOR;
        }
        $dir .= static::DATA_FOLDER . DIRECTORY_SEPARATOR;

        return $dir;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->em        = $container->get('doctrine')->getManager();
        $this->container = $container;

        $this->userRepository         = $this->em->getRepository('OroUserBundle:User');
        $this->organizationRepository = $this->em->getRepository('OroOrganizationBundle:Organization');

        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * @throws EntityNotFoundException
     * @return User
     */
    public function getMainUser()
    {
        if ($this->hasUserReference('main')) {
            return $this->getUserReference('main');
        } else {
            /** @var User $entity */
            $entity = $this->userRepository->find(self::MAIN_USER_ID);
            if (!$entity) {
                throw new EntityNotFoundException('Main user does not exist.');
            }
        }

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
     * @param $object
     * @param $fieldName
     * @param $value
     */
    protected function setObjectValue($object, $fieldName, $value)
    {
        $this->accessor->setValue($object, $fieldName, $value);
    }

    /**
     * @return array
     */
    protected function getExcludeProperties()
    {
        return [
            'uid',
        ];
    }

    /**
     * @param       $object
     * @param array $values
     * @param array $exclude
     */
    protected function setObjectValues($object, $values = [], $exclude = [])
    {
        $exclude = array_merge($this->getExcludeProperties(), $exclude);
        foreach ($values as $fieldName => $value) {
            if (in_array($fieldName, $exclude)) {
                continue;
            }
            $this->setObjectValue($object, $fieldName, $value);
        }
    }

    /**
     * @param User $user
     */
    protected function setSecurityContext($user)
    {
        $securityContext = $this->container->get('security.context');
        /** @var Organization $organization */
        $organization = $user->getOrganization();
        /**
         * Fix: for admin user
         */
        if ($organization && $organization->getName() === null) {
            $this->em->refresh($user);
            $organization = $user->getOrganization();
        }
        $token = new UsernamePasswordOrganizationToken($user, $user->getUsername(), 'main', $organization);
        $securityContext->setToken($token);
    }

    /**
     * Remove event $instance listener (for manual setup created/updated dates)
     *
     * @param       $instance
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
