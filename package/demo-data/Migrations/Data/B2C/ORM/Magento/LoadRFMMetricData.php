<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Magento;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use OroCRM\Bundle\AnalyticsBundle\Entity\RFMMetricCategory;
use OroCRM\Bundle\ChannelBundle\Entity\Channel;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadRFMMetricData extends AbstractFixture implements OrderedFixtureInterface
{
    /** @var  Channel */
    protected $dataChannel;

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
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['rmf_metrics'] as $metricData) {
            $dataChannel = $this->getChannelReference($metricData['channel uid']);
            $category    = new RFMMetricCategory();
            $category->setCategoryIndex($metricData['index'])
                ->setChannel($dataChannel)
                ->setCategoryType($metricData['type'])
                ->setMinValue($metricData['min'])
                ->setMaxValue($metricData['max'])
                ->setOwner($this->getOrganizationReference($metricData['organization uid']));
            $manager->persist($category);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 33;
    }
}
