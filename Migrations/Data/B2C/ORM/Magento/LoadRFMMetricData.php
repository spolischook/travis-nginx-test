<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Magento;

use JMS\JobQueueBundle\Entity\Job;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use OroCRM\Bundle\ChannelBundle\Entity\Channel;
use OroCRM\Bundle\AnalyticsBundle\Command\CalculateAnalyticsCommand;
use OroCRM\Bundle\AnalyticsBundle\Entity\RFMMetricCategory;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadRFMMetricData extends AbstractFixture implements DependentFixtureInterface
{
    /** @var  Channel */
    protected $dataChannel;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadOrganizationData',
            __NAMESPACE__ . '\\LoadMagentoIntegrationData',
        ];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'rmf_metrics' => $this->loadData('magento/rfm_metrics.csv'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['rmf_metrics'] as $metricData) {
            $dataChannel = $this->getIntegrationDataChannelReference($metricData['integration uid']);
            $category = new RFMMetricCategory();
            $category->setCategoryIndex($metricData['index'])
                ->setChannel($dataChannel)
                ->setCategoryType($metricData['type'])
                ->setMinValue($metricData['min'])
                ->setMaxValue($metricData['max'])
                ->setOwner($this->getOrganizationReference($metricData['organization uid']));
            $manager->persist($category);
        }
        $this->addRFMMetricJob();
        $manager->flush();
    }

    /**
     * Add RFM Metric Job for update customers RFM statistic
     */
    protected function addRFMMetricJob()
    {
       $job = new Job(CalculateAnalyticsCommand::COMMAND_NAME);
       $this->em->persist($job);
    }
}
