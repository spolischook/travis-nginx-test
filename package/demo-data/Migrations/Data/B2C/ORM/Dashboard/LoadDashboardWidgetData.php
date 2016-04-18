<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Dashboard;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\DashboardBundle\Model\WidgetModel;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadDashboardWidgetData extends AbstractFixture implements OrderedFixtureInterface
{
    /** @var  Manager */
    protected $dashboardManager;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->dashboardManager = $this->container->get('oro_dashboard.manager');
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'widgets' => $this->loadData('dashboards/widgets.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();
        $this->setSecurityContext($this->getMainUser());
        foreach ($data['widgets'] as $widgetData) {
            $dashboard      = $this->getDashboardReference($widgetData['dashboard uid']);
            $dashboardModel = $this->dashboardManager->getDashboardModel($dashboard);

            $widgetModel = $this->createWidgetModel(
                $widgetData['widget'],
                [$widgetData['position x'], $widgetData['position y']]
            );
            $this->setWidgetsOptions($widgetModel, $widgetData['options']);

            $dashboardModel->addWidget($widgetModel);
            $this->dashboardManager->save($widgetModel);
        }
        $manager->flush();
    }

    /**
     * Setup options for widgets
     *
     * @param WidgetModel $widgetModel
     * @param string      $options
     */
    protected function setWidgetsOptions(WidgetModel $widgetModel, $options = '')
    {
        if (!empty($options)) {
            $widgetOptions = unserialize(base64_decode($options));
            if (is_array($widgetOptions)) {
                if (isset($widgetOptions['dateRange']) && isset($widgetOptions['dateRange']['value'])) {
                    $this->convertDateVars($widgetOptions['dateRange']['value']);
                }
                $widgetModel->getEntity()->setOptions($widgetOptions);
            }
        }
    }

    /**
     * Create dashboard entity with admin user
     *
     * @param string $widgetName
     * @param array  $layoutPosition
     * @return WidgetModel
     */
    protected function createWidgetModel($widgetName, array $layoutPosition = null)
    {
        $widgetModel = $this->dashboardManager->createWidgetModel($widgetName);

        if (null !== $layoutPosition) {
            $widgetModel->setLayoutPosition($layoutPosition);
        }
        return $widgetModel;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 20;
    }
}
