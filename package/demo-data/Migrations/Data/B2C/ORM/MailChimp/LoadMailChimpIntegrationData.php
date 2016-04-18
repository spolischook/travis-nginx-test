<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\MailChimp;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;

use OroCRM\Bundle\MailChimpBundle\Entity\MailChimpTransport;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadMailChimpIntegrationData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @return array
     */
    public function getData()
    {
        return [
            'integrations' => $this->loadData('mailchimp/integrations.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['integrations'] as $integrationData) {
            $transport = new MailChimpTransport();
            $transport->setApiKey($integrationData['api key']);
            $transport->setActivityUpdateInterval(0);
            // Call for setup parameters bag
            $transport->getSettingsBag();
            $manager->persist($transport);

            $integration = new Integration();
            $integration->setDefaultUserOwner($this->getMainUser());
            $integration->setType('mailchimp');
            $integration->setName($integrationData['name']);
            $integration->setTransport($transport);
            $integration->setOrganization($this->getOrganizationReference($integrationData['organization uid']));
            $integration->setConnectors(
                [
                    'list',
                    'campaign',
                    'static_segment',
                    'member',
                    'member_activity',
                    'member_activity_send',
                    'member_activity_abuse',
                    'member_activity_unsubscribe'
                ]
            );
            $integration->setEnabled(true);

            $this->setMailChimpIntegrationReference($integrationData['uid'], $integration);
            $manager->persist($integration);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 34;
    }
}
