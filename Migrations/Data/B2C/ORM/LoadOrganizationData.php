<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class LoadOrganizationData extends AbstractFixture
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
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();
        $main = true;
        foreach($data['organizations'] as $organizationData)
        {
            if($main)
            {
                $organization = $this->getMainOrganization();
                $main = false;
            }
            else {
                $organization = new Organization();
            }
            $organization->setEnabled(true);
            $this->setObjectValues($organization, $organizationData);

            $this->setOrganizationReference($organizationData['uid'], $organization);
            $manager->persist($organization);
        }
        $manager->flush();
    }
}
