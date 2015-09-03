<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use OroCRM\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;

class LoadZendeskTransportData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @return array
     */
    public function getData()
    {
        return [
            'transports' => $this->loadData('zendesk/transports.csv'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['integrations'] as $integrationData) {
            $transport = new ZendeskRestTransport();
            $this->setEntityPropertyValues($transport, $integrationData, array('reference'));
            $manager->persist($transport);
            $this->setReference($data['zendesk_reference'], $transport);
        }
    }

    /**
     * Sets $entity object properties from $data array
     *
     * @param object $entity
     * @param array $data
     * @param array $excludeProperties
     */
    public function setEntityPropertyValues($entity, array $data, array $excludeProperties = array())
    {
        foreach ($data as $property => $value) {
            if (in_array($property, $excludeProperties)) {
                continue;
            }
            PropertyAccess::createPropertyAccessor()->setValue($entity, $property, $value);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 34;
    }
}
