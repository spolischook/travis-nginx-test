<?php

namespace OroPro\Bundle\OrganizationBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\UserBundle\Entity\User;

class AddOrganizationEmailUser extends AbstractFixture
{
    const BATCH_SIZE = 100;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $queryBuilder = $manager->getRepository('OroEmailBundle:EmailUser')
            ->createQueryBuilder('e')
            ->select('e');
        $iterator = new BufferedQueryResultIterator($queryBuilder);
        $iterator->setBufferSize(self::BATCH_SIZE);

        $itemsCount = 0;
        $entities   = [];

        foreach ($iterator as $emailUser) {
            $owner = $emailUser->getOwner();
            if ($owner instanceof User) {
                foreach ($owner->getOrganizations() as $organization) {
                    if ($organization !== $owner->getOrganization()
                        && !$organization->getIsGlobal()
                    ) {
                        $newEmailUser = clone $emailUser;
                        $newEmailUser->setOrganization($organization);
                        $itemsCount++;
                        $entities[] = $newEmailUser;
                    }
                }
            }

            if (0 === $itemsCount % self::BATCH_SIZE) {
                $this->saveEntities($manager, $entities);
                $entities = [];
            }
        }

        if ($itemsCount % self::BATCH_SIZE > 0) {
            $this->saveEntities($manager, $entities);
        }
    }

    /**
     * @param ObjectManager $manager
     * @param array         $entities
     */
    protected function saveEntities(ObjectManager $manager, array $entities)
    {
        foreach ($entities as $entity) {
            $manager->persist($entity);
        }
        $manager->flush();
        $manager->clear();
    }
}
