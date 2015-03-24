<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;


use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;

use OroCRM\Bundle\ChannelBundle\Builder\BuilderFactory;
use OroCRM\Bundle\ChannelBundle\Entity\Channel;
use OroCRM\Bundle\MagentoBundle\Entity\MagentoSoapTransport;


class LoadMagentoIntegrationData extends AbstractFixture implements DependentFixtureInterface
{
    /** @var BuilderFactory */
    protected $factory;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadWebsiteData',
        ];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'magento_integration' => $this->loadData('magento_integration.csv')
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->factory = $container->get('orocrm_channel.builder.factory');
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach($data['magento_integration'] as $integrationData) {
            $transport = new MagentoSoapTransport();
            $transport->setApiUser($integrationData['api user']);
            $transport->setApiKey($integrationData['api key']);
            $transport->setWsdlUrl($integrationData['wsdl url']);
            $manager->persist($transport);

            $integration = new Integration();
            $integration->setType('magento');
            $integration->setConnectors(['customer', 'cart', 'order']);

            $integration->setName($integrationData['integration name']);
            $integration->setTransport($transport);
            $organization = $this->getOrganizationReference($integrationData['organization uid']);
            $integration->setOrganization($organization);

            $this->setReference('Integration:' . $integrationData['uid'], $integration);
            $manager->persist($integration);

            $builder = $this->factory->createBuilderForIntegration($integration);
            $builder->setOwner($integration->getOrganization());
            $builder->setDataSource($integration);
            $builder->setStatus($integration->isEnabled() ? Channel::STATUS_ACTIVE : Channel::STATUS_INACTIVE);
            $dataChannel = $builder->getChannel();
            $this->setReference('IntegrationDataChannel:' . $integrationData['uid'], $dataChannel);
            $manager->persist($dataChannel);
        }
        $manager->flush();
    }
}
