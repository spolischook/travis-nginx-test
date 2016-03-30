<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class LoadOrganizationData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @return array
     */
    public function getData()
    {
        return [
            'organizations' => $this->loadData('organizations.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();
        $main = true;
        foreach ($data['organizations'] as $organizationData) {
            if ($main) {
                $organization = $this->getMainOrganization();
                $main         = false;
            } else {
                $organization = new Organization();
                $organization->addUser($this->getMainUser());
            }
            $organization->setEnabled(true);
            $this->setObjectValues($organization, $organizationData);

            $this->setOrganizationReference($organizationData['uid'], $organization);
            $manager->persist($organization);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }
}
